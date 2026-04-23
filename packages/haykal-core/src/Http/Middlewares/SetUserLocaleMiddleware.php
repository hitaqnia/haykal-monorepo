<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apply the authenticated user's stored locale preference to the current request.
 *
 * Reads the `locale` column populated by the Huwiya claim sync on login
 * and calls `app()->setLocale()` so translations, date formatting, and
 * validator messages honor the user's preference. No-op when the request
 * is unauthenticated or when the user has no locale set.
 *
 * Register under the alias `haykal.user.locale` and place after the
 * authentication middleware in the appropriate middleware group.
 */
final class SetUserLocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $locale = $user->getAttribute('locale');

            if (is_string($locale) && $locale !== '') {
                app()->setLocale($locale);
            }
        }

        return $next($request);
    }
}
