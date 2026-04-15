<?php

if (!function_exists('rayhanRPAdminMenuItems')) {
    function rayhanRPAdminMenuItems()
    {
        return [
            'dashboard' => ['label' => 'Dashboard', 'href' => 'adminWeb_rayhanRP.php'],
            'siswa' => ['label' => 'Siswa', 'href' => 'siswa_rayhanRP.php'],
            'grup' => ['label' => 'Grup', 'href' => 'grup_rayhanRP.php'],
            'jadwal' => ['label' => 'Jadwal', 'href' => 'jadwal_rayhanRP.php'],
            'tugas' => ['label' => 'Tugas', 'href' => 'tugas_rayhanRP.php'],
            'notifikasi' => ['label' => 'Notifikasi', 'href' => 'notifikasi_rayhanRP.php'],
            'riwayat' => ['label' => 'Riwayat', 'href' => 'riwayat_notifikasi_rayhanRP.php'],
            'import_excel' => ['label' => 'Import Excel', 'href' => 'import_excel_rayhanRP.php'],
        ];
    }
}

if (!function_exists('rayhanRPAdminLayoutEscape')) {
    function rayhanRPAdminLayoutEscape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('rayhanRPAdminThemeHref')) {
    function rayhanRPAdminThemeHref()
    {
        $rayhanRPThemePath = __DIR__ . '/../assets/admin_theme_rayhanRP.css';
        $rayhanRPVersion = is_file($rayhanRPThemePath) ? (string)filemtime($rayhanRPThemePath) : '1';

        return 'assets/admin_theme_rayhanRP.css?v=' . rawurlencode($rayhanRPVersion);
    }
}

if (!function_exists('rayhanRPRenderAdminLayoutStart')) {
    function rayhanRPRenderAdminLayoutStart(array $rayhanRPOptions)
    {
        $rayhanRPTitle = (string)($rayhanRPOptions['title'] ?? 'Admin');
        $rayhanRPSubtitle = (string)($rayhanRPOptions['subtitle'] ?? '');
        $rayhanRPPageKey = (string)($rayhanRPOptions['page_key'] ?? '');
        $rayhanRPAdmin = (array)($rayhanRPOptions['admin'] ?? []);
        $rayhanRPTopbarActions = (string)($rayhanRPOptions['topbar_actions'] ?? '');
        $rayhanRPExtraHead = (string)($rayhanRPOptions['extra_head'] ?? '');
        $rayhanRPBodyClass = trim((string)($rayhanRPOptions['body_class'] ?? 'admin-app'));

        $rayhanRPAdminLabel = (string)($rayhanRPAdmin['label'] ?? '');
        $rayhanRPAdminNisNip = (string)($rayhanRPAdmin['nis_nip'] ?? '');
        $rayhanRPAdminRole = strtoupper((string)($rayhanRPAdmin['role'] ?? ''));
        $rayhanRPSidebarLabel = $rayhanRPAdminLabel !== '' ? $rayhanRPAdminLabel : $rayhanRPAdminNisNip;
        $rayhanRPSidebarAvatar = strtoupper(substr($rayhanRPSidebarLabel !== '' ? $rayhanRPSidebarLabel : 'A', 0, 1));
        $rayhanRPMenuItems = rayhanRPAdminMenuItems();

        echo '<!DOCTYPE html>';
        echo '<html lang="id">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>' . rayhanRPAdminLayoutEscape($rayhanRPTitle) . ' - Bot SiRey</title>';
        echo '<link rel="stylesheet" href="' . rayhanRPAdminLayoutEscape(rayhanRPAdminThemeHref()) . '">';
        if ($rayhanRPExtraHead !== '') {
            echo $rayhanRPExtraHead;
        }
        echo '</head>';
        echo '<body class="' . rayhanRPAdminLayoutEscape($rayhanRPBodyClass) . '">';
        echo '<div class="admin-frame">';
        echo '<aside class="sidebar">';
        echo '<div class="brand">';
        echo '<h1>Bot SiRey Admin</h1>';
        echo '<p>Panel monitoring dan pengelolaan sistem.</p>';
        echo '</div>';
        echo '<div class="sidebar-profile">';
        echo '<div class="sidebar-avatar">' . rayhanRPAdminLayoutEscape($rayhanRPSidebarAvatar) . '</div>';
        echo '<div>';
        echo '<strong>' . rayhanRPAdminLayoutEscape($rayhanRPSidebarLabel) . '</strong>';
        echo '<span>' . rayhanRPAdminLayoutEscape($rayhanRPAdminRole) . ' | ' . rayhanRPAdminLayoutEscape($rayhanRPAdminNisNip) . '</span>';
        echo '</div>';
        echo '</div>';
        echo '<ul class="menu">';
        foreach ($rayhanRPMenuItems as $rayhanRPKey => $rayhanRPItem) {
            $rayhanRPActiveClass = $rayhanRPPageKey === $rayhanRPKey ? ' class="active"' : '';
            echo '<li><a' . $rayhanRPActiveClass . ' href="' . rayhanRPAdminLayoutEscape((string)$rayhanRPItem['href']) . '">' . rayhanRPAdminLayoutEscape((string)$rayhanRPItem['label']) . '</a></li>';
        }
        echo '</ul>';
        echo '<div class="sidebar-footer">';
        echo '<a class="btn secondary sidebar-logout" href="adminWeb_rayhanRP.php?logout=1">Logout</a>';
        echo '</div>';
        echo '</aside>';
        echo '<main class="main">';
        echo '<div class="container admin-content">';
        echo '<section class="topbar">';
        echo '<div>';
        echo '<h1 class="title">' . rayhanRPAdminLayoutEscape($rayhanRPTitle) . '</h1>';
        if ($rayhanRPSubtitle !== '') {
            echo '<p class="subtitle">' . $rayhanRPSubtitle . '</p>';
        }
        echo '</div>';
        if ($rayhanRPTopbarActions !== '') {
            echo '<div class="topbar-tools">' . $rayhanRPTopbarActions . '</div>';
        }
        echo '</section>';
    }
}

if (!function_exists('rayhanRPRenderAdminLayoutEnd')) {
    function rayhanRPRenderAdminLayoutEnd()
    {
        echo '</div>';
        echo '</main>';
        echo '</div>';
        echo '</body>';
        echo '</html>';
    }
}
