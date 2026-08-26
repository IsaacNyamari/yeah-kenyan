<?php

namespace App\Concerns;

use Flux\Flux;
use Illuminate\Support\Arr;

/**
 * Routes destructive actions through a Flux modal instead of the browser's
 * native confirm() dialog.
 *
 * The pending action is held in a public property, which the client can write
 * to. {@see confirmableActions()} is therefore a mandatory allow-list: without
 * it, a crafted request could name any public method on the component and have
 * runPendingAction() invoke it.
 */
trait ConfirmsActions
{
    /**
     * The action awaiting confirmation.
     *
     * @var array{method: string, id: int|string|null, heading: string, text: string, confirm: string, variant: string}|null
     */
    public ?array $pendingAction = null;

    /**
     * Methods this component permits the confirm modal to call.
     *
     * @return list<string>
     */
    abstract public function confirmableActions(): array;

    /**
     * Stage an action and open the modal.
     *
     * @param  array<string, string>  $copy
     */
    public function confirmAction(string $method, int|string|null $id = null, array $copy = []): void
    {
        abort_unless(in_array($method, $this->confirmableActions(), true), 403);

        $this->pendingAction = [
            'method' => $method,
            'id' => $id,
            'heading' => Arr::get($copy, 'heading', 'Are you sure?'),
            'text' => Arr::get($copy, 'text', 'This action cannot be undone.'),
            'confirm' => Arr::get($copy, 'confirm', 'Confirm'),
            'variant' => Arr::get($copy, 'variant', 'danger'),
        ];

        Flux::modal('confirm-action')->show();
    }

    /**
     * Run the staged action, re-checking the allow-list because the property
     * may have been tampered with between staging and confirming.
     */
    public function runPendingAction(): void
    {
        $action = $this->pendingAction;

        if ($action === null) {
            return;
        }

        abort_unless(in_array($action['method'], $this->confirmableActions(), true), 403);

        $this->pendingAction = null;

        Flux::modal('confirm-action')->close();

        $action['id'] === null
            ? $this->{$action['method']}()
            : $this->{$action['method']}($action['id']);
    }

    public function cancelPendingAction(): void
    {
        $this->pendingAction = null;

        Flux::modal('confirm-action')->close();
    }
}
