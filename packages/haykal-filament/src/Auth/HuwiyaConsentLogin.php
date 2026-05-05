<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Auth;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\SimplePage;
use Huwiya\Facades\Huwiya;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

/**
 * Consent-style login page for every Haykal Filament panel.
 *
 * Renders a single-button page; the click handler builds the Huwiya OAuth
 * authorize URL (replicating the SDK's state-persistence) and tells
 * Livewire to redirect the browser to the IdP. Throwing an
 * `HttpResponseException` does not work here because Livewire actions
 * intercept thrown responses — the browser must receive an explicit
 * redirect emitted by `$this->redirect(...)`.
 *
 * Title, heading, sub-heading, and button label resolve through the
 * package translation namespace `haykal-filament::auth.login.*`. Apps
 * can override individual strings either by switching the active locale
 * or by publishing the translations and editing them locally; subclass
 * this page and override the `getTitle` / `getHeading` / `getSubHeading`
 * / `loginAction` methods to swap the texts entirely.
 */
class HuwiyaConsentLogin extends SimplePage
{
    protected string $view = 'haykal-filament::auth.huwiya-consent-login';

    public function getTitle(): string|Htmlable
    {
        return __('haykal-filament::auth.login.title');
    }

    public function getHeading(): string|Htmlable
    {
        return __('haykal-filament::auth.login.heading');
    }

    public function getSubHeading(): string|Htmlable|null
    {
        return __('haykal-filament::auth.login.subheading');
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->label(__('haykal-filament::auth.login.button'))
            ->color('primary')
            ->size('lg')
            ->action(fn () => $this->redirect($this->buildHuwiyaAuthorizeUrl()));
    }

    protected function buildHuwiyaAuthorizeUrl(): string
    {
        $guard = Filament::getAuthGuard();
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

        return rtrim((string) config('huwiya.url'), '/').'/oauth/authorize?'.$query;
    }
}
