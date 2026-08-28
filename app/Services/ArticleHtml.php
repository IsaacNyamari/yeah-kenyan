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
        'h3', 'h4', 'h5', 'ul', 'ol', 'li', 'blockquote', 'a',
    ];

    /**
     * Schemes a link may use.
     *
     * An allow-list rather than a javascript: block-list: data:, vbscript: and
     * anything else a browser learns to execute are refused by default instead
     * of needing to be thought of first.
     *
     * @var list<string>
     */
    private const ALLOWED_LINK_SCHEMES = ['http', 'https', 'mailto'];

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
        // removes on* handlers and javascript: URLs in a single pass. Links are
        // rebuilt afterwards from their captured href, so they are the one
        // exception and go through their own check.
        $links = $this->captureLinks($html);

        $html = preg_replace('/<([a-zA-Z0-9]+)\s+[^>]*>/', '<$1>', $html) ?? $html;

        $html = $this->restoreLinks($html, $links);

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
     * Pull the href out of every opening anchor, in order.
     *
     * @return list<string|null> null where the href is missing or unsafe
     */
    private function captureLinks(string $html): array
    {
        preg_match_all('/<a[^>]*>/i', $html, $matches);

        return array_map(function (string $tag): ?string {
            $url = $this->hrefFrom($tag);

            return $url !== null && $this->isSafeLink($url) ? $url : null;
        }, $matches[0]);
    }

    /**
     * The href on one opening anchor, however it was quoted.
     *
     * Three patterns rather than one alternation, so each can be written in the
     * quoting style that does not collide with the quote it is matching.
     */
    private function hrefFrom(string $tag): ?string
    {
        $patterns = [
            '/href\s*=\s*"([^"]*)"/i',
            "/href\s*=\s*'([^']*)'/i",
            '/href\s*=\s*([^\s>"]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $tag, $match) === 1) {
                return trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
        }

        return null;
    }

    /**
     * Put the safe hrefs back onto the stripped anchors.
     *
     * An anchor whose href was missing or unsafe keeps its text and loses the
     * link, which is the quiet outcome: the reader still gets the words.
     *
     * @param  list<string|null>  $links
     */
    private function restoreLinks(string $html, array $links): string
    {
        $index = 0;

        return (string) preg_replace_callback('/<a>/i', function () use (&$index, $links): string {
            $href = $links[$index++] ?? null;

            return $href === null
                ? '<a>'
                : '<a href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'" target="_blank" rel="noopener nofollow">';
        }, $html);
    }

    /**
     * Whether a URL may be linked to.
     */
    private function isSafeLink(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        /*
         * A real URL carries no raw quotes, angle brackets or whitespace —
         * those are percent-encoded. Their presence means someone is trying to
         * close the attribute early, so the link is refused rather than
         * escaped and left looking legitimate.
         */
        if (preg_match('/[\s"<>]/', $url) === 1 || str_contains($url, chr(39))) {
            return false;
        }

        // Relative and anchor links stay on this site, so they need no scheme.
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), self::ALLOWED_LINK_SCHEMES, true);
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
