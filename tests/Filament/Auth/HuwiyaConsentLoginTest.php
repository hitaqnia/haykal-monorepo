<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Filament\Auth;

use Filament\Pages\SimplePage;
use HiTaqnia\Haykal\Filament\Auth\HuwiyaConsentLogin;
use HiTaqnia\Haykal\Tests\Filament\FilamentTestCase;

final class HuwiyaConsentLoginTest extends FilamentTestCase
{
    public function test_consent_login_is_a_filament_simple_page(): void
    {
        $this->assertTrue(is_subclass_of(HuwiyaConsentLogin::class, SimplePage::class));
    }

    public function test_consent_login_resolves_the_package_blade_view(): void
    {
        $page = new HuwiyaConsentLogin;

        $this->assertSame('haykal-filament::auth.huwiya-consent-login', $page->getView());
        $this->assertTrue(view()->exists('haykal-filament::auth.huwiya-consent-login'));
    }

    public function test_title_heading_subheading_and_button_resolve_through_package_translations_in_arabic(): void
    {
        $this->app->setLocale('ar');

        $page = new HuwiyaConsentLogin;

        $this->assertSame('تسجيل الدخول', $page->getTitle());
        $this->assertSame('تسجيل الدخول', $page->getHeading());
        $this->assertSame('تابع باستخدام حساب هويّة.', $page->getSubHeading());
        $this->assertSame('المتابعة باستخدام هويّة', $page->loginAction()->getLabel());
    }

    public function test_translations_fall_back_to_english_for_unknown_locales(): void
    {
        $this->app->setLocale('en');

        $page = new HuwiyaConsentLogin;

        $this->assertSame('Sign in', $page->getHeading());
        $this->assertSame('Continue with Huwiya', $page->loginAction()->getLabel());
    }

    public function test_kurdish_translations_ship_in_the_box(): void
    {
        $this->app->setLocale('ku');

        $page = new HuwiyaConsentLogin;

        $this->assertSame('چوونەژوورەوە', $page->getHeading());
        $this->assertSame('بەردەوامبوون بە هوویا', $page->loginAction()->getLabel());
    }
}
