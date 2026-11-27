<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

/**
 * Penyimpanan unggahan yang aman.
 * - PDF: disimpan di writable/uploads (di luar public), disajikan lewat /berkas/{uuid}
 * - Gambar: divalidasi, di-encode ulang ke WebP (kompresi + membuang payload tersembunyi)
 */
class Uploader
{
    public const MAX_PDF_MB   = 20;
    public const MAX_IMAGE_MB = 8;

    /** GIF bergerak disimpan apa adanya (tidak dikompres), jadi batasnya terpisah */
    public const MAX_GIF_MB = 100;

    public const MAX_GIF_PX = 8000;

    private array $cache = [];

    public function find(int $id): ?array
    {
        if (! array_key_exists($id, $this->cache)) {
            $this->cache[$id] = db_connect()->table('files')->where('id', $id)->get()->getRowArray();
        }

        return $this->cache[$id];
    }

    /** @return int id baris pada tabel files */
    public function storePdf(UploadedFile $file): int
    {
        if (! $file->isValid()) {
            throw new RuntimeException('Unggahan gagal: ' . $file->getErrorString());
        }
        if ($file->getSize() > self::MAX_PDF_MB * 1048576) {
            throw new RuntimeException('Ukuran PDF maksimal ' . self::MAX_PDF_MB . ' MB.');
        }
        if (strtolower($file->getClientExtension()) !== 'pdf' || $file->getMimeType() !== 'application/pdf') {
            throw new RuntimeException('Berkas harus berformat PDF.');
        }

        return $this->storePdfPath($file->getTempName(), $file->getClientName(), true);
    }

    /** Berkas unduhan yang diizinkan (selain PDF): ekstensi => [MIME, jenis tanda tangan berkas]. */
    public const DOC_TYPES = [
        'doc'  => ['application/msword', 'ole'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'ooxml'],
        'xls'  => ['application/vnd.ms-excel', 'ole'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'ooxml'],
        'ppt'  => ['application/vnd.ms-powerpoint', 'ole'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'ooxml'],
        'odt'  => ['application/vnd.oasis.opendocument.text', 'odf'],
        'ods'  => ['application/vnd.oasis.opendocument.spreadsheet', 'odf'],
        'rtf'  => ['application/rtf', 'rtf'],
    ];

