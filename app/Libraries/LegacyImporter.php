<?php

namespace App\Libraries;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;

/**
 * Impor berita dari website lama berbasis Joomla (template website PA/PTA lama) atau WordPress.
 *
 * WordPress: data diambil lewat REST API bawaan (/wp-json/wp/v2/…): daftar berita per kategori,
 * judul, isi, tanggal, gambar utama. Penanda posts.source_ref = "wp:<id>".
 *
 * - Daftar artikel diambil dari halaman kategori (…/berita/berita-terkini.html?limit=0, atau per halaman).
 * - Setiap artikel: judul, tanggal, penulis, jumlah dibaca, isi; gambar & PDF dari situs lama diunduh
 *   ke website baru, gambar pertama menjadi gambar utama.
 * - Penanda posts.source_ref = "joomla:<id>" mencegah dobel bila impor diulang, dan dipakai untuk
 *   mengalihkan (301) alamat berita lama ke alamat baru (lihat Site::notFound).
 * - Hanya mengunduh dari host situs lama yang diisi admin (mencegah server dipakai mengambil alamat lain).
 */
class LegacyImporter
{
    public const MAX_PAGE_BYTES = 6 * 1048576;

    private string $base;   // https://pa-contoh.go.id
    private string $host;   // pa-contoh.go.id (tanpa www.)
    private bool $insecure;
    private Uploader $uploader;
    private ?string $platform; // 'joomla' | 'wordpress' | null = deteksi otomatis

