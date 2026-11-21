<?php

namespace App\Libraries;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Arsip pengumuman otomatis dari Badilag & Mahkamah Agung (judul, tanggal, tautan ke sumber — isi tidak disalin).
 *
 * - Sumber diatur di Pengaturan → Tautan Layanan (default: pengumuman elektronik Badilag & pengumuman MA).
 * - Setiap pengumuman disimpan sekali di tabel external_notices; pengambilan berikutnya hanya menambah yang
 *   belum ada (dicocokkan lewat alamatnya) dan berhenti begitu bertemu pengumuman yang sudah tersimpan.
 * - Isi awal: pengumuman lama diambil mundur bertahap (RSS ?start=10, 20, …) sampai tahun di "feed_since".
 * - Membaca RSS/Atom bila tersedia (langsung atau lewat <link rel="alternate">), selain itu tautan di halaman daftarnya.
 */
class AnnouncementFeed
{
    public const TTL     = 3600;
    public const DEFAULT = [
        'badilag' => ['Badilag', 'https://badilag.mahkamahagung.go.id/pengumuman-elektronik'],
        'ma'      => ['Mahkamah Agung', 'https://www.mahkamahagung.go.id/id/pengumuman'],
    ];

    private const FORWARD_PAGES = 5;

    /** @return array<string, array{0:string,1:string}> sumber aktif: kunci => [label, url] */
    public function sources(): array
    {
        $out = [];
        foreach (self::DEFAULT as $k => [$label, $url]) {
            $v = service('siteSettings')->get('feed_' . $k . '_url'); // null = belum diatur → bawaan; '' = dimatikan
            $u = trim($v ?? $url);
            if ($u !== '') {
                $out[$k] = [$label, $u];
            }
        }

        return $out;
    }

    /** Tahun paling awal yang diarsipkan (Pengaturan → Tautan Layanan). */
    public function sinceYear(): int
    {
        $y = (int) setting('feed_since', (string) ((int) date('Y') - 1));

        return max(2000, min((int) date('Y'), $y));
    }

    /**
     * @return list<array{source:string,label:string,title:string,url:string,date:?string}>
     */
    public function items(int $limit = 10, string $source = '', int $offset = 0): array
    {
        $this->load();
        if (! $this->ready()) {
            return [];
        }
        $labels = array_map(static fn ($s) => $s[0], self::DEFAULT);
        $b      = $this->query($source);
        $rows   = $b->orderBy('published_at', 'DESC')->orderBy('id', 'DESC')->get($limit, $offset)->getResultArray();

        return array_map(static fn ($r) => [
            'source' => $r['source'], 'label' => $labels[$r['source']] ?? $r['source'],
            'title'  => $r['title'], 'url' => $r['url'], 'date' => $r['published_at'],
        ], $rows);
    }

    public function count(string $source = ''): int
    {
        return $this->ready() ? $this->query($source)->countAllResults() : 0;
    }

    /** Waktu pengambilan terakhir, galat & jumlah per sumber (untuk panel admin). */
    public function status(): array
    {
        $d      = cache('ann_feed') ?: [];
        $state  = $this->state();
        $counts = [];
        foreach (array_keys(self::DEFAULT) as $k) {
            $counts[$k] = $this->count($k);
        }

        return ['at' => $d['at'] ?? null, 'errors' => $d['errors'] ?? [], 'counts' => $counts, 'state' => $state, 'since' => $this->sinceYear()];
    }

    private function query(string $source)
    {
        $sources = array_keys($this->sources());
        $b       = db_connect()->table('external_notices');
        if ($source !== '') {
            return $b->where('source', $source);
        }

        return $sources ? $b->whereIn('source', $sources) : $b->where('1 = 0');
    }

    private function ready(): bool
    {
        static $ok = null;

        return $ok ??= db_connect()->tableExists('external_notices');
    }

    /** Ambil yang baru bila sudah lewat 1 jam (dipanggil saat halaman dibuka). */
    private function load(): void
    {
        if (! $this->ready() || ! $this->sources()) {
            return;
        }
        $d = cache('ann_feed');
        if ((is_array($d) && ($d['at'] ?? 0) > time() - self::TTL) || cache('ann_feed_lock')) {
            return;
        }
        cache()->save('ann_feed_lock', 1, 300);
        try {
            $this->sync(3);
        } finally {
            cache()->delete('ann_feed_lock');
        }
    }

