<?php

use App\Models\User;
use App\Support\Permission;
use App\Support\UserRole;
use Livewire\Livewire;

it('keeps the roles screen to administrators', function () {
    $this->get(route('admin.users'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create());
    $this->get(route('admin.users'))->assertForbidden();

    $this->actingAs(User::factory()->moderator()->create());
    $this->get(route('admin.users'))->assertForbidden();

    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('admin.users'))->assertOk();
});

it('promotes an account to another role', function () {
    $this->actingAs(User::factory()->admin()->create());
    $author = User::factory()->create();

    Livewire::test('pages::admin.users')
        ->call('stageRole', $author->id, UserRole::Moderator->value)
        ->call('runPendingAction');

    expect($author->fresh()->primaryRole())->toBe(UserRole::Moderator);
});

it('replaces the previous role rather than stacking another on top', function () {
    $this->actingAs(User::factory()->admin()->create());
    $moderator = User::factory()->moderator()->create();

    Livewire::test('pages::admin.users')
        ->call('stageRole', $moderator->id, UserRole::Author->value)
        ->call('runPendingAction');

    expect($moderator->fresh()->roles)->toHaveCount(1)
        ->and($moderator->fresh()->primaryRole())->toBe(UserRole::Author);
});

it('refuses to change your own role', function () {
    // The last administrator demoting themselves would leave nobody able to
    // reach this screen and appoint a replacement.
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test('pages::admin.users')
        ->call('stageRole', $admin->id, UserRole::Author->value)
        ->call('runPendingAction');

    expect($admin->fresh()->isAdministrator())->toBeTrue();
});

it('ignores a role name that does not exist', function () {
    $this->actingAs(User::factory()->admin()->create());
    $author = User::factory()->create();

    Livewire::test('pages::admin.users')
        ->call('stageRole', $author->id, 'superuser')
        ->call('runPendingAction');

    expect($author->fresh()->primaryRole())->toBe(UserRole::Author);
});

it('will not apply a role tampered with between staging and confirming', function () {
    $this->actingAs(User::factory()->admin()->create());
    $author = User::factory()->create();

    Livewire::test('pages::admin.users')
        ->call('stageRole', $author->id, UserRole::Moderator->value)
        ->set('pendingRole', 'superuser')
        ->call('runPendingAction');

    expect($author->fresh()->primaryRole())->toBe(UserRole::Author);
});

it('only lets the confirm modal call the role action', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.users')
        ->call('confirmAction', 'applyRole')
        ->assertOk();

    Livewire::test('pages::admin.users')
        ->call('confirmAction', 'delete')
        ->assertForbidden();
});

it('gives each role the abilities it should have', function () {
    $admin = User::factory()->admin()->create();
    $moderator = User::factory()->moderator()->create();
    $author = User::factory()->create();

    expect($admin->can(Permission::ManageRoles->value))->toBeTrue()
        ->and($admin->can(Permission::ModeratePosts->value))->toBeTrue()
        ->and($moderator->can(Permission::ModeratePosts->value))->toBeTrue()
        ->and($moderator->can(Permission::ManageRoles->value))->toBeFalse()
        // A moderator judges other people's articles and edits nothing.
        ->and($moderator->can(Permission::ManageNews->value))->toBeFalse()
        ->and($moderator->can(Permission::ManageGallery->value))->toBeFalse()
        ->and($author->can(Permission::ManageNews->value))->toBeTrue()
        ->and($author->can(Permission::ModeratePosts->value))->toBeFalse();
});

it('registers new accounts as authors', function () {
    expect(UserRole::DEFAULT)->toBe(UserRole::Author);
});
