<?php

use App\Models\Subscriber;
use Flux\Flux;
use Livewire\Component;

new class extends Component {
    public string $email = '';

    public function subscribe(): void
    {
        $validated = $this->validate([
            'email' => ['required', 'email', 'max:255', 'unique:subscribers,email'],
        ], [
            'email.unique' => 'You are already subscribed to our newsletter.',
        ]);

        Subscriber::create($validated);

        $this->reset('email');

        Flux::toast(variant: 'success', heading: 'Subscribed', text: 'Thanks for subscribing to our newsletter.');
    }
}; ?>

<form wire:submit="subscribe" class="flex flex-col gap-3 sm:flex-row">
    <div class="flex-1">
        <flux:input
            wire:model="email"
            type="email"
            placeholder="Your email address"
            aria-label="Your email address"
            required
        />
    </div>

    <flux:button type="submit" variant="primary">
        <span wire:loading.remove wire:target="subscribe">Sign Up</span>
        <span wire:loading wire:target="subscribe">Signing up...</span>
    </flux:button>
</form>
