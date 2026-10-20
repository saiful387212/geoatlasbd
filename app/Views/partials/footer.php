<?php
declare(strict_types=1);
$year = date('Y');
?>
<footer class="site-footer">
    <div class="container site-footer__inner">
        <div class="site-footer__brand">
            <img src="<?= e(url('/assets/img/logo.png')) ?>" alt="" width="28" height="28">
            <span>GeoAtlas Bangladesh</span>
        </div>

        <nav class="site-footer__nav" aria-label="Footer">
            <ul>
                <li><a href="<?= e(url('/about')) ?>">About</a></li>
                <li><a href="<?= e(url('/contact')) ?>">Contact</a></li>
                <li><a href="<?= e(url('/projects')) ?>">Projects</a></li>
            </ul>
        </nav>

        <p class="site-footer__meta">
            &copy; <?= e((string) $year) ?> GeoAtlas Bangladesh &middot;
            Understanding Bangladesh Through Maps.
        </p>
    </div>
</footer>