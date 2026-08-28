<?php

use App\Models\User;
use App\Services\Impersonator;

/*
 * An administrator can look at the dashboard as somebody else. The way back is
 * a single id parked in the session, so most of what matters here is that the
 * session is never left holding a borrowed identity.
 */

it('lets an administrator view the site as another account', function () {
    $admin = User::factory()->admin()->create();
    $author = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.impersonate', $author))
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($author->id)
        ->and(app(Impersonator::class)->isImpersonating())->toBeTrue()
        ->and(app(Impersonator::class)->impersonator()?->id)->toBe($admin->id);
});

it('hands the session back when it stops', function () {
    $admin = User::factory()->admin()->create();
    $author = User::factory()->create();

    $this->actingAs($admin)->post(route('admin.impersonate', $author));

    $this->post(route('impersonate.stop'))->assertRedirect(route('admin.users'));

    expect(auth()->id())->toBe($admin->id)
        ->and(app(Impersonator::class)->isImpersonating())->toBeFalse();
});

it('refuses anyone without the roles permission', function () {
    $author = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($author)
        ->post(route('admin.impersonate', $target))
        ->assertForbidden();

    expect(auth()->id())->toBe($author->id);
});

it('refuses a moderator, who has no business borrowing accounts', function () {
    $moderator = User::factory()->moderator()->create();
    $target = User::factory()->create();

    $this->actingAs($moderator)
        ->post(route('admin.impersonate', $target))
        ->assertForbidden();
});

it('refuses to impersonate yourself', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.impersonate', $admin))
        ->assertForbidden();

    expect(app(Impersonator::class)->isImpersonating())->toBeFalse();
});

it('refuses to nest one impersonation inside another', function () {
    // A second hop would overwrite the parked id and strand the administrator.
    $admin = User::factory()->admin()->create();
    $first = User::factory()->admin()->create();
    $second = User::factory()->create();

    $this->actingAs($admin)->post(route('admin.impersonate', $first));

    $this->post(route('admin.impersonate', $second))->assertForbidden();

    expect(app(Impersonator::class)->impersonator()?->id)->toBe($admin->id);
});

it('sees the site through the impersonated account permissions', function () {
    $admin = User::factory()->admin()->create();
    $author = User::factory()->create();

    $this->actingAs($admin)->post(route('admin.impersonate', $author));

    // Borrowed identity means borrowed limits, not the administrator's own.
    $this->get(route('admin.settings'))->assertForbidden();
    $this->get(route('admin.posts'))->assertOk();
});

it('shows the banner while impersonating and not before', function () {
    $admin = User::factory()->admin()->create();
    $author = User::factory()->create();

    $this->actingAs($admin)->get(route('dashboard'))->assertDontSee('Back to my account');

    $this->post(route('admin.impersonate', $author));

    $this->get(route('dashboard'))->assertSee('Back to my account');
});

it('signs out rather than stranding a session when the administrator is gone', function () {
    $admin = User::factory()->admin()->create();
    $author = User::factory()->create();

    $this->actingAs($admin)->post(route('admin.impersonate', $author));

    $admin->delete();

    $this->post(route('impersonate.stop'))->assertRedirect(route('dashboard'));

    expect(auth()->check())->toBeFalse();
});
