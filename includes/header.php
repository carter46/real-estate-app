<?php
/**
 * Shared public header — same menu on every public page.
 *
 * @var string|null $pageTitle
 * @var string|null $activeNav home|properties|agents|about|contact|faq
 */

declare(strict_types=1);

$activeNav = $activeNav ?? '';
require __DIR__ . '/stitch-head.php';

$navItems = [
    'properties' => ['label' => 'Properties', 'href' => base_url('properties.php')],
    'agents' => ['label' => 'Agents', 'href' => base_url('agents.php')],
    'about' => ['label' => 'About Us', 'href' => base_url('about.php')],
    'contact' => ['label' => 'Contact', 'href' => base_url('contact.php')],
    'faq' => ['label' => 'FAQ', 'href' => base_url('faq.php')],
];
?>
<header class="fixed top-0 w-full z-50 bg-surface/90 backdrop-blur-md shadow-[0_1px_8px_rgba(55,5,24,0.04)]">
  <div class="h-20 max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop flex items-center justify-between">
    <div class="flex items-center gap-6">
      <a href="<?= e(base_url('index.php')) ?>" class="flex items-center gap-3 no-underline">
        <img src="<?= e(site_logo_url()) ?>" alt="<?= e(site_name()) ?>" class="h-10 w-auto object-contain"/>
      </a>
      <form action="<?= e(base_url('properties.php')) ?>" method="get" class="hidden lg:flex items-center bg-surface-container px-4 py-2 rounded-full border border-outline-variant/30 text-on-surface-variant">
        <span class="material-symbols-outlined text-[20px] mr-2">search</span>
        <input class="bg-transparent border-none outline-none text-label-sm w-48 placeholder:text-on-surface-variant/50 font-label-sm" placeholder="Search properties…" name="q" type="search" value="<?= e((string) ($_GET['q'] ?? '')) ?>"/>
      </form>
    </div>
    <nav class="hidden xl:flex items-center gap-gutter" aria-label="Primary">
      <?php foreach ($navItems as $key => $item): ?>
        <?php
          $active = $activeNav === $key;
          $cls = $active
              ? 'transition-all py-1 text-primary font-bold border-b-2 border-primary font-subheading text-subheading uppercase tracking-widest'
              : 'font-subheading text-subheading text-on-surface-variant hover:text-primary transition-all py-1 uppercase tracking-widest';
        ?>
        <a class="<?= e($cls) ?>" href="<?= e($item['href']) ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="flex items-center gap-4">
      <a href="<?= e(base_url('contact.php')) ?>" class="hidden md:inline-flex font-label-sm text-label-sm px-6 py-2 border border-primary text-primary hover:bg-primary hover:text-on-primary transition-all no-underline uppercase tracking-widest">Get in Touch</a>
      <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center" aria-hidden="true">
        <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
      </span>
      <details class="xl:hidden relative">
        <summary class="list-none cursor-pointer material-symbols-outlined text-[28px] text-primary">menu</summary>
        <div class="absolute right-0 mt-3 w-56 bg-surface border border-outline-variant/40 shadow-lg p-4 flex flex-col gap-3 z-50">
          <?php foreach ($navItems as $key => $item): ?>
            <a class="font-subheading text-subheading uppercase tracking-widest <?= $activeNav === $key ? 'text-primary font-bold' : 'text-on-surface' ?>" href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
          <?php endforeach; ?>
        </div>
      </details>
    </div>
  </div>
</header>
<main class="pt-20">