    /**
     * Ambil pengumuman yang belum ada. $backfillPages = berapa halaman arsip lama yang boleh dibaca kali ini.
     * @return array{at:int,errors:array,added:array<string,int>,done:array<string,bool>}
     */
    public function sync(int $backfillPages = 3): array
    {
        if (! $this->ready()) {
            throw new \RuntimeException('Jalankan pembaruan database di Dashboard terlebih dahulu.');
        }
        @set_time_limit(300);
        $state  = $this->state();
        $errors = [];
        $added  = [];
        $cutoff = $this->sinceYear() . '-01-01 00:00:00';
        foreach ($this->sources() as $key => [, $url]) {
            $st = $state[$key] ?? [];
            if (($st['url'] ?? '') !== $url) {
                $st = ['url' => $url]; // sumber diganti → mulai ulang
            }
            if (($st['since'] ?? 0) !== $this->sinceYear()) {
                // batas tahun diubah → baca ulang arsip dari awal (yang sudah tersimpan tetap dilewati)
                $st = ['url' => $url, 'feed' => $st['feed'] ?? null, 'since' => $this->sinceYear(), 'done' => false];
            }
            $added[$key] = 0;
            try {
                // 1) pengumuman terbaru: berhenti di halaman yang memuat pengumuman yang sudah tersimpan
                [$items, $feed] = $this->fetchPage($url, $st['feed'] ?? null, 0);
                $st['feed']     = $feed;
                for ($page = 0, $first = null; $page < self::FORWARD_PAGES && $items; $page++) {
                    [$n, $old] = $this->store($key, $items, $cutoff);
                    $added[$key] += $n;
                    if ($old || ! $feed || ($items[0]['url'] ?? null) === $first) {
                        break;
                    }
                    $first = $items[0]['url'];
                    [$items] = $this->fetchPage($url, $feed, ($page + 1) * count($items));
                }
                // 2) isi awal: baca mundur arsip lama sampai tahun batas
                if (! $feed) {
                    $st['done'] = true;
                }
                for ($i = 0; $i < $backfillPages && empty($st['done']); $i++) {
                    $offset = (int) ($st['offset'] ?? 0);
                    [$items] = $this->fetchPage($url, $feed, $offset);
                    if (! $items || ($items[0]['url'] ?? '') === ($st['last_first'] ?? null)) {
                        $st['done'] = true;
                        break;
                    }
                    [$n] = $this->store($key, $items, $cutoff);
                    $added[$key]     += $n;
                    $st['offset']     = $offset + count($items);
                    $st['last_first'] = $items[0]['url'];
                    $dated            = array_filter(array_column($items, 'date'));
                    if ($dated && max($dated) < $cutoff) {
                        $st['done'] = true; // seluruh halaman sudah lebih tua dari batas
                    }
                }
            } catch (\Throwable $e) {
                $errors[$key] = $e->getMessage();
            }
            $state[$key] = $st;
        }
        service('siteSettings')->set(['feed_state' => json_encode($state)]);
        $data = ['at' => time(), 'errors' => $errors];
        cache()->save('ann_feed', $data, 30 * 86400);
        foreach (['home_blocks'] as $prefix) {
            try {
                cache()->deleteMatching($prefix . '*');
            } catch (\Throwable) {
            }
        }

        $done = [];
        foreach ($state as $k => $v) {
            $done[$k] = ! empty($v['done']);
        }

        return $data + ['added' => $added, 'done' => $done];
    }

    private function state(): array
    {
        $s = json_decode((string) setting('feed_state', '{}'), true);

        return is_array($s) ? $s : [];
    }

