<?php

use App\Services\ArticleHtml;

/*
 * Article bodies are rendered unescaped, so the sanitizer is the only thing
 * between a rich-text editor and the page. Links are the one tag allowed to
 * keep an attribute, which makes them the one worth testing hardest.
 */

beforeEach(function () {
    $this->html = app(ArticleHtml::class);
});

it('keeps an ordinary link', function () {
    $out = $this->html->sanitize('<p>See <a href="https://example.com/page">the page</a>.</p>');

    expect($out)->toContain('href="https://example.com/page"')
        ->and($out)->toContain('the page');
});

it('keeps a mailto and a relative link', function () {
    expect($this->html->sanitize('<a href="mailto:hi@example.com">Mail</a>'))
        ->toContain('href="mailto:hi@example.com"')
        ->and($this->html->sanitize('<a href="/gallery">Gallery</a>'))
        ->toContain('href="/gallery"');
});

it('strips a javascript link but keeps the words', function () {
    $out = $this->html->sanitize('<p><a href="javascript:alert(1)">Click me</a></p>');

    expect($out)->not->toContain('javascript')
        ->and($out)->not->toContain('href')
        ->and($out)->toContain('Click me');
});

it('refuses schemes that are not on the list', function (string $href) {
    // An allow-list, so a scheme nobody thought of is refused by default.
    $out = $this->html->sanitize('<a href="'.$href.'">x</a>');

    expect($out)->not->toContain('href');
})->with([
    'data:text/html;base64,PHNjcmlwdD4=',
    'vbscript:msgbox(1)',
    'file:///etc/passwd',
    'JaVaScRiPt:alert(1)',
]);

it('drops every attribute other than a link href', function () {
    $out = $this->html->sanitize(
        '<p class="x" onclick="alert(1)" style="color:red">Text</p>'
        .'<a href="https://example.com" onmouseover="alert(2)" class="y">Link</a>',
    );

    expect($out)->not->toContain('onclick')
        ->and($out)->not->toContain('onmouseover')
        ->and($out)->not->toContain('class=')
        ->and($out)->not->toContain('style=')
        ->and($out)->toContain('href="https://example.com"');
});

it('opens links in a new tab without leaking the referrer', function () {
    expect($this->html->sanitize('<a href="https://example.com">x</a>'))
        ->toContain('rel="noopener nofollow"')
        ->toContain('target="_blank"');
});

it('keeps several links in the right order', function () {
    // The hrefs are captured, the tags stripped, then the hrefs put back — so
    // a mismatch here would silently point links at the wrong places.
    $out = $this->html->sanitize(
        '<a href="https://one.example">One</a> and <a href="https://two.example">Two</a>',
    );

    expect(strpos($out, 'https://one.example'))->toBeLessThan(strpos($out, 'https://two.example'))
        ->and(substr_count($out, 'href='))->toBe(2);
});

it('does not let a stripped link shift the ones after it', function () {
    $out = $this->html->sanitize(
        '<a href="javascript:alert(1)">Bad</a> <a href="https://good.example">Good</a>',
    );

    expect(substr_count($out, 'href='))->toBe(1)
        ->and($out)->toContain('https://good.example')
        ->and($out)->toContain('Bad');
});

it('escapes a quote smuggled into an href', function () {
    $out = $this->html->sanitize('<a href="https://example.com/&quot; onload=&quot;alert(1)">x</a>');

    expect($out)->not->toContain('onload=');
});

it('still strips script tags and their contents', function () {
    expect($this->html->sanitize('<p>Safe</p><script>alert(1)</script>'))
        ->toBe('<p>Safe</p>');
});

it('keeps the markup the editor produces', function () {
    // Exactly what TipTap emits with the toolbar it is configured with.
    $out = $this->html->sanitize(
        '<h3>Heading</h3><p><strong>Bold</strong> <em>italic</em> <u>underline</u></p>'
        .'<ul><li>One</li></ul><ol><li>Two</li></ol><blockquote><p>Quoted</p></blockquote>',
    );

    foreach (['<h3>', '<strong>', '<em>', '<u>', '<ul>', '<ol>', '<li>', '<blockquote>'] as $tag) {
        expect($out)->toContain($tag);
    }
});
