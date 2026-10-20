<?php
declare(strict_types=1);
/** @var string $sectionTitle */
/** @var string $sectionHtml */
/** @var string $sectionId */
$sectionId = $sectionId ?? ('section-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($sectionTitle)));
?>
<section class="case-section" aria-labelledby="<?= e($sectionId) ?>">
    <h2 id="<?= e($sectionId) ?>"><?= e($sectionTitle) ?></h2>
    <div class="prose"><?= $sectionHtml /* sanitized in the model layer */ ?></div>
</section>