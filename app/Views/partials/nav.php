<?php
declare(strict_types=1);
/** @var string $currentPath */

$navItems = [
    ['label' => 'Home',     'href' => '/'],
    ['label' => 'Projects', 'href' => '/projects'],
    ['label' => 'About',    'href' => '/about'],
    ['label' => 'Contact',  'href' => '/contact'],
];
?>
<nav class="site-nav" id="primary-navigation" aria-label="Primary">
    <ul class="site-nav__list">
        <?php foreach ($navItems as $item): ?>
            <?php $active = nav_is_active($currentPath, $item['href']); ?>
            <li class="site-nav__item">
                <a class="site-nav__link<?= $active ? ' is-active' : '' ?>"
                   href="<?= e(url($item['href'])) ?>"
                   <?= $active ? 'aria-current="page"' : '' ?>>
                    <?= e($item['label']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>