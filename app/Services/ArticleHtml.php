<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Reduces article bodies to a safe, predictable subset of HTML.
 *
 * Article bodies are rendered unescaped so their headings and emphasis survive,
 * which means every path that writes one — the legacy importer and the CMS
 * editor alike — has to come through here first. Anything outside the
 * whitelist is stripped, and all attributes are dropped, so neither a crafted
 * import nor a pasted <script> can reach the page.
 */
class ArticleHtml
{
    /**
     * Markup worth keeping in an article body.
     *
     * @var list<string>
     */
    public const ALLOWED_TAGS = [
        'p', 'br', 'b', 'strong', 'i', 'em', 'u',
        'h3', 'h4', 'h5', 'ul', 'ol', 'li', 'blockquote',
    ];

    public function sanitize(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        // Anything script-like goes before strip_tags, so its text content
        // cannot survive as stray page copy.
        $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;

        $html = strip_tags($html, '<'.implode('><', self::ALLOWED_TAGS).'>');

        // Drop every attribute: none of the whitelisted tags need one, and this
        // removes on* handlers and javascript: URLs in a single pass.
        $html = preg_replace('/<([a-zA-Z0-9]+)\s+[^>]*>/', '<$1>', $html) ?? $html;

        return $this->tidy($html);
    }

    /**
     * Turn a body into a short plain-text summary.
     */
    public function excerpt(?string $html, int $length = 180): ?string
    {
        // strip_tags() butts adjacent blocks together ("<h4>Title</h4><p>Body"
        // becomes "TitleBody"), so break them apart before flattening.
        $spaced = preg_replace('#</(p|h[1-6]|li|blockquote|div)>|<br\s*/?>#i', ' ', (string) $html) ?? (string) $html;

        $text = html_entity_decode(strip_tags($spaced), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return $text === '' ? null : Str::limit($text, $length);
    }

    /**
     * Remove the debris that legacy content tends to carry.
     */
    private function tidy(string $html): string
    {
        // Paragraphs left empty by stripped markup, e.g. "<p></p><h4>".
        $html = preg_replace('#<p>(\s|&nbsp;|<br>)*</p>#i', '', $html) ?? $html;

        // Runs of line breaks used as spacing between blocks.
        $html = preg_replace('#(\s*<br>\s*){3,}#i', '<br><br>', $html) ?? $html;

        return trim($html);
    }
}
