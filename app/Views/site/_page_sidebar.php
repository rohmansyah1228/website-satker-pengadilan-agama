        <?php if (! empty($sidebar)): ?>
        <?php
        $sideItems = static function (array $items) use (&$sideItems, $activeHref): string {
            $out = '';
            foreach ($items as $s) {
                if (($s['type'] ?? '') === 'none') {
                    $out .= '<div class="sidebar-group">' . esc($s['label']) . '</div>';
                } else {
                    $active = rtrim($s['href'], '/') === rtrim($activeHref, '/');
                    $out .= '<a href="' . esc($s['href'], 'attr') . '" class="nav-link sidebar-nav-link fw-semibold py-2 px-3' . ($active ? ' active' : '') . '"'
                        . ($active ? ' aria-current="page"' : '')
                        . (($s['type'] ?? '') === 'url' || ! empty($s['new_tab']) ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . esc($s['label']) . '</a>';
                }
                if (! empty($s['children'])) {
                    $out .= '<div class="nav flex-column gap-1 sidebar-sub">' . $sideItems($s['children']) . '</div>';
                }
            }

            return $out;
        };
        ?>
        <aside class="col-lg-3 order-2 order-lg-1" aria-label="Sub-menu <?= esc($sectionLabel, 'attr') ?>">
            <div class="bg-white rounded-3 shadow-sm p-3 position-sticky" style="top: 7.5rem;">
                <nav class="nav flex-column gap-1">
                    <?= $sideItems($sidebar) ?>
                </nav>
            </div>
        </aside>
        <?php endif ?>
