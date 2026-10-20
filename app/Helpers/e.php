<?php
declare(strict_types=1);

/**
 * HTML-escape everything rendered to the browser.
 * Use as:  <?= e($project['title']) ?>
 */
function e(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}