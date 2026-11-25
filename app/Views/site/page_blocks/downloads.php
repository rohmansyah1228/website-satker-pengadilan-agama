<?php
use App\Libraries\PageBlocks;

/**
 * Daftar Unduhan: formulir / blanko / contoh berkas yang dapat diunduh pengunjung.
 * Berkas unggahan disajikan lewat /berkas/{uuid} (PDF dibuka, berkas Office langsung diunduh).
 * @var array $b ['heading','search','items'=>[['name','note','file','url']]]
 */
$uuids = array_values(array_filter(array_column($b['items'], 'file')));
$files = [];
if ($uuids) {
    foreach (db_connect()->table('files')->select('uuid, path, size, downloads')->whereIn('uuid', $uuids)->get()->getResultArray() as $f) {
        $files[$f['uuid']] = $f;
    }
}
$kinds = [
    'pdf'  => ['PDF', 'bi-file-earmark-pdf', 'is-pdf'],
    'doc'  => ['Word', 'bi-file-earmark-word', 'is-word'], 'docx' => ['Word', 'bi-file-earmark-word', 'is-word'], 'odt' => ['ODT', 'bi-file-earmark-word', 'is-word'], 'rtf' => ['RTF', 'bi-file-earmark-text', 'is-word'],
    'xls'  => ['Excel', 'bi-file-earmark-excel', 'is-excel'], 'xlsx' => ['Excel', 'bi-file-earmark-excel', 'is-excel'], 'ods' => ['ODS', 'bi-file-earmark-excel', 'is-excel'],
    'ppt'  => ['PowerPoint', 'bi-file-earmark-ppt', 'is-ppt'], 'pptx' => ['PowerPoint', 'bi-file-earmark-ppt', 'is-ppt'],
];
$size = static function (int $s): string {
    return $s >= 1048576 ? number_format($s / 1048576, 1, ',', '.') . ' MB' : max(1, (int) round($s / 1024)) . ' KB';
};
$id     = 'dl-' . substr(md5(json_encode($b['items'])), 0, 8);
$search = ! empty($b['search']) && count($b['items']) > 1;
?>
<section class="page-block dl-list mb-4"<?= $b['heading'] !== '' ? ' aria-labelledby="' . $id . '-h"' : ' aria-label="Daftar unduhan"' ?>>
    <?php if ($b['heading'] !== '' || $search): ?>
        <div class="dl-list__head">
            <?php if ($b['heading'] !== ''): ?><h2 id="<?= $id ?>-h" class="dl-list__title"><i class="bi bi-folder2-open" aria-hidden="true"></i> <?= esc($b['heading']) ?></h2><?php endif ?>
            <?php if ($search): ?>
                <div class="dl-list__search">
                    <label for="<?= $id ?>-q" class="visually-hidden">Cari berkas</label>
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="<?= $id ?>-q" class="form-control form-control-sm" placeholder="Cari berkas…" data-dl-filter="<?= $id ?>" autocomplete="off">
                </div>
            <?php endif ?>
        </div>
    <?php endif ?>
    <div class="table-responsive">
        <table class="table dl-table align-middle mb-0" id="<?= $id ?>">
            <thead><tr><th scope="col" class="dl-no">No</th><th scope="col">Nama Berkas</th><th scope="col" class="text-end">Unduh</th></tr></thead>
            <tbody>
            <?php foreach ($b['items'] as $i => $it):
                $f    = $it['file'] !== '' ? ($files[$it['file']] ?? null) : null;
                $href = $f ? site_url('berkas/' . $f['uuid']) : ($it['url'] !== '' ? PageBlocks::href($it['url']) : '');
                $ext  = $f ? strtolower(pathinfo($f['path'], PATHINFO_EXTENSION)) : (preg_match('/\.(\w{2,4})(?:$|\?)/', (string) parse_url($it['url'], PHP_URL_PATH), $m) ? strtolower($m[1]) : '');
                $host = $f ? '' : strtolower((string) parse_url($it['url'], PHP_URL_HOST));
                $k    = $kinds[$ext] ?? match (true) {
                    (bool) $f => ['Berkas', 'bi-file-earmark', ''],
                    $it['url'] === '' => ['Belum tersedia', 'bi-file-earmark', ''],
                    in_array($host, ['drive.google.com', 'docs.google.com'], true) => ['Google Drive', 'bi-google', 'is-drive'],
                    default => ['Tautan', 'bi-box-arrow-up-right', 'is-link'],
                };
                $pdf  = $ext === 'pdf'; ?>
                <tr data-dl-row>
                    <td class="dl-no"><?= $i + 1 ?></td>
                    <td>
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi <?= $k[1] ?> dl-ico <?= $k[2] ?>" aria-hidden="true"></i>
                            <div>
                                <div class="dl-name"><?= esc($it['name']) ?></div>
                                <div class="dl-meta">
                                    <span class="dl-type <?= $k[2] ?>"><?= esc($k[0]) ?></span>
                                    <?php if ($f): ?> · <?= $size((int) $f['size']) ?><?php endif ?>
                                    <?php if ($it['note'] !== ''): ?> · <?= esc($it['note']) ?><?php endif ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if ($href !== ''): ?>
                            <a href="<?= esc(safe_href($href), 'attr') ?>" class="btn btn-sm btn-pa-primary dl-btn"<?= $f && ! $pdf ? ' download' : ' target="_blank" rel="noopener"' ?> aria-label="<?= $f && ! $pdf ? 'Unduh' : 'Buka' ?> <?= esc($it['name'], 'attr') ?>">
                                <i class="bi <?= $f && ! $pdf ? 'bi-download' : ($pdf ? 'bi-eye' : 'bi-box-arrow-up-right') ?>" aria-hidden="true"></i><span class="dl-btn-text"> <?= $f && ! $pdf ? 'Unduh' : ($pdf ? 'Lihat / Unduh' : 'Buka') ?></span>
                            </a>
                        <?php else: ?>
                            <span class="small text-secondary">Segera<span class="d-none d-sm-inline"> tersedia</span></span>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
            <?php if ($search): ?><tr class="dl-empty" hidden><td colspan="3" class="text-center text-secondary small py-4">Tidak ada berkas yang cocok.</td></tr><?php endif ?>
            </tbody>
        </table>
    </div>
</section>
