<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Filament\Http;

use HiTaqnia\Haykal\Filament\Http\Middlewares\SetPanelLocale;
use HiTaqnia\Haykal\Tests\Filament\FilamentTestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;

final class SetPanelLocaleTest extends FilamentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configured app locale acts as the seed when the session is empty.
        // Note: `app()->setLocale()` writes through to `config('app.locale')`,
        // so configure the seed *after* any pre-test locale change.
        config()->set('app.locale', 'en');
    }

    public function test_first_request_seeds_the_session_with_the_app_locale_and_applies_it(): void
    {
        $request = $this->makeRequestWithSession();

        $this->runMiddleware($request);

        $this->assertSame('en', app()->getLocale());
        $this->assertSame('en', $request->session()->get('current_lang'));
    }

    public function test_persisted_locale_in_the_session_is_applied_on_subsequent_requests(): void
    {
        $request = $this->makeRequestWithSession(['current_lang' => 'ar']);

        $this->runMiddleware($request);

        $this->assertSame('ar', app()->getLocale());
        $this->assertSame('ar', $request->session()->get('current_lang'));
    }

    public function test_empty_session_value_is_replaced_by_the_app_locale(): void
    {
        $request = $this->makeRequestWithSession(['current_lang' => '']);

        $this->runMiddleware($request);

        $this->assertSame('en', app()->getLocale());
        $this->assertSame('en', $request->session()->get('current_lang'));
    }

    public function test_non_string_session_value_is_replaced_by_the_app_locale(): void
    {
        $request = $this->makeRequestWithSession(['current_lang' => ['not', 'a', 'string']]);

        $this->runMiddleware($request);

        $this->assertSame('en', app()->getLocale());
        $this->assertSame('en', $request->session()->get('current_lang'));
    }

    /**
     * @param  array<string, mixed>  $sessionData
     */
    private function makeRequestWithSession(array $sessionData = []): Request
    {
        $request = Request::create('/admin');
        $session = new Store('test', new ArraySessionHandler(60));

        foreach ($sessionData as $key => $value) {
            $session->put($key, $value);
        }

        $request->setLaravelSession($session);

        return $request;
    }

    private function runMiddleware(Request $request): void
    {
        (new SetPanelLocale)->handle($request, fn () => new Response);
    }
}
