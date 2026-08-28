<?php

use App\Mail\NewsletterIssue;
use App\Models\Newsletter;
use App\Models\NewsletterSend;
use App\Models\NewsletterTemplate;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\NewsletterDispatcher;
use App\Services\NewsletterRenderer;
use App\Support\NewsletterStatus;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
    Mail::fake();
});

it('keeps newsletters to accounts granted them', function () {
    $this->actingAs(User::factory()->create());
    $this->get(route('admin.newsletters'))->assertForbidden();
    $this->get(route('admin.subscribers'))->assertForbidden();

    $this->actingAs(User::factory()->moderator()->create());
    $this->get(route('admin.newsletters'))->assertForbidden();
});

it('drops a template into place around the writing', function () {
    $template = NewsletterTemplate::factory()->create([
        'html' => '<html><body><h1>{{ site_name }}</h1>{{ content }}<a href="{{ unsubscribe_url }}">Stop</a></body></html>',
    ]);

    $newsletter = Newsletter::factory()->create([
        'newsletter_template_id' => $template->id,
        'body' => '<p>The writing.</p>',
    ]);

    $subscriber = Subscriber::factory()->create();

    $html = app(NewsletterRenderer::class)->render($newsletter, $subscriber);

    expect($html)->toContain('<p>The writing.</p>')
        ->and($html)->toContain(config('site.name'))
        ->and($html)->toContain($subscriber->token);
});

it('strips markup a template author never intended to allow', function () {
    // The body reaches the inbox unescaped, so it is sanitized on render too.
    $newsletter = Newsletter::factory()->create([
        'body' => '<p>Fine.</p><script>alert(1)</script>',
    ]);

    expect(app(NewsletterRenderer::class)->render($newsletter))
        ->not->toContain('<script>')
        ->not->toContain('alert(1)');
});

it('falls back to a plain wrapper when the template was deleted', function () {
    $newsletter = Newsletter::factory()->create(['newsletter_template_id' => null, 'body' => '<p>Still sent.</p>']);

    expect(app(NewsletterRenderer::class)->render($newsletter))->toContain('<p>Still sent.</p>');
});

it('writes down the recipient list when sending starts', function () {
    Subscriber::factory()->count(3)->create();
    Subscriber::factory()->unsubscribed()->create();

    $newsletter = Newsletter::factory()->create();

    $count = app(NewsletterDispatcher::class)->prepare($newsletter);

    // The person who unsubscribed is not on the list.
    expect($count)->toBe(3)
        ->and(NewsletterSend::where('newsletter_id', $newsletter->id)->count())->toBe(3)
        ->and($newsletter->fresh()->status)->toBe(NewsletterStatus::Sending);
});

it('sends in batches and finishes', function () {
    Subscriber::factory()->count(5)->create();
    $newsletter = Newsletter::factory()->create();

    $dispatcher = app(NewsletterDispatcher::class);
    $dispatcher->prepare($newsletter);

    $first = $dispatcher->sendChunk($newsletter, limit: 2);

    expect($first['sent'])->toBe(2)
        ->and($first['remaining'])->toBe(3)
        ->and($first['done'])->toBeFalse();

    $dispatcher->sendChunk($newsletter, limit: 10);

    expect($dispatcher->progress($newsletter)['sent'])->toBe(5)
        ->and($newsletter->fresh()->status)->toBe(NewsletterStatus::Sent);

    Mail::assertSent(NewsletterIssue::class, 5);
});

it('never sends the same person an issue twice', function () {
    // Batches are driven by the browser, so a repeated or overlapping call has
    // to be harmless.
    Subscriber::factory()->count(3)->create();
    $newsletter = Newsletter::factory()->create();

    $dispatcher = app(NewsletterDispatcher::class);

    $dispatcher->prepare($newsletter);
    $dispatcher->sendChunk($newsletter);
    $dispatcher->prepare($newsletter);
    $dispatcher->sendChunk($newsletter);

    Mail::assertSent(NewsletterIssue::class, 3);
    expect(NewsletterSend::where('newsletter_id', $newsletter->id)->count())->toBe(3);
});

