<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Whitelist-based HTML sanitizer for case-study prose.
 * Allows: p, h2, h3, h4, ul, ol, li, strong, em, b, i, a[href], br, blockquote, code, pre.
 * Strips everything else (scripts, styles, iframes, event handlers).
 * Rejects hrefs that aren't http(s):, mailto:, /, or #.
 */
final class HtmlSanitizer
{
    /** @var string[] */
    private const ALLOWED_TAGS = [
        'p', 'h2', 'h3', 'h4', 'ul', 'ol', 'li',
        'strong', 'em', 'b', 'i', 'br', 'blockquote', 'code', 'pre', 'a',
    ];

    public static function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // Wrap so DOMDocument keeps a proper tree even for fragments.
        $wrapped = '<!DOCTYPE html><html><body>' . $html . '</body></html>';
        $doc = new \DOMDocument();
        // Suppress libxml warnings for slightly malformed input.
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML($wrapped, LIBXML_NONET | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $body = $doc->getElementsByTagName('body')->item(0);
        if (!$body) {
            return '';
        }

        self::cleanNode($body);

        // Serialise only the body's children
        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        return $out;
    }

    private static function cleanNode(\DOMNode $node): void
    {
        // Iterate over a snapshot because we mutate the tree.
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof \DOMComment) {
                $node->removeChild($child);
                continue;
            }
            if ($child instanceof \DOMText) {
                continue;
            }
            if (!$child instanceof \DOMElement) {
                $node->removeChild($child);
                continue;
            }

            $tag = strtolower($child->nodeName);
            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                // Keep child text but strip the tag itself.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            // Strip all attributes except an allow-listed href on <a>.
            $attrs = [];
            foreach ($child->attributes as $attr) {
                $attrs[] = $attr->name;
            }
            foreach ($attrs as $name) {
                $value = $child->getAttribute($name);
                $lower = strtolower($name);
                if ($tag === 'a' && $lower === 'href') {
                    if (!self::isSafeHref($value)) {
                        $child->removeAttribute($name);
                    } else {
                        $child->setAttribute('href', $value);
                        // Force safe rel on external links.
                        $child->setAttribute('rel', 'noopener noreferrer');
                        if (preg_match('#^https?://#i', $value)) {
                            $child->setAttribute('target', '_blank');
                        }
                    }
                } else {
                    $child->removeAttribute($name);
                }
            }

            self::cleanNode($child);
        }
    }

    public static function isSafeHref(string $href): bool
    {
        $href = trim($href);
        if ($href === '') return false;
        if (str_starts_with($href, '#')) return true;
        if (str_starts_with($href, '/')) {
            // Reject protocol-relative //evil.com
            return !str_starts_with($href, '//');
        }
        return (bool) preg_match('#^(https?://|mailto:)#i', $href);
    }
}