    /**
     * Simpan pengumuman yang belum ada.
     * @return array{0:int,1:bool} [jumlah baru, ada yang sudah tersimpan]
     */
    private function store(string $source, array $items, string $cutoff): array
    {
        $db     = db_connect();
        $hashes = array_map(static fn ($i) => sha1($i['url']), $items);
        $have   = array_flip(array_column($db->table('external_notices')->select('url_hash')->whereIn('url_hash', $hashes)->get()->getResultArray(), 'url_hash'));
        $seen   = $have !== []; // halaman ini memuat pengumuman yang sudah tersimpan → sudah tersusul
        $new    = 0;
        foreach ($items as $i => $it) {
            $h = $hashes[$i];
            if (isset($have[$h]) || ($it['date'] && $it['date'] < $cutoff)) {
                continue;
            }
            $have[$h] = true;
            $db->table('external_notices')->ignore(true)->insert([
                'source' => $source, 'title' => $it['title'], 'url' => mb_substr($it['url'], 0, 700), 'url_hash' => $h,
                'published_at' => $it['date'] ?? date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $new++;
        }

        return [$new, $seen];
    }

    /**
     * Satu halaman daftar. $feed = alamat RSS yang sudah dikenali (null = cari dulu, false = tidak ada RSS).
     * @return array{0:list<array{title:string,url:string,date:?string}>,1:string|false}
     */
    private function fetchPage(string $url, $feed, int $start): array
    {
        if ($feed === null) {
            $feed = $this->discoverFeed($url);
        }
        if ($feed) {
            [$body] = $this->get($start ? $feed . (str_contains($feed, '?') ? '&' : '?') . 'start=' . $start : $feed);

            return [$this->parseFeed($body), $feed];
        }
        if ($start > 0) {
            return [[], false];
        }
        [$body] = $this->get($url);

        return [$this->parseHtml($body, $url), false];
    }

    /** Alamat RSS/Atom sumber: sumber itu sendiri, atau <link rel="alternate"> di halamannya. */
    private function discoverFeed(string $url)
    {
        [$body, $type] = $this->get($url);
        if ($this->looksLikeFeed($body, $type)) {
            return $url;
        }
        if (preg_match_all('/<link\b[^>]*>/i', $body, $m)) {
            foreach ($m[0] as $tag) {
                if (preg_match('/type=["\']application\/(rss|atom)\+xml/i', $tag) && preg_match('/href=["\']([^"\']+)/i', $tag, $h)) {
                    $feedUrl = $this->absolute(html_entity_decode($h[1]), $url);
                    if ($feedUrl && $this->sameSite($feedUrl, $url)) {
                        try {
                            [$fb, $ft] = $this->get($feedUrl);
                            if ($this->looksLikeFeed($fb, $ft) && $this->parseFeed($fb)) {
                                return $feedUrl;
                            }
                        } catch (\Throwable) {
                        }
                    }
                }
            }
        }

        return false;
    }

    private function looksLikeFeed(string $body, string $type): bool
    {
        return str_contains($type, 'xml') || preg_match('/^\s*(<\?xml[^>]*>\s*)?<(rss|feed|rdf:RDF)\b/i', $body);
    }

    private function parseFeed(string $xml): array
    {
        $prev = libxml_use_internal_errors(true);
        $x    = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        libxml_use_internal_errors($prev);
        if (! $x) {
            return [];
        }
        $out = [];
        foreach ($x->channel->item ?? [] as $i) {
            $out[] = $this->item((string) $i->title, (string) $i->link, (string) ($i->pubDate ?: $i->children('dc', true)->date));
        }
        foreach ($x->entry ?? [] as $e) {
            $link = '';
            foreach ($e->link as $l) {
                if ((string) $l['rel'] === '' || (string) $l['rel'] === 'alternate') {
                    $link = (string) $l['href'];
                }
            }
            $out[] = $this->item((string) $e->title, $link, (string) ($e->published ?: $e->updated));
        }

        return array_values(array_filter($out));
    }

    /** Halaman daftar tanpa RSS: ambil tautan pengumuman di isi halaman beserta tanggal di dekatnya. */
    private function parseHtml(string $html, string $pageUrl): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        $xp       = new DOMXPath($dom);
        $listPath = rtrim((string) parse_url($pageUrl, PHP_URL_PATH), '/');
        $out      = [];
        $seen     = [];
        foreach ($xp->query('//a[@href]') as $a) {
            /** @var DOMElement $a */
            if ($xp->query('ancestor::nav|ancestor::header|ancestor::footer|ancestor::*[contains(@class,"menu") or contains(@class,"breadcrumb") or contains(@class,"pagination") or contains(@class,"sidebar")]', $a)->length) {
                continue;
            }
            $href = $this->absolute(html_entity_decode($a->getAttribute('href')), $pageUrl);
            if (! $href || ! $this->sameSite($href, $pageUrl) || isset($seen[$href])) {
                continue;
            }
            $path = rtrim((string) parse_url($href, PHP_URL_PATH), '/');
            if ($path === $listPath || $path === '' || str_contains((string) parse_url($href, PHP_URL_QUERY), 'page=')) {
                continue;
            }
            $isDetail = str_starts_with($path, $listPath . '/') || preg_match('#/(media|pengumuman|announcement)s?/|\.pdf$#i', $path);
            $title    = trim(preg_replace('/\s+/u', ' ', $a->getAttribute('title') ?: $a->textContent));
            if (! $isDetail || mb_strlen($title) < 15 || preg_match('/^(selengkapnya|baca|lihat|unduh|download|read more)/i', $title)) {
                continue;
            }
            // tanggal: di elemen pembungkus terdekat (baris daftar / kartu)
            $date = null;
            for ($n = $a->parentNode, $i = 0; $n instanceof DOMElement && $i < 4 && ! $date; $n = $n->parentNode, $i++) {
                $t    = $xp->query('.//time[@datetime]', $n);
                $date = $t->length ? $t->item(0)->getAttribute('datetime') : $this->findDate($n->textContent);
            }
            $seen[$href] = true;
            if ($it = $this->item($title, $href, $date)) {
                $out[] = $it + ['_under' => str_starts_with($path, $listPath . '/')];
            }
        }
        // bila ada tautan di bawah alamat daftar (mis. /id/pengumuman/7657/judul), abaikan tautan lain (brosur, dokumen di kaki halaman)
        if (array_filter($out, static fn ($i) => $i['_under'])) {
            $out = array_filter($out, static fn ($i) => $i['_under']);
        }

        return array_map(static function ($i) {
            unset($i['_under']);

            return $i;
        }, array_slice(array_values($out), 0, 30));
    }

    private function item(string $title, string $url, ?string $date): ?array
    {
        $title = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $title = preg_replace('/\s*\|\s*\(\d{1,2}\/\d{1,2}(\/\d{2,4})?\)\s*$/', '', $title); // "… | (19/11)"
        $url   = trim($url);
        if ($title === '' || ! preg_match('#^https?://#i', $url)) {
            return null;
        }
        $ts = $date ? strtotime($date) : false;

        return ['title' => mb_substr($title, 0, 300), 'url' => $url, 'date' => $ts ? date('Y-m-d H:i:s', $ts) : null];
    }

    /** Tanggal berbahasa Indonesia/Inggris atau angka di dalam teks → Y-m-d. */
    private function findDate(string $text): ?string
    {
        $mon = ['januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6, 'jul' => 7, 'agu' => 8, 'aug' => 8, 'agt' => 8, 'sep' => 9, 'okt' => 10, 'oct' => 10, 'nov' => 11, 'des' => 12, 'dec' => 12,
            'january' => 1, 'february' => 2, 'march' => 3, 'june' => 6, 'july' => 7, 'august' => 8, 'october' => 10, 'december' => 12];
        if (preg_match('/(?<!\d)(\d{1,2})\s+([a-z]{3,9})\.?\s+(\d{4})\b/i', $text, $m) && isset($mon[strtolower($m[2])])) {
            return sprintf('%04d-%02d-%02d', $m[3], $mon[strtolower($m[2])], $m[1]);
        }
        if (preg_match('/\b(\d{4})-(\d{2})-(\d{2})\b/', $text, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }
        if (preg_match('#\b(\d{1,2})[/-](\d{1,2})[/-](\d{4})\b#', $text, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        return null;
    }

    /** @return array{0:string,1:string} [isi, content-type] */
    private function get(string $url): array
    {
        if (! function_exists('curl_init')) {
            throw new \RuntimeException('ekstensi PHP cURL belum aktif');
        }
        $ch  = curl_init($url);
        $buf = '';
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 4,
            CURLOPT_PROTOCOLS       => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT  => 5,
            CURLOPT_TIMEOUT         => 10,
            CURLOPT_ENCODING        => '',
            CURLOPT_USERAGENT       => 'Mozilla/5.0 (compatible; WebsiteSatker/1.0; +' . base_url() . ')',
            CURLOPT_WRITEFUNCTION   => static function ($c, $chunk) use (&$buf) {
                $buf .= $chunk;

                return strlen($buf) > 3 * 1048576 ? 0 : strlen($chunk);
            },
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $type = strtolower((string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
        $err  = curl_errno($ch) ? curl_error($ch) : '';
        curl_close($ch);
        if ($code === 0 || $code >= 400) {
            throw new \RuntimeException($err ?: 'HTTP ' . $code);
        }

        return [$buf, $type];
    }

    /** Domain sama (boleh berbeda subdomain di bawah domain induk yang sama, mis. *.mahkamahagung.go.id). */
    private function sameSite(string $a, string $b): bool
    {
        $root = static function (string $u): string {
            $h = strtolower((string) parse_url($u, PHP_URL_HOST));
            $p = explode('.', $h);

            return implode('.', array_slice($p, -3)); // mahkamahagung.go.id
        };

        return $root($a) !== '' && $root($a) === $root($b);
    }

    private function absolute(string $href, string $from): ?string
    {
        $href = trim($href);
        if ($href === '' || $href[0] === '#' || preg_match('/^(javascript|mailto|tel|data):/i', $href)) {
            return null;
        }
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }
        $f    = parse_url($from);
        $root = ($f['scheme'] ?? 'https') . '://' . ($f['host'] ?? '');
        if (str_starts_with($href, '//')) {
            return ($f['scheme'] ?? 'https') . ':' . $href;
        }
        if ($href[0] === '/') {
            return $root . $href;
        }

        return $root . preg_replace('#/[^/]*$#', '/', $f['path'] ?? '/') . $href;
    }
}
