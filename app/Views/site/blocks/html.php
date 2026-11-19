<?php $html = (string) ($b['settings']['content'] ?? ''); if (trim(strip_tags($html)) === '') return; ?>
<div class="prose<?= $onDark ? ' text-white' : '' ?><?= $nested ? '' : ' mx-auto' ?>" style="<?= $nested ? '' : 'max-width: 52rem;' ?>"><?= $html /* dibersihkan HTMLPurifier saat disimpan */ ?></div>