it('skips someone who unsubscribed after the list was written', function () {
    $staying = Subscriber::factory()->create();
    $leaving = Subscriber::factory()->create();

    $newsletter = Newsletter::factory()->create();
    $dispatcher = app(NewsletterDispatcher::class);
    $dispatcher->prepare($newsletter);

    $leaving->update(['unsubscribed_at' => now()]);

    $dispatcher->sendChunk($newsletter);

    Mail::assertSent(NewsletterIssue::class, 1);
    Mail::assertSent(NewsletterIssue::class, fn ($mail): bool => $mail->subscriber->is($staying));
});

it('drops the delivery record when a subscriber is deleted mid-send', function () {
    Subscriber::factory()->count(3)->create();
    $newsletter = Newsletter::factory()->create();

    $dispatcher = app(NewsletterDispatcher::class);
    $dispatcher->prepare($newsletter);

    $doomed = NewsletterSend::where('newsletter_id', $newsletter->id)->first();
    Subscriber::whereKey($doomed->subscriber_id)->delete();

    // The foreign key cascades, so the row goes with them rather than being
    // left pointing at nothing.
    expect(NewsletterSend::where('newsletter_id', $newsletter->id)->count())->toBe(2);

    $result = $dispatcher->sendChunk($newsletter);

    expect($result['sent'])->toBe(2)
        ->and($result['done'])->toBeTrue();
});

it('marks one recipient failed and keeps going', function () {
    $staying = Subscriber::factory()->count(2)->create();
    $leaving = Subscriber::factory()->create();

    $newsletter = Newsletter::factory()->create();
    $dispatcher = app(NewsletterDispatcher::class);
    $dispatcher->prepare($newsletter);

    $leaving->update(['unsubscribed_at' => now()]);

    $result = $dispatcher->sendChunk($newsletter);

    expect($result['sent'])->toBe(2)
        ->and($result['failed'])->toBe(1)
        ->and($result['done'])->toBeTrue()
        ->and(NewsletterSend::whereNotNull('failure')->count())->toBe(1);
});

it('does not record a test send against the list', function () {
    Subscriber::factory()->count(2)->create();
    $newsletter = Newsletter::factory()->create();

    app(NewsletterDispatcher::class)->sendTest($newsletter, 'check@example.com');

    Mail::assertSent(NewsletterIssue::class, 1);
    expect(NewsletterSend::count())->toBe(0);
});

it('refuses to rewrite an issue that already went out', function () {
    $sent = Newsletter::factory()->sent()->create();

    Livewire::test('pages::admin.newsletters')
        ->call('edit', $sent->id)
        ->assertForbidden();
});

it('saves a draft from the composer', function () {
    $template = NewsletterTemplate::factory()->create();

    Livewire::test('pages::admin.newsletters')
        ->set('subject', 'March roundup')
        ->set('body', '<p>Here is what happened.</p>')
        ->set('newsletter_template_id', $template->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(Newsletter::firstWhere('subject', 'March roundup')?->status)->toBe(NewsletterStatus::Draft);
});

it('insists a template keeps the content placeholder', function () {
    // Without it the design would send and the writing would silently vanish.
    Livewire::test('pages::admin.newsletter-templates')
        ->set('name', 'Broken')
        ->set('html', '<html><body>No placeholder here</body></html>')
        ->call('save')
        ->assertHasErrors('html');

    expect(NewsletterTemplate::where('name', 'Broken')->exists())->toBeFalse();
});

it('keeps only one default template', function () {
    $first = NewsletterTemplate::factory()->default()->create();

    Livewire::test('pages::admin.newsletter-templates')
        ->set('name', 'Second')
        ->set('html', '<html><body>{{ content }}</body></html>')
        ->set('is_default', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($first->fresh()->is_default)->toBeFalse()
        ->and(NewsletterTemplate::where('is_default', true)->count())->toBe(1);
});
