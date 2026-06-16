<?php

function designer_sections() {
    return [
        'works' => [
            'title' => 'Karya Saya',
            'items' => [
                ['key' => 'all', 'label' => 'Semua Karya', 'href' => 'unggahan.php?view=all#daftar-karya', 'icon' => 'zmdi-view-list'],
                ['key' => 'auction-create', 'label' => 'Buat Lelang', 'href' => 'unggahan.php?view=auction-create#formUpload', 'icon' => 'zmdi-plus-circle-o'],
                ['key' => 'active', 'label' => 'Lelang Aktif', 'href' => 'unggahan.php?view=active#daftar-karya', 'icon' => 'zmdi-time'],
            ],
        ],
        'sales' => [
            'title' => 'Penjualan',
            'items' => [
                ['key' => 'all', 'label' => 'Semua Penjualan', 'href' => 'penjualan.php?view=all', 'icon' => 'zmdi-receipt'],
                ['key' => 'processing', 'label' => 'Sedang Diproses', 'href' => 'penjualan.php?view=processing', 'icon' => 'zmdi-time-restore'],
                ['key' => 'history', 'label' => 'Riwayat', 'href' => 'penjualan.php?view=history', 'icon' => 'zmdi-time-countdown'],
            ],
        ],
        'finance' => [
            'title' => 'Keuangan',
            'items' => [
                ['key' => 'withdraw', 'label' => 'Pencairan Dana', 'href' => 'pencairan.php', 'icon' => 'zmdi-balance-wallet'],
                ['key' => 'history', 'label' => 'Riwayat Pencairan', 'href' => 'pencairan.php?view=history', 'icon' => 'zmdi-time-countdown'],
            ],
        ],
    ];
}

function designer_section_data($section) {
    $sections = designer_sections();
    return $sections[$section] ?? null;
}

function designer_section_active_label($section, $active_key) {
    $section_data = designer_section_data($section);
    if (!$section_data) return '';

    foreach ($section_data['items'] as $item) {
        if ($item['key'] === $active_key) return $item['label'];
    }

    return $section_data['items'][0]['label'] ?? '';
}

function render_designer_section_sidebar($section, $active_key) {
    $section_data = designer_section_data($section);
    if (!$section_data || empty($section_data['items'])) return;

    $active_label = designer_section_active_label($section, $active_key);
    ?>
    <details class="designer-section-mobile">
        <summary>
            <span>
                <small><?php echo htmlspecialchars($section_data['title']); ?></small>
                <?php echo htmlspecialchars($active_label); ?>
            </span>
            <i class="zmdi zmdi-chevron-down" aria-hidden="true"></i>
        </summary>
        <nav aria-label="Submenu <?php echo htmlspecialchars($section_data['title']); ?>">
            <?php foreach ($section_data['items'] as $item) { ?>
                <a href="<?php echo htmlspecialchars($item['href']); ?>" class="<?php echo $item['key'] === $active_key ? 'active' : ''; ?>">
                    <i class="zmdi <?php echo htmlspecialchars($item['icon']); ?>" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($item['label']); ?>
                </a>
            <?php } ?>
        </nav>
    </details>

    <aside class="designer-section-sidebar">
        <h2><?php echo htmlspecialchars($section_data['title']); ?></h2>
        <nav aria-label="Submenu <?php echo htmlspecialchars($section_data['title']); ?>">
            <?php foreach ($section_data['items'] as $item) { ?>
                <a href="<?php echo htmlspecialchars($item['href']); ?>" class="<?php echo $item['key'] === $active_key ? 'active' : ''; ?>">
                    <i class="zmdi <?php echo htmlspecialchars($item['icon']); ?>" aria-hidden="true"></i>
                    <span><?php echo htmlspecialchars($item['label']); ?></span>
                </a>
            <?php } ?>
        </nav>
    </aside>
    <?php
}

