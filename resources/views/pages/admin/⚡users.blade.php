<?php

use App\Concerns\ConfirmsActions;
use App\Models\User;
use App\Support\Permission;
use App\Support\UserRole;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Roles and permissions')] class extends Component {
    use ConfirmsActions;
    use WithPagination;

    public string $search = '';

    public ?int $selectedId = null;

    /**
     * The role staged for the account awaiting confirmation. Held separately
     * because the confirm modal only carries an id.
     */
    public ?string $pendingRole = null;

    /**
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->with('roles', 'permissions')
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function selected(): ?User
    {
        return $this->selectedId
            ? User::with('roles', 'permissions')->find($this->selectedId)
            : null;
    }

    /**
     * @return list<UserRole>
     */
    #[Computed]
    public function roles(): array
    {
        return UserRole::cases();
    }

    /**
     * @return array<string, list<Permission>>
     */
    #[Computed]
    public function permissionGroups(): array
    {
        return Permission::grouped();
    }

    #[Computed]
    public function administratorCount(): int
    {
        return User::role(UserRole::Admin->value)->count();
    }

    /**
     * @return list<string>
     */
    public function confirmableActions(): array
    {
        return ['applyRole'];
    }

    public function select(int $id): void
    {
        $this->selectedId = $id;
    }

    public function stageRole(int $userId, string $role): void
    {
        $target = UserRole::tryFrom($role);
        $user = User::findOrFail($userId);

        if ($target === null) {
            Flux::toast(variant: 'danger', heading: 'Unknown role', text: 'That role does not exist.');

            return;
        }

        if ($this->wouldLockOut($user)) {
            return;
        }

        $this->pendingRole = $target->value;

        $this->confirmAction('applyRole', $user->id, [
            'heading' => 'Change the role for '.$user->name.'?',
            'text' => $user->name.' will become '.$target->label().'. '.$target->description()
                .' Any permissions granted on top of their old role are cleared.',
            'confirm' => 'Change role',
            'variant' => $target === UserRole::Admin ? 'danger' : 'info',
        ]);
    }

    public function applyRole(int $userId): void
    {
        $target = UserRole::tryFrom((string) $this->pendingRole);
        $user = User::findOrFail($userId);

        // Re-checked here because both the staged role and the id travel
        // through the browser between staging and confirming.
        if ($target === null || $this->wouldLockOut($user)) {
            $this->pendingRole = null;

            return;
        }

        $user->syncRoles([$target->value]);

        // Extras were granted against the old role. Carrying them over would
        // quietly leave a demoted account holding abilities nobody re-approved.
        $user->syncPermissions([]);

        $this->pendingRole = null;

        $this->refreshLists();

        Flux::toast(
            variant: 'success',
            heading: 'Role updated',
            text: $user->name.' is now '.$target->label().'.',
        );
    }

    /**
     * Grant or withdraw one permission on top of whatever the role gives.
     */
    public function togglePermission(int $userId, string $permission): void
    {
        $ability = Permission::tryFrom($permission);
        $user = User::findOrFail($userId);

        if ($ability === null) {
            return;
        }

        if ($this->wouldLockOut($user)) {
            return;
        }

        // A permission the role already carries is not the caller's to remove;
        // withdrawing it means changing the role.
        if ($this->roleGrants($user, $ability)) {
            Flux::toast(
                variant: 'warning',
                heading: 'Comes with the role',
                text: $ability->label().' is part of being '.($user->primaryRole()?->label() ?? 'this role').'. Change the role to remove it.',
            );

            return;
        }

        $user->hasDirectPermission($ability->value)
            ? $user->revokePermissionTo($ability->value)
            : $user->givePermissionTo($ability->value);

        $this->refreshLists();

        Flux::toast(
            variant: 'success',
            text: $user->hasDirectPermission($ability->value)
                ? $user->name.' can now '.lcfirst($ability->label()).'.'
                : $ability->label().' withdrawn from '.$user->name.'.',
        );
    }

    public function roleGrants(User $user, Permission $permission): bool
    {
        return $user->roles->contains(
            fn ($role): bool => $role->hasPermissionTo($permission->value),
        );
    }

    /**
     * An administrator editing their own access could lock themselves out of
     * the only screen that can grant it back.
     */
    private function wouldLockOut(User $user): bool
    {
        if (! $user->is(auth()->user())) {
            return false;
        }

        Flux::toast(
            variant: 'warning',
            heading: 'Not allowed',
            text: 'You cannot change your own access. Ask another administrator to do it.',
        );

        return true;
    }

    private function refreshLists(): void
    {
        unset($this->users, $this->selected, $this->administratorCount);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    <x-admin.confirm-modal :pending="$pendingAction" />

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">Roles and permissions</flux:heading>
            <flux:text class="mt-1">
                A role sets the baseline. Grant extra permissions on top of it for one person without
                changing what everyone with that role can do.
            </flux:text>
        </div>

        <flux:badge color="zinc">
            {{ $this->administratorCount }} administrator{{ $this->administratorCount === 1 ? '' : 's' }}
        </flux:badge>
    </div>

    <div class="grid gap-6 lg:grid-cols-5">

        {{-- Accounts --}}
        <div class="lg:col-span-2">
            <flux:card>
                <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass"
                            placeholder="Search by name or email" size="sm" />

                <div class="mt-4 space-y-2" wire:loading.class="opacity-50">
                    @forelse ($this->users as $account)
                        @php($role = $account->primaryRole())
                        <button type="button" wire:click="select({{ $account->id }})"
                                @class([
                                    'w-full rounded-lg border p-3 text-start transition',
                                    'border-brand-500 bg-brand-50/50 dark:bg-brand-900/10' => $selectedId === $account->id,
                                    'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' => $selectedId !== $account->id,
                                ])>
                            <div class="flex items-center gap-2">
                                <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $account->name }}</span>
                                @if ($role)
                                    <flux:badge size="sm" :color="$role->badgeColor()">{{ $role->label() }}</flux:badge>
                                @endif
                            </div>
                            <p class="mt-0.5 truncate text-xs text-zinc-500">{{ $account->email }}</p>
                            @if ($account->permissions->isNotEmpty())
                                <p class="mt-1 text-xs text-brand-600">
                                    +{{ $account->permissions->count() }} extra permission{{ $account->permissions->count() === 1 ? '' : 's' }}
                                </p>
                            @endif
                        </button>
                    @empty
                        <flux:text size="sm">No accounts match that search.</flux:text>
                    @endforelse
                </div>

                <div class="mt-4">{{ $this->users->links() }}</div>
            </flux:card>
        </div>

        {{-- Access for the selected account --}}
        <div class="lg:col-span-3">
            @if ($this->selected)
                @php($account = $this->selected)
                @php($isSelf = $account->is(auth()->user()))

                <div class="space-y-6">
                    <flux:card>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <flux:heading size="lg">{{ $account->name }}</flux:heading>
                                <flux:text class="mt-1">{{ $account->email }}</flux:text>
                            </div>

                            @unless ($isSelf)
                                <form method="POST" action="{{ route('admin.impersonate', $account) }}">
                                    @csrf
                                    <flux:button type="submit" size="sm" variant="ghost" icon="eye">
                                        View the site as them
                                    </flux:button>
                                </form>
                            @endunless
                        </div>

                        @if ($isSelf)
                            <flux:callout variant="secondary" class="mt-4">
                                <flux:callout.text>
                                    This is your own account. Another administrator has to change your access, so
                                    nobody can lock themselves out of this screen.
                                </flux:callout.text>
                            </flux:callout>
                        @endif

                        <flux:separator class="my-5" />

                        <flux:heading size="sm">Role</flux:heading>
                        <flux:text size="sm" class="mt-1">Sets the baseline and clears any extras.</flux:text>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($this->roles as $option)
                                <flux:button size="sm"
                                             :variant="$account->primaryRole() === $option ? 'primary' : 'ghost'"
                                             :disabled="$isSelf || $account->primaryRole() === $option"
                                             wire:click="stageRole({{ $account->id }}, '{{ $option->value }}')">
                                    {{ $option->label() }}
                                </flux:button>
                            @endforeach
                        </div>

                        <flux:text size="sm" class="mt-3">
                            {{ $account->primaryRole()?->description() }}
                        </flux:text>
                    </flux:card>

                    <flux:card>
                        <flux:heading size="lg">Permissions</flux:heading>
                        <flux:text size="sm" class="mt-1">
                            Ticked and greyed means it comes with the role. Tick anything else to grant it to
                            {{ $account->name }} alone.
                        </flux:text>

                        <div class="mt-5 space-y-6">
                            @foreach ($this->permissionGroups as $group => $permissions)
                                <div>
                                    <flux:heading size="sm" class="text-zinc-500">{{ $group }}</flux:heading>

                                    <div class="mt-2 space-y-1">
                                        @foreach ($permissions as $permission)
                                            @php($fromRole = $this->roleGrants($account, $permission))
                                            @php($direct = $account->hasDirectPermission($permission->value))

                                            <label @class([
                                                'flex items-start gap-3 rounded-lg border p-3 transition',
                                                'cursor-pointer hover:border-zinc-300 dark:hover:border-zinc-600' => ! $fromRole && ! $isSelf,
                                                'border-zinc-200 dark:border-zinc-700' => ! $direct || $fromRole,
                                                'border-brand-400 bg-brand-50/40 dark:bg-brand-900/10' => $direct && ! $fromRole,
                                            ])>
                                                <input type="checkbox" class="mt-0.5 size-4 accent-brand-600"
                                                       @checked($fromRole || $direct)
                                                       @disabled($fromRole || $isSelf)
                                                       wire:click="togglePermission({{ $account->id }}, '{{ $permission->value }}')">

                                                <span class="min-w-0 flex-1">
                                                    <span class="flex flex-wrap items-center gap-2 text-sm font-medium">
                                                        {{ $permission->label() }}

                                                        @if ($fromRole)
                                                            <flux:badge size="sm" color="zinc">From role</flux:badge>
                                                        @elseif ($direct)
                                                            <flux:badge size="sm" color="sky">Granted</flux:badge>
                                                        @endif

                                                        @if ($permission->isPrivileged() && ! $fromRole)
                                                            <flux:badge size="sm" color="red">Hands over control</flux:badge>
                                                        @endif
                                                    </span>
                                                    <span class="mt-0.5 block text-xs text-zinc-500">
                                                        {{ $permission->description() }}
                                                    </span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </flux:card>
                </div>
            @else
                <flux:card>
                    <div class="py-16 text-center">
                        <flux:icon.user-group class="mx-auto size-10 text-zinc-400" />
                        <flux:text class="mt-3">Select an account to set what it can do.</flux:text>
                    </div>
                </flux:card>
            @endif
        </div>
    </div>
</div>
