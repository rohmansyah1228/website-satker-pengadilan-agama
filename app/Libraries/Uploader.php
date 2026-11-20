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

    /** @return string path relatif dari public/, mis. uploads/images/2026/abc.webp */
    public function storeImage(UploadedFile $file, int $maxWidth = 1600): string
    {
        if (! $file->isValid()) {
            throw new RuntimeException('Unggahan gagal: ' . $file->getErrorString());
        }
        if ($file->getSize() > self::MAX_IMAGE_MB * 1048576) {
            throw new RuntimeException('Ukuran gambar maksimal ' . self::MAX_IMAGE_MB . ' MB.');
        }

        return $this->storeImagePath($file->getTempName(), $maxWidth);
    }

    /** Simpan gambar dari berkas sementara di server (unggahan atau unduhan impor). */
    public function storeImagePath(string $tmp, int $maxWidth = 1600): string
    {
        if (! is_file($tmp) || filesize($tmp) > self::MAX_IMAGE_MB * 1048576) {
            throw new RuntimeException('Ukuran gambar maksimal ' . self::MAX_IMAGE_MB . ' MB.');
        }
        $info = @getimagesize($tmp);
        // GIF disimpan apa adanya agar animasinya tetap jalan (mis. ikon CCTV); dibatasi 2 MB & 1600 px
        if ($info && $info[2] === IMAGETYPE_GIF) {
            if (filesize($tmp) > 2 * 1048576 || $info[0] > 1600 || $info[1] > 1600) {
                throw new RuntimeException('GIF maksimal 2 MB dan 1600 × 1600 piksel.');
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
        $map  = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'];
        if (! $info || ! isset($map[$info[2]])) {
            throw new RuntimeException('Gambar harus berformat JPG, PNG, WebP, atau GIF.');
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

        $rel = 'uploads/images/' . date('Y') . '/' . bin2hex(random_bytes(12)) . '.webp';
        $dir = FCPATH . dirname($rel);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        imagewebp($src, FCPATH . $rel, 80);
        imagedestroy($src);

        return $rel;
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
