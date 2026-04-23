<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Auth;

use Filament\Facades\Filament;
use Filament\Pages\SimplePage;
use Huwiya\Facades\Huwiya;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

/**
 * Filament login page that delegates authentication to the Huwiya IdP.
 *
 * Every Haykal Filament panel registers this page as its login target.
 * When an unauthenticated user hits the panel, Filament routes them here;
 * on mount we construct the Huwiya OAuth redirect for the panel's guard
 * and hand control to the IdP. The IdP validates credentials, drives any
 * required MFA, and redirects back to `/huwiya/callback` where the SDK
 * exchanges the authorization code for a JWT and establishes the session.
 *
 * Implementation note: we cannot delegate to `Huwiya::redirect()` directly
 * because Livewire rebinds the `redirect` container singleton during
 * component execution, and the SDK's return-type declaration expects
 * Laravel's native `RedirectResponse`. We replicate the SDK's state-
 * persistence and URL construction inline and throw a raw
 * `HttpResponseException` so Filament short-circuits the Livewire
 * lifecycle and emits the browser redirect.
 */
class HuwiyaRedirectLogin extends SimplePage
{
    protected string $view = 'filament-panels::pages.auth.login';

    public function mount(): void
    {
        $guard = $this->guard();
        Huwiya::assertGuardIsHuwiyaWeb($guard);

        $state = Str::random(40);

        session()->put('huwiya.oauth', [
            'state' => $state,
            'guard' => $guard,
        ]);

        $query = http_build_query([
            'client_id' => config('huwiya.client_id'),
            'redirect_uri' => config('huwiya.redirect_uri'),
            'response_type' => 'code',
            'state' => $state,
        ]);

        throw new HttpResponseException(
            new RedirectResponse(rtrim((string) config('huwiya.url'), '/').'/oauth/authorize?'.$query),
        );
    }

    /**
     * Resolve the web guard the Huwiya redirect is bound to.
     *
     * Defaults to Filament's configured auth guard for the active panel,
     * which must use the `huwiya-web` driver.
     */
    protected function guard(): string
    {
        return Filament::getAuthGuard();
    }
}
