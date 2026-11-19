<?php
/**
 * Render satu field form.
 * @var string $name @var array $f [label, type, rules, ...opsi] @var mixed $value @var array $errors @var array $lookups
 */
[$label, $type] = $f;
$rules    = $f[2] ?? '';
$required = str_contains($rules, 'required') && ! str_contains($rules, 'permit_empty');
$err      = $errors[$name] ?? null;
$id       = 'f_' . $name;
$invalid  = $err ? ' is-invalid' : '';
$desc     = trim(($f['help'] ?? '') !== '' ? $id . '_help' : '');
$aria     = ($desc ? ' aria-describedby="' . $desc . ($err ? ' ' . $id . '_err' : '') . '"' : ($err ? ' aria-describedby="' . $id . '_err"' : '')) . ($err ? ' aria-invalid="true"' : '');
$disabled = ! empty($lockedField) ? ' readonly' : '';
$value    = $value ?? '';
// nama input (mis. settings[limit]) bila berbeda dari id/kunci galat. Catatan: view() CI4 menyimpan variabel antar-panggilan,
// jadi pemanggil yang memakai inputName WAJIB mengirim 'inputName' => null untuk field biasa.
$inputName = $inputName ?? $name;
?>
<div class="mb-3">
    <?php if ($type === 'checkbox'): ?>
        <div class="form-check form-switch">
            <input type="checkbox" class="form-check-input<?= $invalid ?>" role="switch" id="<?= $id ?>" name="<?= $inputName ?>" value="1"<?= $value ? ' checked' : '' ?><?= $aria ?>>
            <label class="form-check-label fw-semibold" for="<?= $id ?>"><?= esc($label) ?></label>
        </div>
    <?php else: ?>
        <label for="<?= $id ?>" class="form-label fw-semibold"><?= esc($label) ?><?= $required ? ' <span class="text-danger" aria-hidden="true">*</span>' : '' ?></label>
        <?php switch ($type):
            case 'textarea': ?>
                <textarea id="<?= $id ?>" name="<?= $inputName ?>" rows="4" class="form-control<?= $invalid ?>"<?= $required ? ' required' : '' ?><?= $aria ?>><?= esc((string) $value) ?></textarea>
            <?php break; case 'richtext':
                $html = (string) $value;
                if (trim($html) !== '' && ! preg_match('/<(p|ol|ul|li|br|div|h\d|table|strong|em|a)\b/i', $html)) {
                    // teks lama (satu per baris) → HTML agar rapi di editor
                    $lines = array_values(array_filter(array_map(static fn ($l) => trim(preg_replace('/^\s*(\d+[.)]|[-•*])\s+/u', '', $l)), preg_split('/\R/', $html)), 'strlen'));
                    $html  = ($f['plainAs'] ?? '') === 'ol'
                        ? '<ol>' . implode('', array_map(static fn ($l) => '<li>' . esc($l) . '</li>', $lines)) . '</ol>'
                        : implode('', array_map(static fn ($l) => '<p>' . esc($l) . '</p>', $lines));
                } ?>
                <textarea id="<?= $id ?>" name="<?= $inputName ?>" rows="12" class="form-control<?= $invalid ?>" data-richtext<?= ! empty($f['height']) ? ' data-richtext-height="' . (int) $f['height'] . '"' : '' ?><?= $aria ?>><?= esc($html) ?></textarea>
            <?php break; case 'select':
                $opts = $f['options'] ?? ($lookups[$name] ?? []); ?>
                <select id="<?= $id ?>" name="<?= $inputName ?>" class="form-select<?= $invalid ?>"<?= $required ? ' required' : '' ?><?= $aria ?><?= $disabled ? ' disabled' : '' ?>>
                    <?php if (! isset($opts[''])): ?><option value="">— Pilih —</option><?php endif ?>
                    <?php foreach ($opts as $k => $v): ?><option value="<?= esc((string) $k, 'attr') ?>"<?= (string) $value === (string) $k ? ' selected' : '' ?>><?= esc($v) ?></option><?php endforeach ?>
                </select>
            <?php break; case 'image': ?>
                <?php if ($value): ?>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <img src="<?= media_url($value) ?>" alt="Gambar saat ini" style="max-height: 90px; max-width: 220px" class="rounded border">
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="<?= $inputName ?>__remove" value="1" id="<?= $id ?>_rm"><label class="form-check-label small" for="<?= $id ?>_rm">Hapus gambar</label></div>
                    </div>
                <?php endif ?>
                <input type="file" id="<?= $id ?>" name="<?= $inputName ?>" accept="image/jpeg,image/png,image/webp" class="form-control<?= $invalid ?>"<?= $aria ?>>
                <div class="form-text">JPG/PNG/WebP, maks. 8 MB. Otomatis dikompres ke WebP.</div>
            <?php break; case 'pdf': ?>
                <?php if ($value && ($url = file_url((int) $value))): ?>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <a href="<?= esc($url, 'attr') ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf text-danger" aria-hidden="true"></i> <?= esc(service('uploader')->find((int) $value)['original_name'] ?? 'PDF') ?></a>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="<?= $inputName ?>__remove" value="1" id="<?= $id ?>_rm"><label class="form-check-label small" for="<?= $id ?>_rm">Hapus berkas</label></div>
                    </div>
                <?php endif ?>
                <input type="file" id="<?= $id ?>" name="<?= $inputName ?>" accept="application/pdf,.pdf" class="form-control<?= $invalid ?>"<?= $aria ?>>
                <div class="form-text">PDF, maks. 20 MB.<?= $value ? ' Unggah berkas baru untuk mengganti.' : '' ?></div>
            <?php break; case 'number': ?>
                <input type="number" id="<?= $id ?>" name="<?= $inputName ?>" value="<?= esc((string) $value, 'attr') ?>" step="<?= str_contains($rules, 'decimal') ? '0.1' : '1' ?>" class="form-control<?= $invalid ?>" inputmode="<?= str_contains($rules, 'decimal') ? 'decimal' : 'numeric' ?>"<?= $required ? ' required' : '' ?><?= $aria ?>>
            <?php break; case 'year': ?>
                <input type="number" id="<?= $id ?>" name="<?= $inputName ?>" value="<?= esc((string) $value, 'attr') ?>" min="2000" max="2099" class="form-control<?= $invalid ?>" inputmode="numeric" style="max-width: 10rem"<?= $required ? ' required' : '' ?><?= $aria ?>>
            <?php break; case 'date': ?>
                <input type="date" id="<?= $id ?>" name="<?= $inputName ?>" value="<?= esc((string) $value, 'attr') ?>" class="form-control<?= $invalid ?>" style="max-width: 14rem"<?= $required ? ' required' : '' ?><?= $aria ?>>
            <?php break; case 'datetime': ?>
                <input type="datetime-local" id="<?= $id ?>" name="<?= $inputName ?>" value="<?= esc($value ? date('Y-m-d\TH:i', strtotime((string) $value)) : '', 'attr') ?>" class="form-control<?= $invalid ?>" style="max-width: 16rem"<?= $required ? ' required' : '' ?><?= $aria ?>>
            <?php break; case 'time': ?>
                <input type="time" id="<?= $id ?>" name="<?= $inputName ?>" value="<?= esc((string) $value, 'attr') ?>" class="form-control<?= $invalid ?>" style="max-width: 10rem"<?= $aria ?>>
            <?php break; case 'url': ?>
                <input type="text" id="<?= $id ?>" name="<?= $inputName ?>" value="<?= esc((string) $value, 'attr') ?>" class="form-control<?= $invalid ?>" inputmode="url" spellcheck="false" placeholder="https://… atau /halaman/internal"<?= $required ? ' required' : '' ?><?= $aria ?>>
            <?php break; case 'email': ?>
                <input type="email" id="<?= $id ?>" name="<?= $inputName ?>" value="<?= esc((string) $value, 'attr') ?>" class="form-control<?= $invalid ?>" autocomplete="off" spellcheck="false"<?= $aria ?>>
            <?php break; default: ?>
                <input type="text" id="<?= $id ?>" name="<?= $inputName ?>" value="<?= esc((string) $value, 'attr') ?>" class="form-control<?= ! empty($f['large']) ? ' form-control-lg' : '' ?><?= $invalid ?>"<?= $required ? ' required' : '' ?><?= $aria ?><?= $disabled ?><?= ! empty($f['slugSource']) ? ' data-slug-source' : '' ?>>
        <?php endswitch ?>
    <?php endif ?>
    <?php if (! empty($f['help'])): ?><div id="<?= $id ?>_help" class="form-text"><?= esc($f['help']) ?></div><?php endif ?>
    <?php if ($err): ?><div id="<?= $id ?>_err" class="invalid-feedback d-block"><?= esc($err) ?></div><?php endif ?>
</div>
