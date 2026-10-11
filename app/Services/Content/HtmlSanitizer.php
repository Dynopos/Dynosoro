<?php

namespace App\Services\Content;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list HTML cleaner for article bodies (rule 5). Anything not listed is removed:
 * unknown tags are unwrapped (their text kept), dangerous ones (script, style, iframe,
 * form...) are dropped with their content, and every attribute except a safe internal
 * href is stripped. Only links to the website's own /blog/ pages survive.
 */
class HtmlSanitizer
{
    public const ALLOWED_TAGS = [
        'h2', 'h3', 'p', 'ul', 'ol', 'li', 'strong', 'em', 'a', 'blockquote',
        'table', 'thead', 'tbody', 'tr', 'th', 'td', 'br',
    ];

    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select',
        'textarea', 'noscript', 'template', 'svg', 'math', 'link', 'meta', 'head', 'title',
    ];

    public function clean(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="dynoseo-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('dynoseo-root');

        if ($root === null) {
            return '';
        }

        $this->cleanChildren($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim(preg_replace("/\n{3,}/", "\n\n", $out) ?? $out);
    }

    private function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);

                continue;
            }

            $this->cleanChildren($child);

            if ($tag === 'h1') {
                $this->rename($child, 'h2');

                continue;
            }

            if ($tag === 'b' || $tag === 'i') {
                $this->rename($child, $tag === 'b' ? 'strong' : 'em');

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                $this->unwrap($child);

                continue;
            }

            $href = $tag === 'a' ? trim($child->getAttribute('href')) : null;

            foreach (iterator_to_array($child->attributes) as $attribute) {
                $child->removeAttribute($attribute->nodeName);
            }

            if ($tag === 'a') {
                if ($href !== null && self::isInternalBlogLink($href)) {
                    $child->setAttribute('href', $href);
                } else {
                    $this->unwrap($child);
                }
            }
        }
    }

    public static function isInternalBlogLink(string $href): bool
    {
        return preg_match('#^/blog/[a-z0-9-]+/?$#', $href) === 1;
    }

    private function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private function rename(DOMElement $element, string $tag): void
    {
        $replacement = $element->ownerDocument->createElement($tag);

        while ($element->firstChild) {
            $replacement->appendChild($element->firstChild);
        }

        $element->parentNode->replaceChild($replacement, $element);
    }
}
