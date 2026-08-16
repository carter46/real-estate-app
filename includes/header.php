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
$headerSearchQ = (string) ($_GET['q'] ?? '');
$showLogo = site_has_logo();
?>
<header class="fixed top-0 w-full z-50 bg-surface/90 backdrop-blur-md shadow-[0_1px_8px_rgba(55,5,24,0.04)]">
  <input type="checkbox" id="site-nav" class="peer sr-only" autocomplete="off">
  <div class="h-20 max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop flex items-center justify-between">
    <div class="flex items-center gap-6 min-w-0">
      <a href="<?= e(base_url('index.php')) ?>" class="flex items-center gap-3 no-underline min-w-0">
        <?php if ($showLogo): ?>
          <img src="<?= e(site_logo_url()) ?>" alt="<?= e(site_name()) ?>" class="h-10 w-auto max-w-[12rem] object-contain"/>
        <?php else: ?>
          <span class="font-display-lg text-[1.35rem] leading-none text-primary truncate"><?= e(site_name()) ?></span>
        <?php endif; ?>
      </a>
      <form action="<?= e(base_url('properties.php')) ?>" method="get" class="hidden lg:flex items-center bg-surface-container px-4 py-2 rounded-full border border-outline-variant/30 text-on-surface-variant">
        <span class="material-symbols-outlined text-[20px] mr-2">search</span>
        <input class="bg-transparent border-none outline-none text-label-sm w-56 placeholder:text-on-surface-variant/50 font-label-sm" placeholder="Region, beds, address…" name="q" type="search" value="<?= e($headerSearchQ) ?>"/>
      </form>
    </div>
    <nav class="hidden xl:flex items-center gap-gutter" aria-label="Primary">
      <?php foreach ($navItems as $key => $item): ?>
        <?php
          $active = $activeNav === $key;
          $cls = $active
              ? 'transition-all py-1 text-primary font-bold border-b-2 border-primary font-subheading text-[11px] uppercase tracking-[0.12em]'
              : 'font-subheading text-[11px] text-on-surface-variant hover:text-primary transition-all py-1 uppercase tracking-[0.12em]';
        ?>
        <a class="<?= e($cls) ?>" href="<?= e($item['href']) ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="flex items-center gap-4">
      <a href="<?= e(base_url('contact.php')) ?>" class="hidden md:inline-flex font-label-sm text-label-sm px-6 py-2 border border-primary text-primary hover:bg-primary hover:text-on-primary transition-all no-underline uppercase tracking-widest">Get in Touch</a>
      <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center" aria-hidden="true">
        <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
      </span>
      <label for="site-nav" class="xl:hidden list-none cursor-pointer material-symbols-outlined text-[28px] text-primary select-none" aria-label="Open menu">menu</label>
    </div>
  </div>

  <label for="site-nav" class="fixed inset-0 z-[60] bg-black/45 opacity-0 pointer-events-none transition-opacity duration-300 peer-checked:opacity-100 peer-checked:pointer-events-auto xl:hidden" aria-hidden="true"></label>

  <aside class="fixed inset-y-0 right-0 z-[70] flex w-full max-w-none flex-col bg-surface shadow-2xl transition-transform duration-300 ease-out translate-x-full peer-checked:translate-x-0 xl:hidden" aria-label="Mobile navigation">
    <div class="flex h-20 items-center justify-between px-margin-mobile border-b border-outline-variant/30">
      <span class="font-subheading text-subheading uppercase tracking-widest text-primary">Menu</span>
      <label for="site-nav" class="material-symbols-outlined text-[28px] text-primary cursor-pointer select-none" aria-label="Close menu">close</label>
    </div>
    <div class="flex-1 overflow-y-auto px-margin-mobile py-6 flex flex-col gap-8">
      <form action="<?= e(base_url('properties.php')) ?>" method="get" class="flex items-center bg-surface-container px-4 py-3 rounded-full border border-outline-variant/30 text-on-surface-variant">
        <span class="material-symbols-outlined text-[22px] mr-2">search</span>
        <input class="bg-transparent border-none outline-none text-body-md w-full placeholder:text-on-surface-variant/50 font-body-md" placeholder="Region, beds, address…" name="q" type="search" value="<?= e($headerSearchQ) ?>"/>
      </form>
      <nav class="flex flex-col gap-1" aria-label="Mobile">
        <?php foreach ($navItems as $key => $item): ?>
          <a class="font-subheading text-subheading uppercase tracking-widest py-3 border-b border-outline-variant/25 <?= $activeNav === $key ? 'text-primary font-bold' : 'text-on-surface' ?>" href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
        <?php endforeach; ?>
      </nav>
      <a href="<?= e(base_url('contact.php')) ?>" class="inline-flex justify-center font-label-sm text-label-sm px-6 py-3 border border-primary text-primary hover:bg-primary hover:text-on-primary transition-all no-underline uppercase tracking-widest">Get in Touch</a>
    </div>
  </aside>
</header>
<main class="pt-20">