    /**
     * Simpan berkas unduhan: PDF, Word, Excel, PowerPoint, OpenDocument, RTF.
     * Isi berkas diperiksa (bukan hanya nama/ekstensi); dokumen Office ber-makro (VBA) ditolak.
     * Disimpan di writable/uploads (tidak bisa dijalankan dari web), disajikan lewat /berkas/{uuid}.
     * @return int id baris pada tabel files
     */
    public function storeDocument(UploadedFile $file): int
    {
        if (! $file->isValid()) {
            throw new RuntimeException('Unggahan gagal: ' . $file->getErrorString());
        }
        $ext = strtolower((string) pathinfo((string) $file->getClientName(), PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            return $this->storePdf($file);
        }
        if (! isset(self::DOC_TYPES[$ext])) {
            throw new RuntimeException('Format berkas tidak didukung. Gunakan PDF, Word (doc/docx), Excel (xls/xlsx), PowerPoint (ppt/pptx), ODT/ODS, atau RTF.');
        }
        if ($file->getSize() > self::MAX_PDF_MB * 1048576) {
            throw new RuntimeException('Ukuran berkas maksimal ' . self::MAX_PDF_MB . ' MB.');
        }
        [$mime, $kind] = self::DOC_TYPES[$ext];
        $tmp = $file->getTempName();
        $this->checkDocument($tmp, $ext, $kind);

        $uuid = bin2hex(random_bytes(16));
        $rel  = 'uploads/docs/' . date('Y') . '/' . $uuid . '.' . $ext;
        $dir  = WRITEPATH . dirname($rel);
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        if (! (@rename($tmp, WRITEPATH . $rel) || @copy($tmp, WRITEPATH . $rel))) {
            throw new RuntimeException('Berkas tidak dapat disimpan.');
        }
        $name = preg_replace('/[^\w\s.\-()]/u', '', (string) $file->getClientName()) ?: ('berkas.' . $ext);
        db_connect()->table('files')->insert([
            'uuid' => $uuid, 'path' => $rel, 'original_name' => mb_substr($name, 0, 250),
            'mime' => $mime, 'size' => (int) filesize(WRITEPATH . $rel), 'created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) db_connect()->insertID();
    }

    /** Pastikan isi berkas benar-benar dokumen sesuai ekstensinya. */
    private function checkDocument(string $tmp, string $ext, string $kind): void
    {
        $head = (string) @file_get_contents($tmp, false, null, 0, 8);
        $bad  = 'Isi berkas tidak sesuai format .' . $ext . '. Simpan ulang dari Word/Excel lalu unggah kembali.';
        if ($kind === 'ole') {
            if ($head !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
                throw new RuntimeException($bad);
            }

            return;
        }
        if ($kind === 'rtf') {
            if (! str_starts_with($head, '{\\rtf')) {
                throw new RuntimeException($bad);
            }

            return;
        }
        // docx/xlsx/pptx/odt/ods = arsip ZIP
        if (! str_starts_with($head, "PK\x03\x04")) {
            throw new RuntimeException($bad);
        }
        if (! class_exists(\ZipArchive::class)) {
            return; // ekstensi zip tidak tersedia: cukup tanda tangan berkas
        }
        $z = new \ZipArchive();
        if ($z->open($tmp) !== true) {
            throw new RuntimeException($bad);
        }
        try {
            if ($kind === 'ooxml') {
                if ($z->locateName('[Content_Types].xml') === false) {
                    throw new RuntimeException($bad);
                }
                for ($i = 0; $i < $z->numFiles; $i++) {
                    if (preg_match('/vbaProject\.bin$|\.(exe|dll|bat|cmd|js|vbs|ps1|scr)$/i', (string) $z->getNameIndex($i))) {
                        throw new RuntimeException('Dokumen memuat makro/program tersisip dan tidak boleh diunggah. Simpan sebagai .' . $ext . ' biasa (tanpa makro).');
                    }
                }
            } else {
                $mt = (string) $z->getFromName('mimetype');
                if ($mt !== self::DOC_TYPES[$ext][0]) {
                    throw new RuntimeException($bad);
                }
            }
        } finally {
            $z->close();
        }
    }

    /**
     * Simpan PDF dari berkas sementara di server (unggahan atau unduhan impor).
     * @return int id baris pada tabel files
     */
    public function storePdfPath(string $tmp, string $originalName, bool $move = false): int
    {
        if (! is_file($tmp) || filesize($tmp) > self::MAX_PDF_MB * 1048576) {
            throw new RuntimeException('Ukuran PDF maksimal ' . self::MAX_PDF_MB . ' MB.');
        }
        $fh  = fopen($tmp, 'rb');
        $sig = $fh ? fread($fh, 5) : '';
        if ($fh) {
            fclose($fh);
        }
        if ($sig !== '%PDF-') {
            throw new RuntimeException('Berkas harus berformat PDF.');
        }

        $uuid = bin2hex(random_bytes(16));
        $rel  = 'uploads/docs/' . date('Y') . '/' . $uuid . '.pdf';
        $dir  = WRITEPATH . dirname($rel);
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        if (! ($move ? @rename($tmp, WRITEPATH . $rel) || @copy($tmp, WRITEPATH . $rel) : @copy($tmp, WRITEPATH . $rel))) {
            throw new RuntimeException('PDF tidak dapat disimpan.');
        }

        $name = preg_replace('/[^\w\s.\-()]/u', '', $originalName) ?: 'dokumen.pdf';
        db_connect()->table('files')->insert([
            'uuid' => $uuid, 'path' => $rel, 'original_name' => mb_substr($name, 0, 250),
            'mime' => 'application/pdf', 'size' => (int) filesize(WRITEPATH . $rel), 'created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) db_connect()->insertID();
    }

    /**
     * Uraikan struktur GIF dan kembalikan gabungan bagian yang bisa memuat teks: header, isi semua blok ekstensi
     * (komentar, aplikasi, teks, kontrol grafis) dan data apa pun setelah penanda akhir (0x3B).
     * Data piksel (LZW) dilewati. null = struktur tidak sah.
     */
    public static function gifTextParts(string $b): ?string
    {
        $len = strlen($b);
        if ($len < 13 || (substr($b, 0, 6) !== 'GIF87a' && substr($b, 0, 6) !== 'GIF89a')) {
            return null;
        }
        $out   = substr($b, 0, 13);
        $flags = ord($b[10]);
        $pos   = 13 + (($flags & 0x80) ? 3 * (2 << ($flags & 0x07)) : 0); // tabel warna global
        // lompati rangkaian sub-blok; $keep = simpan isinya untuk diperiksa
        $subBlocks = static function (bool $keep) use ($b, $len, &$pos, &$out): bool {
            while ($pos < $len) {
                $n = ord($b[$pos++]);
                if ($n === 0) {
                    return true;
                }
                if ($pos + $n > $len) {
                    return false;
                }
                if ($keep) {
                    $out .= substr($b, $pos, $n);
                }
                $pos += $n;
            }

            return false;
        };
        while ($pos < $len) {
            $c = ord($b[$pos++]);
            if ($c === 0x3B) { // akhir gambar: sisa berkas (seharusnya kosong) ikut diperiksa
                return $out . "\n" . substr($b, $pos);
            }
            if ($c === 0x21) { // blok ekstensi
                if ($pos >= $len) {
                    return null;
                }
                $pos++;
                $out .= "\n";
                if (! $subBlocks(true)) {
                    return null;
                }
            } elseif ($c === 0x2C) { // deskriptor gambar + tabel warna lokal + data LZW
                if ($pos + 9 > $len) {
                    return null;
                }
                $f = ord($b[$pos + 8]);
                $pos += 9 + (($f & 0x80) ? 3 * (2 << ($f & 0x07)) : 0);
                $pos++; // ukuran kode LZW minimum
                if (! $subBlocks(false)) {
                    return null;
                }
            } else {
                return null;
            }
        }

        return $out; // berakhir rapi tanpa penanda akhir (masih ditampilkan peramban)
    }

    /** @return string path relatif dari public/, mis. uploads/images/2026/abc.webp (atau .jpg/.png bila server tanpa WebP) */
    public function storeImage(UploadedFile $file, int $maxWidth = 1600): string
    {
        if (! $file->isValid()) {
            throw new RuntimeException('Unggahan gagal: ' . $file->getErrorString());
        }
        if ($file->getSize() > max(self::MAX_IMAGE_MB, self::MAX_GIF_MB) * 1048576) {
            throw new RuntimeException('Ukuran gambar maksimal ' . self::MAX_IMAGE_MB . ' MB (GIF ' . self::MAX_GIF_MB . ' MB).');
        }

        return $this->storeImagePath($file->getTempName(), $maxWidth);
    }

    /** Simpan gambar dari berkas sementara di server (unggahan atau unduhan impor). */
    public function storeImagePath(string $tmp, int $maxWidth = 1600): string
    {
        if (! is_file($tmp) || filesize($tmp) > max(self::MAX_IMAGE_MB, self::MAX_GIF_MB) * 1048576) {
            throw new RuntimeException('Ukuran gambar maksimal ' . self::MAX_IMAGE_MB . ' MB (GIF ' . self::MAX_GIF_MB . ' MB).');
        }
        $info = @getimagesize($tmp);
        // GIF disimpan apa adanya agar animasinya tetap jalan (mis. ikon CCTV, banner bergerak)
        if ($info && $info[2] === IMAGETYPE_GIF) {
            if (filesize($tmp) > self::MAX_GIF_MB * 1048576 || $info[0] > self::MAX_GIF_PX || $info[1] > self::MAX_GIF_PX) {
                throw new RuntimeException('GIF maksimal ' . self::MAX_GIF_MB . ' MB dan ' . self::MAX_GIF_PX . ' × ' . self::MAX_GIF_PX . ' piksel.');
            }
            // GIF besar dibaca utuh untuk diperiksa: pastikan memori PHP cukup (tidak pernah diturunkan)
            $need = (int) (filesize($tmp) * 2 + 64 * 1048576);
            $lim  = trim((string) ini_get('memory_limit'));
            $unit = ['g' => 1073741824, 'm' => 1048576, 'k' => 1024][strtolower(substr($lim, -1))] ?? 1;
            if ((int) $lim > 0 && (int) $lim * $unit < $need) {
                @ini_set('memory_limit', (string) ceil($need / 1048576) . 'M');
            }
            // tolak GIF yang disisipi kode (polyglot webshell). Yang diperiksa hanya bagian yang bisa memuat teks
            // (blok komentar/aplikasi/teks & data setelah akhir gambar), bukan data piksel terkompresi yang acak
            // sehingga bisa "kebetulan" berisi <% atau <? (salah tolak pada GIF yang sah).
            $scan = self::gifTextParts((string) @file_get_contents($tmp));
            if ($scan === null) {
                throw new RuntimeException('Berkas GIF rusak atau tidak lengkap. Simpan ulang GIF lalu unggah kembali.');
            }
            if (preg_match('/<\?php|<\?=|<\?\s|<script\b|<%|<html\b|<iframe\b|javascript:/i', $scan)) {
                throw new RuntimeException('Berkas GIF mengandung kode yang tidak diperbolehkan.');
            }
            $rel = 'uploads/images/' . date('Y') . '/' . bin2hex(random_bytes(12)) . '.gif';
            if (! is_dir(FCPATH . dirname($rel))) {
                mkdir(FCPATH . dirname($rel), 0755, true);
            }
            if (! @copy($tmp, FCPATH . $rel)) {
                throw new RuntimeException('Gambar tidak dapat disimpan.');
            }

            return $rel;
        }
        if (filesize($tmp) > self::MAX_IMAGE_MB * 1048576) {
            throw new RuntimeException('Gambar JPG/PNG/WebP maksimal ' . self::MAX_IMAGE_MB . ' MB.');
        }
        $map  = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'];
        if (! $info || ! isset($map[$info[2]])) {
            throw new RuntimeException('Gambar harus berformat JPG, PNG, WebP, atau GIF.');
        }
        if (! function_exists($map[$info[2]])) {
            throw new RuntimeException('Server website belum mendukung gambar WebP. Unggah gambar JPG atau PNG.');
        }
        $src = @$map[$info[2]]($tmp);
        if (! $src) {
            throw new RuntimeException('Gambar tidak dapat dibaca.');
        }
        [$w, $h] = [$info[0], $info[1]];
        if ($w > $maxWidth) {
            $nh  = (int) round($h * $maxWidth / $w);
            $dst = imagecreatetruecolor($maxWidth, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxWidth, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst;
        } else {
            imagepalettetotruecolor($src);
            imagesavealpha($src, true);
        }

        // WebP bila GD server mendukungnya; bila tidak: PNG untuk gambar PNG (menjaga transparansi), selain itu JPG
        $ext = self::webpSupported() ? 'webp' : ($info[2] === IMAGETYPE_PNG ? 'png' : 'jpg');
        $rel = 'uploads/images/' . date('Y') . '/' . bin2hex(random_bytes(12)) . '.' . $ext;
        $dir = FCPATH . dirname($rel);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $ok = match ($ext) {
            'webp'  => imagewebp($src, FCPATH . $rel, 80),
            'png'   => imagepng($src, FCPATH . $rel, 6),
            default => imagejpeg($src, FCPATH . $rel, 82),
        };
        imagedestroy($src);
        if (! $ok) {
            throw new RuntimeException('Gambar tidak dapat disimpan.');
        }

        return $rel;
    }

    /** Untuk pengujian: paksa anggap WebP (tidak) tersedia. */
    public static ?bool $forceWebp = null;

    /** GD server dapat menulis WebP? (sebagian hosting memasang GD tanpa WebP) */
    public static function webpSupported(): bool
    {
        if (self::$forceWebp !== null) {
            return self::$forceWebp;
        }

        return function_exists('imagewebp') && ! empty((function_exists('gd_info') ? gd_info() : [])['WebP Support']);
    }

    public function deleteImage(?string $rel): void
    {
        if ($rel && str_starts_with($rel, 'uploads/images/') && ! str_contains($rel, '..') && ! str_contains($rel, '/default/')) {
            @unlink(FCPATH . $rel);
        }
    }

    public function deleteFile(?int $id): void
    {
        if (! $id || ! ($f = $this->find($id))) {
            return;
        }
        if (! str_contains($f['path'], '..')) {
            @unlink(WRITEPATH . $f['path']);
        }
        db_connect()->table('files')->where('id', $id)->delete();
        unset($this->cache[$id]);
    }
}
