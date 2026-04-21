<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Auth;

use Filament\Facades\Filament;
use Filament\Pages\SimplePage;
use Huwiya\Facades\Huwiya;
use Illuminate\Http\Exceptions\HttpResponseException;

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
 * The page itself never renders — it always short-circuits with an HTTP
 * redirect response. Panels that need a pre-redirect confirmation screen
 * should register a different page and trigger the redirect from a
 * button action.
 */
class HuwiyaRedirectLogin extends SimplePage
{
    protected string $view = 'filament-panels::pages.auth.login';

    public function mount(): void
    {
        throw new HttpResponseException(
            Huwiya::redirect($this->guard()),
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
