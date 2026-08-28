<?php

namespace App\Services;

use App\Models\Newsletter;
use App\Models\NewsletterTemplate;
use App\Models\Subscriber;
use Illuminate\Support\Str;

/**
 * Puts an issue inside its template.
 *
 * Templates are edited in the CMS, so the placeholder syntax is deliberately
 * plain: {{ name }} and nothing else. Blade would hand whoever edits a template
 * the ability to run PHP, which is a much bigger thing to grant than "change
 * the masthead colour".
 */
class NewsletterRenderer
{
    public function __construct(private ArticleHtml $articleHtml) {}

    /**
     * Render an issue as it will arrive for one recipient.
     *
     * A subscriber is optional so the CMS can preview an issue before anyone
     * has been chosen to receive it.
     */
    public function render(Newsletter $newsletter, ?Subscriber $subscriber = null): string
    {
        $template = $newsletter->template ?? $this->fallbackTemplate();

        return $this->fill($template->html, $this->tokens($newsletter, $subscriber));
    }

    /**
     * The plain-text alternative, so the message is not sent as HTML alone.
     */
    public function renderText(Newsletter $newsletter, ?Subscriber $subscriber = null): string
    {
        $body = html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], "\n", $newsletter->body)));

        $lines = [
            $newsletter->subject,
            '',
            trim(preg_replace('/\n{3,}/', "\n\n", $body) ?? $body),
            '',
            '—',
            config('site.name'),
        ];

        if ($subscriber instanceof Subscriber) {
            $lines[] = 'Unsubscribe: '.$this->unsubscribeUrl($subscriber);
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, string>
     */
    private function tokens(Newsletter $newsletter, ?Subscriber $subscriber): array
    {
        return [
            'subject' => e($newsletter->subject),
            'preheader' => e((string) $newsletter->preheader),
            // Sanitized on the way in, and again here: a body written before
            // the sanitizer existed would otherwise go out unfiltered.
            'content' => $this->articleHtml->sanitize($newsletter->body),
            'site_name' => e((string) config('site.name')),
            'site_url' => e(route('home')),
            'year' => (string) now()->year,
            'name' => e($subscriber?->name ?: 'there'),
            'email' => e((string) $subscriber?->email),
            'unsubscribe_url' => $subscriber instanceof Subscriber
                ? e($this->unsubscribeUrl($subscriber))
                : e(route('home')),
        ];
    }

    public function unsubscribeUrl(Subscriber $subscriber): string
    {
        return route('newsletter.unsubscribe', $subscriber->token);
    }

    /**
     * @param  array<string, string>  $tokens
     */
    private function fill(string $html, array $tokens): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-z_]+)\s*\}\}/i',
            fn (array $match): string => $tokens[Str::lower($match[1])] ?? '',
            $html,
        );
    }

    /**
     * Used when an issue has no template, or its template was deleted after
     * the issue was written. Sending a bare body beats sending nothing.
     */
    private function fallbackTemplate(): NewsletterTemplate
    {
        return new NewsletterTemplate([
            'name' => 'Plain',
            'html' => self::STARTER_HTML,
        ]);
    }

    /**
     * The template a new installation starts with, and the one the CMS offers
     * as a starting point for a new design.
     */
    public const STARTER_HTML = <<<'HTML'
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:Helvetica,Arial,sans-serif;color:#27272a;">
    <span style="display:none;max-height:0;overflow:hidden;">{{ preheader }}</span>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#dc2626;padding:24px;text-align:center;">
                            <h1 style="margin:0;font-size:22px;color:#ffffff;">{{ site_name }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 28px;">
                            <h2 style="margin:0 0 16px;font-size:20px;color:#18181b;">{{ subject }}</h2>
                            <p style="margin:0 0 20px;font-size:15px;color:#52525b;">Hi {{ name }},</p>
                            <div style="font-size:15px;line-height:1.65;color:#3f3f46;">{{ content }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 28px;background:#fafafa;text-align:center;font-size:12px;color:#71717a;">
                            <p style="margin:0 0 8px;">&copy; {{ year }} {{ site_name }}</p>
                            <p style="margin:0;">
                                <a href="{{ unsubscribe_url }}" style="color:#71717a;">Unsubscribe</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}
