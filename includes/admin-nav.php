<?php
/**
 * Admin sidebar — labels match references/admin_* IA.
 *
 * @var string $adminActiveNav
 */

declare(strict_types=1);

$adminActiveNav = $adminActiveNav ?? 'overview';

$items = [
    'overview' => ['label' => 'Dashboard Overview', 'href' => 'admin/index.php', 'icon' => 'dashboard'],
    'properties' => ['label' => 'Manage Properties', 'href' => 'admin/properties.php', 'icon' => 'home_work'],
    'types' => ['label' => 'Property Types', 'href' => 'admin/property-types.php', 'icon' => 'category'],
    'amenities' => ['label' => 'Amenities', 'href' => 'admin/amenities.php', 'icon' => 'checklist'],
    'inquiries' => ['label' => 'Inquiries', 'href' => 'admin/inquiries.php', 'icon' => 'mail'],
    'settings' => ['label' => 'Website Settings', 'href' => 'admin/settings.php', 'icon' => 'settings'],
    'profile' => ['label' => 'Account', 'href' => 'admin/profile.php', 'icon' => 'person'],
];
?>
<aside class="admin-sidebar" aria-label="Admin">
    <nav class="admin-sidebar__nav">
        <?php foreach ($items as $key => $item): ?>
            <a class="admin-sidebar__link<?= $adminActiveNav === $key ? ' is-active' : '' ?>"
               href="<?= e(base_url($item['href'])) ?>">
                <span class="material-symbols-outlined" aria-hidden="true"><?= e($item['icon']) ?></span>
                <span><?= e(strtoupper($item['label'])) ?></span>
            </a>
        <?php endforeach; ?>
        <a class="admin-sidebar__link admin-sidebar__link--exit" href="<?= e(base_url('index.php')) ?>">
            <span class="material-symbols-outlined" aria-hidden="true">logout</span>
            <span>EXIT TO SITE</span>
        </a>
    </nav>
</aside>
