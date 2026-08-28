<?php

use App\Concerns\ConfirmsActions;
use App\Models\User;
use App\Support\UserRole;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('User roles')] class extends Component {
    use ConfirmsActions;
    use WithPagination;

    public string $search = '';

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
            ->with('roles')
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->paginate(15);
    }

    /**
     * @return list<UserRole>
     */
    #[Computed]
    public function roles(): array
    {
        return UserRole::cases();
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
            'text' => $user->name.' will become '.$target->label().'. '.$target->description(),
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

        $this->pendingRole = null;

        unset($this->users, $this->administratorCount);

        Flux::toast(
            variant: 'success',
            heading: 'Role updated',
            text: $user->name.' is now '.$target->label().'.',
        );
    }

    /**
     * An administrator editing their own role could demote the only account
     * able to reach this screen, leaving the site with no administrator and
     * no way to appoint one.
     */
    private function wouldLockOut(User $user): bool
    {
        if (! $user->is(auth()->user())) {
            return false;
        }

        Flux::toast(
            variant: 'warning',
            heading: 'Not allowed',
            text: 'You cannot change your own role. Ask another administrator to do it.',
        );

        return true;
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
            <flux:heading size="xl">User roles</flux:heading>
            <flux:text class="mt-1">
                Decide what each account may do. New accounts start as authors.
            </flux:text>
        </div>

        <flux:badge color="zinc">
            {{ $this->administratorCount }} administrator{{ $this->administratorCount === 1 ? '' : 's' }}
        </flux:badge>
    </div>

    <flux:card>
        <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass"
                    placeholder="Search by name or email" size="sm" class="max-w-sm" />

        <div class="mt-4 overflow-x-auto" wire:loading.class="opacity-50">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-3 py-2 text-start font-medium">Account</th>
                        <th class="px-3 py-2 text-start font-medium">Current role</th>
                        <th class="px-3 py-2 text-start font-medium">Joined</th>
                        <th class="px-3 py-2 text-end font-medium">Change role</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->users as $account)
                        @php($role = $account->primaryRole())
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                            <td class="px-3 py-3">
                                <div class="font-medium">{{ $account->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $account->email }}</div>
                            </td>
                            <td class="px-3 py-3">
                                @if ($role)
                                    <flux:badge size="sm" :color="$role->badgeColor()">{{ $role->label() }}</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">None</flux:badge>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-zinc-500">
                                {{ site_time($account->created_at)?->format('M d, Y') }}
                            </td>
                            <td class="px-3 py-3">
                                @if ($account->is(auth()->user()))
                                    <div class="text-end text-xs text-zinc-500">Your own account</div>
                                @else
                                    <div class="flex flex-wrap justify-end gap-1">
                                        @foreach ($this->roles as $option)
                                            <flux:button size="xs"
                                                         :variant="$role === $option ? 'primary' : 'ghost'"
                                                         :disabled="$role === $option"
                                                         wire:click="stageRole({{ $account->id }}, '{{ $option->value }}')">
                                                {{ $option->label() }}
                                            </flux:button>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-10 text-center">
                                <flux:text>No accounts match that search.</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $this->users->links() }}</div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">What each role can do</flux:heading>

        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            @foreach ($this->roles as $option)
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:heading size="sm">{{ $option->label() }}</flux:heading>
                    <flux:text size="sm" class="mt-1">{{ $option->description() }}</flux:text>
                </div>
            @endforeach
        </div>
    </flux:card>
</div>
