<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Lets an administrator see the dashboard as somebody else.
 *
 * The administrator's own id is parked in the session and is the only way back,
 * so the session is never left holding a borrowed identity with no route home.
 * Nesting is refused: a second hop would overwrite that id and strand the
 * original account.
 */
class Impersonator
{
    private const SESSION_KEY = 'impersonator_id';

    public function isImpersonating(): bool
    {
        return Session::has(self::SESSION_KEY);
    }

    /**
     * The administrator behind the account currently being viewed.
     */
    public function impersonator(): ?User
    {
        $id = Session::get(self::SESSION_KEY);

        return is_numeric($id) ? User::find((int) $id) : null;
    }

    /**
     * Whether the signed-in user may borrow this account.
     */
    public function canImpersonate(?User $actor, User $target): bool
    {
        if ($actor === null || $this->isImpersonating()) {
            return false;
        }

        // Nothing to see, and it would park the actor's own id pointing at
        // itself with no way to tell the two states apart.
        if ($actor->is($target)) {
            return false;
        }

        return $actor->can('manage roles');
    }

    public function start(User $target): void
    {
        $actor = Auth::user();

        abort_unless($actor instanceof User && $this->canImpersonate($actor, $target), 403);

        $originalId = $actor->getKey();

        Auth::login($target);

        // Written after the login so a session regenerated during it cannot
        // discard the only pointer back to the administrator.
        Session::put(self::SESSION_KEY, $originalId);
    }

    /**
     * Hand the session back to the administrator who started this.
     */
    public function stop(): bool
    {
        $id = Session::pull(self::SESSION_KEY);

        if (! is_numeric($id)) {
            return false;
        }

        $original = User::find((int) $id);

        if (! $original instanceof User) {
            // The account was deleted mid-session. Signing out is the only
            // honest outcome; staying would leave a borrowed identity behind.
            Auth::logout();

            return false;
        }

        Auth::login($original);

        return true;
    }
}
