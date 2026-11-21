<?php

namespace App\Libraries;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Pengumuman otomatis dari Badilag & Mahkamah Agung (tautan ke sumber, bukan salinan isi).
 *
 * - Sumber diatur di Pengaturan → Tautan Layanan (default: pengumuman elektronik Badilag & pengumuman MA).
 * - Membaca RSS/Atom bila tersedia (langsung atau lewat <link rel="alternate">), selain itu membaca
 *   tautan pengumuman di halaman daftarnya.
 * - Hasil disimpan 1 jam; bila sumber sedang tidak dapat dibuka, daftar terakhir yang berhasil tetap tampil.
 */
class AnnouncementFeed
{
    public const TTL     = 3600;
    public const KEEP    = 30 * 86400;
    public const DEFAULT = [
        'badilag' => ['Badilag', 'https://badilag.mahkamahagung.go.id/pengumuman-elektronik'],
        'ma'      => ['Mahkamah Agung', 'https://www.mahkamahagung.go.id/id/pengumuman'],
    ];

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

    /**
     * @return list<array{source:string,label:string,title:string,url:string,date:?string}>
     */
    public function items(int $limit = 10, string $source = ''): array
    {
        $data  = $this->load();
        $items = $data['items'] ?? [];
        if ($source !== '') {
            $items = array_values(array_filter($items, static fn ($i) => $i['source'] === $source));
        }

        return array_slice($items, 0, $limit);
    }

    /** Waktu pengambilan terakhir & galat per sumber (untuk panel admin). */
    public function status(): array
    {
        $d = cache('ann_feed') ?: [];

        return ['at' => $d['at'] ?? null, 'errors' => $d['errors'] ?? [], 'count' => count($d['items'] ?? [])];
    }

    private function load(): array
    {
        if (! $this->sources()) {
            return ['items' => []];
        }
        $data = cache('ann_feed');
        if (is_array($data) && ($data['at'] ?? 0) > time() - self::TTL) {
            return $data;
        }
        // cegah banyak pengunjung mengambil bersamaan; yang lain memakai data lama
        if (is_array($data) && cache('ann_feed_lock')) {
            return $data;
        }
        cache()->save('ann_feed_lock', 1, 120);

        return $this->refresh(is_array($data) ? $data : []);
    }

    /** Ambil ulang semua sumber. Data lama dipertahankan untuk sumber yang gagal. */
    public function refresh(array $old = []): array
    {
        $old   = $old ?: (cache('ann_feed') ?: []);
        $items = [];
        $errs  = [];
        foreach ($this->sources() as $key => [$label, $url]) {
            try {
                $got = $this->fetchSource($url);
                if (! $got) {
                    throw new \RuntimeException('tidak ada pengumuman yang terbaca');
                }
                foreach ($got as $g) {
                    $items[] = ['source' => $key, 'label' => $label] + $g;
                }
            } catch (\Throwable $e) {
                $errs[$key] = $e->getMessage();
                foreach ($old['items'] ?? [] as $i) {
                    if ($i['source'] === $key) {
                        $items[] = $i;
                    }
                }
            }
        }
        usort($items, static fn ($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
        $data = ['at' => time(), 'items' => $items, 'errors' => $errs];
        cache()->save('ann_feed', $data, self::KEEP);
        cache()->delete('ann_feed_lock');

        return $data;
    }

    /** @return list<array{title:string,url:string,date:?string}> */
    private function fetchSource(string $url): array
    {
        [$body, $type] = $this->get($url);
        if ($this->looksLikeFeed($body, $type)) {
            return $this->parseFeed($body);
        }
        // halaman HTML: cari tautan RSS/Atom
        if (preg_match_all('/<link\b[^>]*>/i', $body, $m)) {
            foreach ($m[0] as $tag) {
                if (preg_match('/type=["\']application\/(rss|atom)\+xml/i', $tag) && preg_match('/href=["\']([^"\']+)/i', $tag, $h)) {
                    $feedUrl = $this->absolute(html_entity_decode($h[1]), $url);
                    if ($feedUrl && $this->sameSite($feedUrl, $url)) {
                        try {
                            [$fb, $ft] = $this->get($feedUrl);
                            if ($this->looksLikeFeed($fb, $ft) && ($items = $this->parseFeed($fb))) {
                                return $items;
                            }
                        } catch (\Throwable) {
                        }
                    }
                }
            }
        }

        return $this->parseHtml($body, $url);
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
                $date = $this->findDate($n->textContent);
            }
            $seen[$href] = true;
            $out[]       = $this->item($title, $href, $date);
            if (count($out) >= 30) {
                break;
            }
        }

        return array_values(array_filter($out));
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
