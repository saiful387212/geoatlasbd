<?php
declare(strict_types=1);

/** Format an ISO/SQL datetime for display. */
function fmt_date(?string $sqlDatetime, string $format = 'M j, Y'): string
{
    if (!$sqlDatetime) {
        return '';
    }
    $ts = strtotime($sqlDatetime);
    return $ts === false ? '' : date($format, $ts);
}

/** Trim text to $limit chars without breaking words. */
function truncate(string $text, int $limit = 140): string
{
    $text = trim(strip_tags($text));
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    $cut  = mb_substr($text, 0, $limit);
    $last = mb_strrpos($cut, ' ');
    if ($last !== false && $last > 0) {
        $cut = mb_substr($cut, 0, $last);
    }
    return rtrim($cut, " \t\n\r\0\x0B.,;:") . '…';
}

/** Split tags CSV into trimmed array, filtering empties. */
function split_tags(?string $csv): array
{
    if ($csv === null || trim($csv) === '') {
        return [];
    }
    return array_values(array_filter(array_map('trim', explode(',', $csv)), fn($t) => $t !== ''));
}
/**
 * Safe URL for templates — returns null if the URL is unsafe to render.
 * Use inside href="" / src="" attributes with e() around the result.
 */
function safe_href(?string $href): ?string
{
    if ($href === null) return null;
    $href = trim($href);
    if ($href === '') return null;
    if (str_starts_with($href, '#')) return $href;
    if (str_starts_with($href, '/')) {
        return str_starts_with($href, '//') ? null : $href;
    }
    return preg_match('#^(https?://|mailto:)#i', $href) ? $href : null;
}

/**
 * Return a safe path relative to /uploads/ for an image/PDF row.
 * Accepts older records that stored only the generated basename.
 * Rejects traversal and absolute paths. Returns null if unsafe.
 */
function safe_upload_path(?string $relativePath, string $expectedSubdir): ?string
{
    if ($relativePath === null) return null;
    $relativePath = trim($relativePath);
    if ($relativePath === '') return null;

    // No protocol, no leading slash, no .., no backslash
    if (preg_match('#^(?:[a-z]+:)?//#i', $relativePath)) return null;
    if (str_contains($relativePath, '..')) return null;
    if (str_contains($relativePath, '\\')) return null;
    $relativePath = ltrim($relativePath, '/');

    // Older uploader versions persisted only the generated filename.
    if (!str_contains($relativePath, '/')) {
        $relativePath = $expectedSubdir . '/' . $relativePath;
    } elseif (str_starts_with($relativePath, 'uploads/' . $expectedSubdir . '/')) {
        $relativePath = substr($relativePath, strlen('uploads/'));
    }

    // Must start with the expected subdir (images/ or pdfs/)
    if (!str_starts_with($relativePath, $expectedSubdir . '/')) {
        return null;
    }

    // Only letters, digits, dot, dash, underscore, slash
    if (!preg_match('#^[A-Za-z0-9._/\-]+$#', $relativePath)) return null;

    return $relativePath;
}

/**
 * Parse comma-separated data_sources into an array of strings.
 * Accepts both "A · B · C" (middle dot) and "A, B, C" (comma) separators.
 */
function parse_data_sources(?string $raw): array
{
    if ($raw === null || trim($raw) === '') return [];
    // Split on '·' or ',' preserving contents
    $parts = preg_split('/\s*[·,]\s*/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
    return array_values(array_map('trim', $parts ?: []));
}

/**
 * Split research_questions text into an array.
 * One per line; lines starting with "- " become bullets without the dash.
 */
function parse_lines(?string $raw): array
{
    if ($raw === null) return [];
    $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
    $out = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if (str_starts_with($line, '- ')) {
            $line = substr($line, 2);
        }
        $out[] = $line;
    }
    return $out;
}