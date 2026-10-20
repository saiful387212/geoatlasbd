<?php
declare(strict_types=1);
?>
<section class="page-hero">
    <div class="container">
        <p class="eyebrow">404</p>
        <h1 class="page-hero__title">Project not found</h1>
        <p class="page-hero__lead">
            This project either does not exist, has not been published,
            or the URL is no longer valid.
        </p>
        <p style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1.5rem;">
            <a class="btn btn--primary" href="<?= e(url('/projects')) ?>">Browse all projects</a>
            <a class="btn btn--ghost" href="<?= e(url('/')) ?>">Back to home</a>
        </p>
    </div>
</section>