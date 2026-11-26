<?php

/**
 * Pemeriksa sebelum push: memastikan commit yang akan dikirim ke GitHub adalah rilis bertanda tangan yang utuh.
 *
 *   php deploy/cek-tanda-tangan.php [commit]     (bawaan: HEAD)
 *
 * Gagal (kode keluar 1) bila:
 *  - version.sig tidak sah untuk version.json + integrity.json (kunci di app/Config/Release.php), atau
 *  - ada berkas di commit itu yang isinya berbeda / tidak tercantum / hilang dibanding integrity.json.
 *
 * Mengapa: Update Website di satker mengambil berkas dari GitHub dan menolak SEMUA berkas bila satu saja tidak cocok
 * dengan tanda tangan. Jadi setiap perubahan berkas harus dikirim sebagai rilis (deploy/rilis.php / rilis-dari-pc.bat).
 *
 * Dipasang otomatis sebagai pengait "pre-push" oleh deploy/rilis-dari-pc.bat; di komputer lain:
 *   git config core.hooksPath deploy/hooks
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/app/Libraries/Integrity.php';

use App\Libraries\Integrity;

$ref = $argv[1] ?? 'HEAD';
if (! preg_match('/^[A-Za-z0-9_.\/^~-]+$/', $ref)) {
    fwrite(STDERR, "Commit tidak valid.\n");
    exit(2);
}

/** Baca banyak berkas dari git sekaligus. @return array<string,string> */
function gitRead(string $root, string $ref, array $paths): array
{
    $p = proc_open(['git', '-C', $root, 'cat-file', '--batch'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (! is_resource($p)) {
        fwrite(STDERR, "Gagal menjalankan git.\n");
        exit(2);
    }
    $out = [];
    foreach ($paths as $path) {
        fwrite($pipes[0], $ref . ':' . $path . "\n");
        fflush($pipes[0]);
        $head = (string) fgets($pipes[1]);
        if (! preg_match('/^\S+ blob (\d+)$/', trim($head), $m)) {
            continue; // tidak ada
        }
        $len = (int) $m[1];
        $buf = '';
        while (strlen($buf) < $len && ! feof($pipes[1])) {
            $buf .= fread($pipes[1], $len - strlen($buf));
        }
        fgets($pipes[1]); // baris baru penutup
        $out[$path] = $buf;
    }
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($p);

    return $out;
}

exec('git -C ' . escapeshellarg($root) . ' ls-tree -r -z --name-only ' . escapeshellarg($ref), $lines, $rc);
if ($rc !== 0) {
    fwrite(STDERR, "Gagal membaca isi commit $ref.\n");
    exit(2);
}
$all = array_values(array_filter(explode("\0", implode("\n", $lines)), static fn ($p) => $p !== ''));

$meta = gitRead($root, $ref, Integrity::META);
$cfg  = gitRead($root, $ref, ['app/Config/Release.php'])['app/Config/Release.php'] ?? '';
preg_match_all('/-----BEGIN PUBLIC KEY-----.*?-----END PUBLIC KEY-----/s', $cfg, $k);

$bad = [];
try {
    $signed = Integrity::verified($meta['version.json'] ?? '', $meta['integrity.json'] ?? '', $meta['version.sig'] ?? '', $k[0]);
} catch (Throwable $e) {
    fwrite(STDERR, "\nDITOLAK: tanda tangan rilis tidak sah ({$e->getMessage()}).\n");
    $signed = null;
}

if ($signed) {
    // berkas yang ikut ditandatangani (aturan sama dengan deploy/tanda-tangan.php)
    $want = array_values(array_filter($all, static fn ($p) => ! in_array($p, Integrity::META, true) && ! str_starts_with($p, '.github/')));
    $body = gitRead($root, $ref, $want);
    foreach ($want as $p) {
        if (! isset($signed['files'][$p])) {
            $bad[] = "  + $p (belum tercantum di integrity.json)";
        } elseif (! isset($body[$p]) || Integrity::hash($body[$p]) !== $signed['files'][$p]) {
            $bad[] = "  ~ $p (isi berbeda dengan yang ditandatangani)";
        }
    }
    foreach (array_diff(array_keys($signed['files']), $want) as $p) {
        $bad[] = "  - $p (tercantum di integrity.json tetapi tidak ada)";
    }
}

if ($signed && ! $bad) {
    printf("Rilis bertanda tangan utuh: versi %s (build %d), %d berkas.\n", $signed['name'], $signed['build'], count($signed['files']));
    exit(0);
}
if ($bad) {
    fwrite(STDERR, "\nDITOLAK: berkas berikut berubah sesudah rilis terakhir ditandatangani:\n" . implode("\n", array_slice($bad, 0, 30))
        . (count($bad) > 30 ? "\n  … dan " . (count($bad) - 30) . " lainnya" : '') . "\n");
}
fwrite(STDERR, "\nKirim perubahan ini sebagai rilis baru (deploy\\rilis-dari-pc.bat atau php deploy/rilis.php \"catatan\"),\n"
    . "jangan push langsung — Update Website di satker akan gagal bila isi GitHub tidak cocok dengan tanda tangan.\n\n");
exit(1);