    public function __construct(string $siteUrl, bool $insecure = false, string $platform = 'auto')
    {
        $siteUrl = trim($siteUrl);
        if (! preg_match('#^https?://#i', $siteUrl)) {
            $siteUrl = 'https://' . $siteUrl;
        }
        $p = parse_url($siteUrl);
        if (empty($p['host']) || ! preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $p['host'])) {
            throw new RuntimeException('Alamat website lama tidak valid.');
        }
        $this->host     = preg_replace('/^www\./i', '', strtolower($p['host']));
        $this->base     = strtolower($p['scheme'] ?? 'https') . '://' . strtolower($p['host']);
        $this->insecure = $insecure;
        $this->uploader = service('uploader');
        $this->platform = in_array($platform, ['joomla', 'wordpress'], true) ? $platform : null;
    }

    /** Jenis website lama: 'wordpress' bila REST API WordPress menjawab, selain itu 'joomla'. */
    public function platform(): string
    {
        if ($this->platform !== null) {
            return $this->platform;
        }
        $res = $this->wpGet('/');
        if ($res['code'] === 200 && is_array($res['data']) && in_array('wp/v2', (array) ($res['data']['namespaces'] ?? []), true)) {
            return $this->platform = 'wordpress';
        }
        $home = $this->fetch($this->base . '/');
        if (preg_match('#/wp-(content|includes)/#i', $home['body'])) {
            throw new RuntimeException('Website lama memakai WordPress, tetapi jalur datanya (REST API /wp-json/) ditutup. '
                . 'Minta pengelola website lama membuka REST API sementara selama proses impor.');
        }

        return $this->platform = 'joomla';
    }

    private ?bool $isWp = null;

    /**
     * Petunjuk bila impor dijalankan dari tab Joomla padahal website lama memakai WordPress
     * (dipanggil hanya saat terjadi galat). Kosong bila tidak relevan.
     */
    public function wrongTabHint(): string
    {
        if ($this->platform !== 'joomla') {
            return '';
        }
        if ($this->isWp === null) {
            $this->isWp = false;
            try {
                $res = $this->wpGet('/');
                if ($res['code'] === 200 && is_array($res['data']) && in_array('wp/v2', (array) ($res['data']['namespaces'] ?? []), true)) {
                    $this->isWp = true;
                } else {
                    $this->isWp = (bool) preg_match('#/wp-(content|includes)/#i', $this->fetch($this->base . '/')['body']);
                }
            } catch (\Throwable) {
            }
        }

        return $this->isWp ? ' Website lama ini memakai WordPress, bukan Joomla — buka tab "Website WordPress" di atas lalu ulangi dari sana.' : '';
    }

    // ------------------------------------------------------------------ daftar artikel

    /**
     * @return list<array{url:string,id:int}> artikel di halaman kategori (terbaru dulu)
     */
    public function listArticles(string $categoryUrl): array
    {
        if ($this->platform() === 'wordpress') {
            return $this->wpList($categoryUrl);
        }
        $url = $this->absolute($categoryUrl, $this->base . '/');
        if (! $url || ! $this->isOwnHost($url)) {
            throw new RuntimeException('Alamat kategori harus berada di ' . $this->host . '.');
        }
        $catPath = preg_replace('#\.html?$#i', '', trim((string) parse_url($url, PHP_URL_PATH), '/'));
        if ($catPath === '') {
            throw new RuntimeException('Alamat kategori tidak valid, contoh: ' . $this->base . '/berita/berita-terkini.html');
        }
        // alamat artikel Joomla: …/123-judul.html, atau tanpa akhiran .html (SEF tanpa sufiks)
        $re = '#^/' . preg_quote($catPath, '#') . '/(?:[\w-]+/)*(\d+)-[^/?\#.]+(?:\.html?)?/?$#i';

        $found = [];
        $pages = [$this->withQuery($url, 'limit=0')];
        $seen  = [];
        for ($i = 0; $i < 300 && $pages; $i++) {
            $page = array_shift($pages);
            if (isset($seen[$page])) {
                continue;
            }
            $seen[$page] = true;
            $res         = $this->fetch($page);
            if ($res['code'] >= 400 || $res['body'] === '') {
                if ($i === 0) {
                    throw new RuntimeException('Halaman kategori tidak dapat dibuka (HTTP ' . $res['code'] . ').');
                }
                continue;
            }
            $before = count($found);
            preg_match_all('/href\s*=\s*["\']([^"\']+)["\']/i', $res['body'], $m);
            foreach ($m[1] as $href) {
                $abs = $this->absolute(html_entity_decode($href), $page);
                if (! $abs || ! $this->isOwnHost($abs)) {
                    continue;
                }
                $path = (string) parse_url($abs, PHP_URL_PATH);
                if (preg_match($re, $path, $mm) && ! isset($found[(int) $mm[1]])) {
                    $found[(int) $mm[1]] = ['url' => $this->base . $path, 'id' => (int) $mm[1]];
                }
                // halaman berikutnya (bila ?limit=0 tidak didukung): ?start=N / ?limitstart=N di kategori yang sama
                if (preg_match('#^/' . preg_quote($catPath, '#') . '(\.html?)?$#i', $path) && preg_match('/(?:^|&)(?:limit)?start=(\d+)/', (string) parse_url($abs, PHP_URL_QUERY), $sm)) {
                    $next = $this->base . $path . '?start=' . (int) $sm[1];
                    if (! isset($seen[$next]) && ! in_array($next, $pages, true)) {
                        $pages[] = $next;
                    }
                }
            }
            if ($i > 0 && count($found) === $before && ! $pages) {
                break;
            }
        }
        if (! $found) {
            throw new RuntimeException('Tidak ada berita yang ditemukan di halaman kategori ini.');
        }
        krsort($found);

        return array_values($found);
    }

    // ------------------------------------------------------------------ impor satu artikel

    /**
     * @param array{category:string,since?:int,images?:bool,status?:string} $opt
     * @return array{status:string,message:string,title?:string,date?:string,slug?:string,id?:int}
     */
    public function import(string $url, array $opt): array
    {
        if ($this->platform() === 'wordpress') {
            return $this->wpImport($url, $opt);
        }
        $db  = db_connect();
        $url = $this->absolute($url, $this->base . '/');
        if (! $url) {
            return ['status' => 'error', 'message' => 'Alamat berita tidak valid.'];
        }
        if (! $this->isOwnHost($url)) {
            return ['status' => 'error', 'message' => 'Berita ini dari ' . parse_url($url, PHP_URL_HOST) . ', sedangkan Website lama (kotak 1) berisi '
                . $this->host . '. Isi kotak 1 dengan alamat website asal berita tersebut.'];
        }
        if (! preg_match('#/(\d+)-[^/.]+(?:\.html?)?/?$#i', (string) parse_url($url, PHP_URL_PATH), $m)) {
            return ['status' => 'error', 'message' => 'Alamat berita tidak dikenali. Tempel alamat satu berita (mis. …/berita-terkini/785-judul-berita atau …/785-judul-berita.html), bukan halaman kategori.'];
        }
        $ref = 'joomla:' . (int) $m[1];
        $old = $db->table('posts')->select('id, slug, title, image')->where('source_ref', $ref)->get()->getRowArray();
        if ($old && empty($opt['overwrite'])) {
            return ['status' => 'skip', 'message' => 'Sudah ada', 'title' => $old['title'], 'slug' => $old['slug'], 'id' => (int) $old['id']];
        }
        // Bila "timpa": berita lama BARU dihapus setelah berita baru berhasil dibangun (lihat sebelum insert),
        // agar berita lama tidak hilang jika pengambilan dari website lama gagal.

        $res = $this->fetch($url);
        if ($res['code'] >= 400 || $res['body'] === '') {
            return ['status' => 'error', 'message' => 'Gagal dibuka (HTTP ' . $res['code'] . ($res['error'] ? ', ' . $res['error'] : '') . ')'];
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $res['body'], LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        $xp = new DOMXPath($dom);

        // wadah berita: template Joomla 3 (article.item / div.item-page), Joomla 4/5 (com-content-article),
        // Joomla 1.5 (table.contentpaneopen) dan template lama lain
        $article = $this->first($xp, "//article[contains(concat(' ',normalize-space(@class),' '),' item ')]")
            ?? $this->first($xp, "//div[contains(@class,'item-page')]")
            ?? $this->first($xp, "//div[contains(@class,'com-content-article')]")
            ?? $this->first($xp, "//*[@itemprop='articleBody']/..")
            ?? $this->first($xp, "//table[contains(@class,'contentpaneopen')]/..")
            ?? $this->first($xp, "//*[@id='component' or @id='content' or contains(@class,'componentcontent') or contains(@class,'component-content')]");
        if (! $article) {
            return ['status' => 'error', 'message' => 'Isi berita tidak dikenali (template website lama tidak dikenal)'];
        }
        $body = $this->first($xp, ".//div[contains(concat(' ',normalize-space(@class),' '),' content ')]", $article)
            ?? $this->first($xp, ".//*[@itemprop='articleBody']", $article)
            ?? $this->first($xp, ".//div[contains(@class,'com-content-article__body')]", $article)
            ?? $this->first($xp, "(.//table[contains(@class,'contentpaneopen')])[last()]", $article) ?? $article;

        // judul
        $title = $this->text($this->first($xp, ".//*[@itemprop='headline' or @itemprop='name']", $article))
            ?: $this->text($this->first($xp, ".//*[contains(@class,'contentheading') or contains(@class,'item-title') or contains(@class,'article-title')]", $article))
            ?: $this->text($this->first($xp, ".//*[contains(@class,'page-header')]//*[self::h1 or self::h2]", $article))
            ?: $this->text($this->first($xp, "//meta[@property='og:title']/@content"))
            ?: $this->text($this->first($xp, ".//h1 | .//h2", $article))
            ?: $this->text($this->first($xp, '//title'));
        // template yang <title>-nya nama situs (judul berita hanya teks tebal di awal isi)
        $docTitle = $this->text($this->first($xp, '//title'));
        if ($title === '' || $title === $docTitle) {
            foreach (iterator_to_array($xp->query('.//strong | .//b', $body)) as $st) {
                $t = trim(preg_replace('/\s+/u', ' ', $st->textContent));
                if (mb_strlen($t) >= 10 && mb_strlen($t) <= 250 && ! preg_match('/^(published|ditulis|dibuat|diterbitkan)\b/iu', $t)) {
                    $title = $t;
                    break;
                }
            }
        }
        $title = trim(preg_replace('/\s+/u', ' ', html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $title = preg_replace('/\s+[-|–]\s+' . preg_quote((string) setting('site_name', '#'), '/') . '$/iu', '', $title);
        if ($title === '') {
            return ['status' => 'error', 'message' => 'Judul tidak ditemukan'];
        }

        // tanggal, penulis, dibaca
        $meta  = $this->text($this->first($xp, ".//*[contains(@class,'meta') or contains(@class,'article-info')]", $article));
        $date  = $this->text($this->first($xp, './/time/@datetime', $article))
            ?: $this->text($this->first($xp, "//meta[@property='article:published_time']/@content"))
            ?: $this->text($this->first($xp, "//*[@itemprop='datePublished']/@datetime | //meta[@itemprop='datePublished']/@content"));
        $ts = $date ? strtotime($date) : false;
        if (! $ts) {
            // template lama: tanggal hanya berupa teks, mis. "Published: Friday, 13 November 2026 07:57" / "Jumat, 13 November 2026"
            $txt = $meta . ' ' . $this->text($this->first($xp, ".//*[contains(@class,'published') or contains(@class,'createdate') or contains(@class,'create') or contains(@class,'date')]", $article))
                . ' ' . mb_substr($this->text($article), 0, 1500);
            if (($loose = self::looseDate($txt)) !== null) {
                $date = $loose;
                $ts   = strtotime($loose);
            }
        }
        if (! $ts) {
            return ['status' => 'error', 'message' => 'Tanggal terbit tidak ditemukan', 'title' => $title];
        }
        if (strlen($date) <= 10) {
            $ts += 8 * 3600; // hanya tanggal → pukul 08.00
        }
        if (! empty($opt['since']) && (int) date('Y', $ts) < (int) $opt['since']) {
            return ['status' => 'skip', 'message' => 'Terbit ' . date('Y', $ts) . ', sebelum tahun ' . (int) $opt['since'], 'title' => $title];
        }
        $author = preg_match('/(?:Ditulis oleh|Written by)\s+(.+?)\s+(?:on|pada|\bDitulis\b|$)/iu', $meta, $am) ? trim($am[1], " .:\t") : 'Humas';
        $views  = preg_match('/(?:Dilihat|Hits|Dibaca)\s*:?\s*([\d.,]+)/iu', $meta, $vm) ? (int) preg_replace('/\D/', '', $vm[1]) : 0;

        $alias = preg_replace(['#/+$#', '#^.*/\d+-#', '#\.html?$#i'], '', (string) parse_url($url, PHP_URL_PATH));

        return $this->build($dom, $xp, $body, $url, [
            'ref' => $ref, 'old' => $old, 'title' => $title, 'ts' => $ts, 'author' => $author, 'views' => $views,
            'slug' => $alias ?: url_title($title, '-', true), 'id' => (int) $m[1], 'cover' => null,
        ], $opt);
    }

    /**
     * Bersihkan isi, unduh gambar & PDF, tentukan gambar utama, lalu simpan berita (dipakai Joomla & WordPress).
     * $p: ref, old, title, ts, author, views, slug (dasar slug), id, cover (URL gambar utama terpisah, mis. featured image WordPress)
     */
    private function build(DOMDocument $dom, DOMXPath $xp, DOMNode $body, string $url, array $p, array $opt): array
    {
        $db    = db_connect();
        $title = $p['title'];
        $old   = $p['old'];
        $ts    = $p['ts'];

        // bersihkan isi
        foreach (['.//script', './/style', './/form', './/noscript', ".//*[@id='jc' or @id='comments' or contains(@class,'jcomments')]",
            ".//*[contains(@class,'pagenav') or contains(@class,'pager') or contains(@class,'icons') or contains(@class,'tags')]",
            // keterangan artikel bawaan Joomla (tanggal terbit, penulis, dibaca, judul) — sudah diambil terpisah, jangan ikut jadi isi
            ".//*[contains(concat(' ',normalize-space(@class),' '),' article-info ') or contains(@class,'createdate') or contains(@class,'createdby') or contains(@class,'modifydate') or contains(@class,'contentheading') or contains(@class,'page-header') or contains(@class,'buttonheading')]",
            ".//dd[contains(@class,'hits') or contains(@class,'published') or contains(@class,'create') or contains(@class,'modified') or contains(@class,'category-name') or contains(@class,'parent-category-name')]",
            ".//*[contains(@class,'sharedaddy') or contains(@class,'jp-relatedposts') or contains(@class,'wp-block-buttons') and .//a[contains(@href,'share')]]"] as $q) {
            foreach (iterator_to_array($xp->query($q, $body)) as $n) {
                $n->parentNode?->removeChild($n);
            }
        }
        $this->dropTitleRepeat($body, $title);

        $wantImages = $opt['images'] ?? true;
        $images     = 0;
        $pdfs       = 0;
        $cover      = null;
        $coverNode  = null;
        $coverKey   = null;
        // gambar utama terpisah (featured image WordPress)
        if (! empty($p['cover']) && $wantImages && ($cu = $this->absolute($p['cover'], $url)) && $this->isOwnHost($cu) && ($rel = $this->downloadImage($cu))) {
            $cover    = $rel;
            $coverKey = $this->imageKey($cu);
            $images++;
        }

        // gambar & lampiran PDF
        foreach (iterator_to_array($xp->query('.//img', $body)) as $img) {
            /** @var DOMElement $img */
            $raw = $img->getAttribute('src');
            foreach (['data-src', 'data-lazy-src', 'data-orig-file'] as $lazy) { // gambar "lazy load"
                if ((trim($raw) === '' || str_starts_with($raw, 'data:')) && $img->getAttribute($lazy) !== '') {
                    $raw = $img->getAttribute($lazy);
                }
            }
            foreach (['srcset', 'sizes', 'data-src', 'data-srcset', 'data-lazy-src', 'data-lazy-srcset', 'data-orig-file', 'data-medium-file', 'data-large-file', 'loading', 'decoding', 'fetchpriority'] as $attr) {
                $img->removeAttribute($attr);
            }
            $src = $this->absolute(html_entity_decode($raw), $url);
            if (! $src || str_starts_with($src, 'data:')) {
                $img->parentNode?->removeChild($img);
                continue;
            }
            $img->setAttribute('src', $src);
            if ($coverKey !== null && $coverNode === null && $this->imageKey($src) === $coverKey) {
                // gambar yang sama dengan gambar utama: pakai berkas yang sudah diunduh
                $img->setAttribute('src', base_url($cover));
                $coverNode = $img;
            } elseif ($wantImages && $this->isOwnHost($src) && ($rel = $this->downloadImage($src))) {
                $img->setAttribute('src', base_url($rel));
                $images++;
                if ($cover === null) {
                    $cover     = $rel;
                    $coverNode = $img;
                }
            }
            if (! $img->getAttribute('alt') || preg_match('/^\w{0,3}\d*$/', $img->getAttribute('alt'))) {
                $img->setAttribute('alt', $title);
            }
        }
        foreach (iterator_to_array($xp->query('.//a[@href]', $body)) as $a) {
            /** @var DOMElement $a */
            $href = $this->absolute(html_entity_decode($a->getAttribute('href')), $url);
            if (! $href) {
                continue;
            }
            $a->setAttribute('href', $href);
            if ($wantImages && $this->isOwnHost($href) && preg_match('/\.pdf$/i', (string) parse_url($href, PHP_URL_PATH)) && ($fid = $this->downloadPdf($href))) {
                $a->setAttribute('href', file_url($fid));
                $pdfs++;
            }
        }
        // gambar utama dihapus dari isi bila letaknya di awal (agar tidak tampil dua kali)
        if ($coverNode && ! $this->insideTag($coverNode, ['a', 'table']) && $this->isNearTop($coverNode, $body)) {
            $pn = $coverNode->parentNode;
            $pn?->removeChild($coverNode);
            while ($pn instanceof DOMElement && $pn !== $body && trim(str_replace("\u{a0}", '', $pn->textContent)) === '' && ! $xp->query('.//img|.//iframe', $pn)->length) {
                $up = $pn->parentNode;
                $up?->removeChild($pn);
                $pn = $up;
            }
        }

        $html = '';
        foreach ($body->childNodes as $c) {
            $html .= $dom->saveHTML($c);
        }
        $html = service('purifier')->clean($html);
        if (trim(strip_tags($html, '<img><iframe><a>')) === '' && ! $cover) {
            return ['status' => 'error', 'message' => 'Isi berita kosong', 'title' => $title];
        }

        $text    = trim(preg_replace(['/\s+/u', '/\s+([.,;:!?)])/u'], [' ', '$1'], html_entity_decode(strip_tags(str_replace('<', ' <', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $excerpt = mb_strlen($text) > 240 ? rtrim(mb_substr($text, 0, mb_strrpos(mb_substr($text, 0, 240), ' ') ?: 240), ' ,.;:') . '…' : $text;

        // "timpa": sekarang berita baru siap — hapus berita lama (beserta gambar utamanya) tepat sebelum menyimpan yang baru
        if ($old) {
            if (! empty($old['image']) && $old['image'] !== $cover) {
                $this->uploader->deleteImage($old['image']);
            }
            $db->table('posts')->where('id', (int) $old['id'])->delete();
        }

        $now = date('Y-m-d H:i:s');
        $db->table('posts')->insert([
            'category'     => $opt['category'],
            'slug'         => $this->slug((string) $p['slug'], (int) $p['id'], $title),
            'title'        => mb_substr($title, 0, 255),
            'excerpt'      => $excerpt,
            'content'      => $html,
            'image'        => $cover,
            'image_alt'    => $cover ? mb_substr($title, 0, 255) : null,
            'status'       => ($opt['status'] ?? 'published') === 'draft' ? 'draft' : 'published',
            'published_at' => date('Y-m-d H:i:s', $ts),
            'author'       => mb_substr((string) $p['author'], 0, 120),
            'views'        => (int) $p['views'],
            'created_at'   => $now,
            'updated_at'   => $now,
            'source_ref'   => $p['ref'],
        ]);
        $id   = (int) $db->insertID();
        $slug = (string) $db->table('posts')->select('slug')->where('id', $id)->get()->getRow('slug');

        $info = [];
        if ($images) {
            $info[] = $images . ' gambar';
        }
        if ($pdfs) {
            $info[] = $pdfs . ' PDF';
        }

        return ['status' => 'ok', 'message' => $info ? implode(', ', $info) : 'tanpa gambar', 'title' => $title, 'date' => date('Y-m-d', $ts), 'slug' => $slug, 'id' => $id];
    }

    // ------------------------------------------------------------------ WordPress (REST API)

    /**
     * Daftar berita WordPress dari alamat kategori (…/category/berita/ atau ?cat=24). Kosong/akar situs = semua berita.
     * @return list<array{url:string,id:int,ref:string}>
     */
    private function wpList(string $categoryUrl): array
    {
        $catId = null;
        $categoryUrl = trim($categoryUrl);
        if ($categoryUrl !== '') {
            $url = $this->absolute($categoryUrl, $this->base . '/');
            if (! $url || ! $this->isOwnHost($url)) {
                throw new RuntimeException('Alamat kategori harus berada di ' . $this->host . '.');
            }
            $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
            parse_str((string) parse_url($url, PHP_URL_QUERY), $qs);
            $path = preg_replace('#/page/\d+$#', '', $path); // halaman ke-2 dst. dari daftar kategori
            if (! empty($qs['cat']) && ctype_digit((string) $qs['cat'])) {
                $catId = (int) $qs['cat'];
            } elseif (preg_match('#(?:^|/)(?:category|kategori)/(?:[^/]+/)*([^/]+)$#i', $path, $m)) {
                $res = $this->wpGet('/wp/v2/categories', ['slug' => rawurldecode($m[1]), '_fields' => 'id,name,count']);
                if (empty($res['data'][0]['id'])) {
                    throw new RuntimeException('Kategori "' . rawurldecode($m[1]) . '" tidak ditemukan di website lama.');
                }
                $catId = (int) $res['data'][0]['id'];
            } elseif ($path !== '') {
                throw new RuntimeException('Alamat kategori WordPress tidak dikenali. Contoh: ' . $this->base . '/category/berita/ (kosongkan untuk semua berita).');
            }
        }

        $found = [];
        for ($page = 1; $page <= 300; $page++) {
            $q = ['per_page' => 100, 'page' => $page, '_fields' => 'id,link', 'orderby' => 'date', 'order' => 'desc'];
            if ($catId) {
                $q['categories'] = $catId;
            }
            $res = $this->wpGet('/wp/v2/posts', $q);
            if ($res['code'] === 400 && $page > 1) {
                break; // melewati halaman terakhir
            }
            if ($res['code'] !== 200 || ! is_array($res['data'])) {
                if ($page === 1) {
                    throw new RuntimeException('Daftar berita WordPress tidak dapat diambil (HTTP ' . $res['code'] . ').');
                }
                break;
            }
            foreach ($res['data'] as $r) {
                $id = (int) ($r['id'] ?? 0);
                if ($id && ! isset($found[$id])) {
                    $link       = (string) ($r['link'] ?? '');
                    $found[$id] = ['url' => $link !== '' && $this->isOwnHost($link) ? $link : $this->base . '/?p=' . $id, 'id' => $id, 'ref' => 'wp:' . $id];
                }
            }
            if (count($res['data']) < 100) {
                break;
            }
        }
        if (! $found) {
            throw new RuntimeException('Tidak ada berita yang ditemukan di kategori ini.');
        }

        return array_values($found); // sudah urut terbaru dulu
    }

    /**
     * Daftar kategori WordPress (untuk dipilih di halaman impor).
     * @return list<array{id:int,name:string,slug:string,count:int,url:string}>
     */
    public function wpCategories(): array
    {
        if ($this->platform() !== 'wordpress') {
            throw new RuntimeException('Website ini tidak terdeteksi sebagai WordPress. Gunakan tab Joomla.');
        }
        $out = [];
        for ($page = 1; $page <= 10; $page++) {
            $res = $this->wpGet('/wp/v2/categories', ['per_page' => 100, 'page' => $page, 'hide_empty' => 'true', '_fields' => 'id,name,slug,count,link']);
            if ($res['code'] !== 200 || ! is_array($res['data']) || ! $res['data']) {
                break;
            }
            foreach ($res['data'] as $c) {
                $link  = (string) ($c['link'] ?? '');
                $out[] = ['id' => (int) $c['id'], 'name' => html_entity_decode((string) $c['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'slug' => (string) $c['slug'],
                    'count' => (int) $c['count'], 'url' => $link !== '' && $this->isOwnHost($link) ? $link : $this->base . '/?cat=' . (int) $c['id']];
            }
            if (count($res['data']) < 100) {
                break;
            }
        }
        if (! $out) {
            throw new RuntimeException('Kategori WordPress tidak dapat diambil.');
        }
        usort($out, static fn ($a, $b) => $b['count'] <=> $a['count']);

        return $out;
    }

    /** Impor satu berita WordPress dari alamat beritanya (…/judul-berita/ atau …/?p=123). */
    private function wpImport(string $url, array $opt): array
    {
        $db  = db_connect();
        $url = $this->absolute($url, $this->base . '/');
        if (! $url || ! $this->isOwnHost($url)) {
            return ['status' => 'error', 'message' => 'Alamat berita harus berada di ' . $this->host . '.'];
        }
        parse_str((string) parse_url($url, PHP_URL_QUERY), $qs);
        $embed = ['_embed' => 'wp:featuredmedia'];
        if (! empty($qs['p']) && ctype_digit((string) $qs['p'])) {
            $res  = $this->wpGet('/wp/v2/posts/' . (int) $qs['p'], $embed);
            $post = $res['code'] === 200 && is_array($res['data']) ? $res['data'] : null;
        } else {
            $segs = array_values(array_filter(explode('/', trim((string) parse_url($url, PHP_URL_PATH), '/')), 'strlen'));
            $slug = rawurldecode((string) end($segs));
            if ($slug === '' || in_array(strtolower($slug), ['category', 'kategori', 'page', 'tag'], true)) {
                return ['status' => 'error', 'message' => 'Alamat berita tidak valid (tempel alamat satu berita, bukan halaman kategori).'];
            }
            $res  = $this->wpGet('/wp/v2/posts', $embed + ['slug' => $slug]);
            $post = $res['code'] === 200 && ! empty($res['data'][0]) ? $res['data'][0] : null;
        }
        if (! $post || empty($post['id'])) {
            return ['status' => 'error', 'message' => 'Berita tidak ditemukan di website lama (mungkin berupa halaman, bukan berita).'];
        }

        $ref = 'wp:' . (int) $post['id'];
        $old = $db->table('posts')->select('id, slug, title, image')->where('source_ref', $ref)->get()->getRowArray();
        $title = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) ($post['title']['rendered'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($old && empty($opt['overwrite'])) {
            return ['status' => 'skip', 'message' => 'Sudah ada', 'title' => $old['title'], 'slug' => $old['slug'], 'id' => (int) $old['id']];
        }
        if ($title === '') {
            return ['status' => 'error', 'message' => 'Judul tidak ditemukan'];
        }
        if (! empty($post['content']['protected'])) {
            return ['status' => 'error', 'message' => 'Berita dilindungi kata sandi', 'title' => $title];
        }
        $ts = strtotime((string) ($post['date'] ?? ''));
        if (! $ts) {
            return ['status' => 'error', 'message' => 'Tanggal terbit tidak ditemukan', 'title' => $title];
        }
        if (! empty($opt['since']) && (int) date('Y', $ts) < (int) $opt['since']) {
            return ['status' => 'skip', 'message' => 'Terbit ' . date('Y', $ts) . ', sebelum tahun ' . (int) $opt['since'], 'title' => $title];
        }

        // penulis: nama akun admin (mis. "adm paptk") diganti "Humas"
        $author = trim((string) ($post['yoast_head_json']['author'] ?? ''));
        if ($author === '' || preg_match('/^(adm|admin|administrator|operator|user|webmaster|editor)\b/i', $author)) {
            $author = 'Humas';
        }
        $views = 0;
        foreach (['magazineBlocksPostViewCount', 'post_views_count', 'views'] as $k) {
            if (isset($post[$k]) && is_numeric($post[$k])) {
                $views = (int) $post[$k];
                break;
            }
        }
        // gambar utama: featured image → og:image (Yoast) → gambar pertama di isi
        $cover = $post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? null;
        if (! is_string($cover) || $cover === '') {
            $cover = $post['yoast_head_json']['og_image'][0]['url'] ?? ($post['jetpack_featured_media_url'] ?? null);
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="wp-isi">' . (string) ($post['content']['rendered'] ?? '') . '</div>', LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        $xp   = new DOMXPath($dom);
        $body = $this->first($xp, "//div[@id='wp-isi']");
        if (! $body) {
            return ['status' => 'error', 'message' => 'Isi berita tidak dikenali', 'title' => $title];
        }
        $link = (string) ($post['link'] ?? $url);

        return $this->build($dom, $xp, $body, $this->isOwnHost($link) ? $link : $url, [
            'ref' => $ref, 'old' => $old, 'title' => $title, 'ts' => $ts, 'author' => $author, 'views' => $views,
            'slug' => (string) ($post['slug'] ?? ''), 'id' => (int) $post['id'], 'cover' => is_string($cover) ? $cover : null,
        ], $opt);
    }

    /**
     * Panggil REST API WordPress website lama. Jalur /wp-json/… lalu cadangan ?rest_route=… (permalink polos).
     * @return array{code:int,data:mixed}
     */
    private function wpGet(string $route, array $query = []): array
    {
        $qs  = $query ? http_build_query($query) : '';
        $res = $this->fetch($this->base . '/wp-json' . $route . ($qs !== '' ? '?' . $qs : ''));
        $data = json_decode($res['body'], true);
        if ($data === null && in_array($res['code'], [0, 404], true)) {
            $res  = $this->fetch($this->base . '/?rest_route=' . rawurlencode($route) . ($qs !== '' ? '&' . $qs : ''));
            $data = json_decode($res['body'], true);
        }

        return ['code' => $data === null ? ($res['code'] === 200 ? 502 : $res['code']) : $res['code'], 'data' => $data];
    }

    // ------------------------------------------------------------------ bantu

    private function slug(string $base, int $id, string $title): string
    {
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(rawurldecode($base) ?: url_title($title, '-', true))), '-');
        $slug = rtrim(mb_substr($slug ?: 'berita-' . $id, 0, 160), '-');
        if (db_connect()->table('posts')->where('slug', $slug)->countAllResults() > 0) {
            $slug .= '-' . $id;
        }

        return $slug;
    }

    /** Kunci pembanding gambar: nama berkas tanpa akhiran ukuran WordPress (-1024x768). */
    private function imageKey(string $url): string
    {
        return strtolower(preg_replace('/-\d+x\d+(?=\.\w+$)/', '', rawurldecode(basename((string) parse_url($url, PHP_URL_PATH)))));
    }

    private function downloadImage(string $src): ?string
    {
        $tmp = $this->download($src, Uploader::MAX_IMAGE_MB);
        if (! $tmp) {
            return null;
        }
        try {
            return $this->uploader->storeImagePath($tmp, 1600);
        } catch (\Throwable) {
            return null;
        } finally {
            @unlink($tmp);
        }
    }

    private function downloadPdf(string $href): ?int
    {
        $tmp = $this->download($href, Uploader::MAX_PDF_MB);
        if (! $tmp) {
            return null;
        }
        try {
            return $this->uploader->storePdfPath($tmp, rawurldecode(basename((string) parse_url($href, PHP_URL_PATH))));
        } catch (\Throwable) {
            return null;
        } finally {
            @unlink($tmp);
        }
    }

    /** Unduh ke berkas sementara (dibatasi ukuran). */
    private function download(string $url, int $maxMb): ?string
    {
        $tmp = tempnam(WRITEPATH . 'cache', 'imp');
        $fh  = fopen($tmp, 'wb');
        $ch  = $this->curl($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE             => $fh,
            CURLOPT_NOPROGRESS       => false,
            CURLOPT_PROGRESSFUNCTION => static fn ($c, $dlTotal, $dl) => ($dlTotal > $maxMb * 1048576 || $dl > $maxMb * 1048576) ? 1 : 0,
        ]);
        $ok   = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $last = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $ipPublic = true;
        try {
            \App\Libraries\UrlGuard::assertConnectedIpPublic($ch);
        } catch (\Throwable) {
            $ipPublic = false;
        }
        curl_close($ch);
        fclose($fh);
        if (! $ok || $code >= 400 || ! $ipPublic || ($last && ! $this->isOwnHost($last)) || filesize($tmp) === 0) {
            @unlink($tmp);

            return null;
        }

        return $tmp;
    }

    /** @return array{code:int,body:string,error:string} */
    /**
     * Tanggal dari teks bebas berbahasa Indonesia/Inggris, mis. "Friday, 13 November 2026 07:57",
     * "Jumat, 13 Nopember 2026", "13 Nov 2026". Hasil "Y-m-d H:i" atau "Y-m-d" (tanpa jam), null bila tidak ada.
     */
    public static function looseDate(string $text): ?string
    {
        $months = ['jan' => 1, 'feb' => 2, 'peb' => 2, 'mar' => 3, 'apr' => 4, 'mei' => 5, 'may' => 5, 'jun' => 6, 'jul' => 7,
            'agu' => 8, 'agt' => 8, 'aug' => 8, 'sep' => 9, 'okt' => 10, 'oct' => 10, 'nov' => 11, 'nop' => 11, 'des' => 12, 'dec' => 12];
        if (! preg_match('/\b(\d{1,2})\s+(jan|feb|peb|mar|apr|mei|may|jun|jul|agu|agt|aug|sep|okt|oct|nov|nop|des|dec)[a-z]*\.?\s+(\d{4})(?:[,\s]+(?:pukul\s+)?(\d{1,2})[:.](\d{2}))?/iu', $text, $m)) {
            return null;
        }
        $d = (int) $m[1];
        $mo = $months[strtolower($m[2])];
        $y = (int) $m[3];
        if (! checkdate($mo, $d, $y) || $y < 1990 || $y > (int) date('Y') + 1) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $y, $mo, $d) . (isset($m[4]) && $m[4] !== '' ? sprintf(' %02d:%02d', (int) $m[4], (int) $m[5]) : '');
    }

    protected function fetch(string $url): array
    {
        $ch = $this->curl($url);
        $buf = '';
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function ($c, $chunk) use (&$buf) {
            $buf .= $chunk;

            return strlen($buf) > self::MAX_PAGE_BYTES ? 0 : strlen($chunk);
        });
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err  = curl_errno($ch) ? $this->curlError($ch) : '';
        $last = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $ipErr = null;
        try {
            \App\Libraries\UrlGuard::assertConnectedIpPublic($ch);
        } catch (\Throwable $e) {
            $ipErr = $e->getMessage();
        }
        curl_close($ch);
        if ($ipErr) {
            throw new RuntimeException($ipErr);
        }
        if ($last && ! $this->isOwnHost($last)) {
            return ['code' => 400, 'body' => '', 'error' => 'dialihkan ke situs lain'];
        }
        if ($code === 0 && $err) {
            throw new RuntimeException('Website lama tidak dapat dihubungi: ' . $err);
        }

        return ['code' => $code, 'body' => $buf, 'error' => $err];
    }

    private function curl(string $url)
    {
        if (! function_exists('curl_init')) {
            throw new RuntimeException('Ekstensi PHP cURL belum aktif (php.ini: extension=curl).');
        }
        \App\Libraries\UrlGuard::assertPublic($url);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 5,
            CURLOPT_PROTOCOLS       => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT  => 15,
            CURLOPT_TIMEOUT         => 60,
            CURLOPT_USERAGENT       => 'Mozilla/5.0 (compatible; ImporWebsiteSatker/1.0)',
            CURLOPT_ENCODING        => '',
            CURLOPT_SSL_VERIFYPEER  => ! $this->insecure,
            CURLOPT_SSL_VERIFYHOST  => $this->insecure ? 0 : 2,
        ]);

        return $ch;
    }

    private function curlError($ch): string
    {
        $no = curl_errno($ch);
        if (in_array($no, [35, 51, 58, 60, 77], true)) {
            return 'sertifikat SSL tidak dapat diverifikasi (' . curl_error($ch) . '). Centang "Abaikan verifikasi sertifikat SSL" lalu coba lagi.';
        }

        return curl_error($ch);
    }

    private function isOwnHost(string $url): bool
    {
        $h = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $h !== '' && preg_replace('/^www\./', '', $h) === $this->host;
    }

    private function absolute(string $href, string $from): ?string
    {
        $href = trim($href);
        if ($href === '' || $href[0] === '#' || preg_match('/^(javascript|mailto|tel|data):/i', $href)) {
            return null;
        }
        if (preg_match('#^https?://#i', $href)) {
            return str_replace(' ', '%20', $href);
        }
        if (str_starts_with($href, '//')) {
            return 'https:' . $href;
        }
        $fp   = parse_url($from);
        $root = ($fp['scheme'] ?? 'https') . '://' . ($fp['host'] ?? $this->host);
        if ($href[0] === '/') {
            return str_replace(' ', '%20', $root . $href);
        }
        // Joomla memakai <base href> = akar situs, jadi "images/…" relatif terhadap akar, bukan folder halaman
        return str_replace(' ', '%20', $root . '/' . ltrim(preg_replace('#^(\./)+#', '', $href), '/'));
    }

    private function withQuery(string $url, string $q): string
    {
        return $url . (str_contains($url, '?') ? '&' : '?') . $q;
    }

    private function first(DOMXPath $xp, string $q, ?DOMNode $ctx = null): ?DOMNode
    {
        $r = $ctx ? $xp->query($q, $ctx) : $xp->query($q);

        return $r && $r->length ? $r->item(0) : null;
    }

    private function text(?DOMNode $n): string
    {
        return $n ? trim(preg_replace('/\s+/u', ' ', $n->textContent)) : '';
    }

    /** Buang judul yang diulang dan paragraf kosong di awal isi (umum di website lama). */
    private function dropTitleRepeat(DOMNode $body, string $title): void
    {
        $norm      = static fn (string $s) => preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower(html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $want      = $norm($title);
        $titleGone = false;
        foreach (iterator_to_array($body->childNodes) as $c) {
            if (! $c instanceof DOMElement) {
                if (trim(str_replace("\u{a0}", '', $c->textContent)) === '') {
                    continue;
                }

                return;
            }
            $hasMedia = $c->getElementsByTagName('img')->length || $c->getElementsByTagName('iframe')->length || in_array(strtolower($c->tagName), ['img', 'iframe', 'table', 'hr'], true);
            $t        = $norm($c->textContent);
            if ($t === '' && ! $hasMedia) {
                $body->removeChild($c);

                continue;
            }
            if (! $titleGone && ! $hasMedia && $t === $want) {
                $body->removeChild($c);
                $titleGone = true;

                continue;
            }

            return;
        }
    }

    private function insideTag(DOMNode $n, array $tags): bool
    {
        for ($p = $n->parentNode; $p; $p = $p->parentNode) {
            if ($p instanceof DOMElement && in_array(strtolower($p->tagName), $tags, true)) {
                return true;
            }
        }

        return false;
    }

    private function isNearTop(DOMNode $n, DOMNode $body): bool
    {
        while ($n->parentNode && $n->parentNode !== $body) {
            $n = $n->parentNode;
        }
        $i = 0;
        foreach ($body->childNodes as $c) {
            if ($c === $n) {
                return $i < 3;
            }
            if ($c instanceof DOMElement) {
                $i++;
            }
        }

        return false;
    }
}
