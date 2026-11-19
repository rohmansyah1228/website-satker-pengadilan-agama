<?php foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $k => $cls): ?>
    <?php if ($msg = session()->getFlashdata($k)): ?>
        <div class="alert alert-<?= $cls ?> alert-dismissible fade show" role="<?= $k === 'success' ? 'status' : 'alert' ?>">
            <?= $k === 'error' && str_contains($msg, '<br>') ? $msg : esc($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    <?php endif ?>
<?php endforeach ?>
