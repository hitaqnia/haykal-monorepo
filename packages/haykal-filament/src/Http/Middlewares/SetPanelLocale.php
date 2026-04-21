<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Http\Middlewares;

use Closure;
use HiTaqnia\Haykal\Filament\HaykalFilamentServiceProvider;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apply a session-persisted locale for the current Filament request.
 *
 * The panel's language switcher writes the selected locale into the
 * `current_lang` session key; this middleware reads it on every request
 * and calls `app()->setLocale()` so translations, validation messages,
 * and date formatting honor the panel user's choice. Defaults to the
 * framework locale on first visit.
 *
 * Registered under the alias `haykal.filament.locale` by
 * {@see HaykalFilamentServiceProvider}.
 */
final class SetPanelLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('current_lang');

        if (! is_string($locale) || $locale === '') {
            $locale = (string) config('app.locale');
            $request->session()->put('current_lang', $locale);
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
