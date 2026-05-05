<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Filament;

use Filament\Actions\CreateAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use HiTaqnia\Haykal\Filament\BaseFilamentServiceProvider;

final class BaseFilamentServiceProviderTest extends FilamentTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            ConcreteFilamentServiceProvider::class,
        ];
    }

    public function test_create_action_default_disables_create_another(): void
    {
        // The provider's `configureActionDefaults()` runs `CreateAction::configureUsing(...)`,
        // which fires for every newly-instantiated CreateAction. Constructing one in-test
        // is enough to observe the applied default.
        $this->assertFalse(CreateAction::make('create')->canCreateAnother());
    }

    public function test_text_column_default_placeholder_is_em_dash(): void
    {
        $this->assertSame('—', TextColumn::make('foo')->getPlaceholder());
    }

    public function test_text_entry_default_placeholder_is_em_dash(): void
    {
        $this->assertSame('—', TextEntry::make('foo')->getPlaceholder());
    }

    public function test_copyable_text_column_renders_translated_tooltip(): void
    {
        // Arabic locale exercises the Arabic copyable.php translation file
        // shipped under packages/haykal-filament/lang/ar/.
        $this->app->setLocale('ar');

        $column = TextColumn::make('value')->copyable();

        $this->assertSame('انقر للنسخ', $column->getTooltip('any-state'));
    }

    public function test_copyable_text_entry_renders_translated_tooltip_in_kurdish(): void
    {
        $this->app->setLocale('ku');

        $entry = TextEntry::make('value')->copyable();

        $this->assertSame('کلیک بکە بۆ کۆپیکردن', $entry->getTooltip('any-state'));
    }

    public function test_non_copyable_text_column_has_no_tooltip(): void
    {
        $this->assertNull(TextColumn::make('value')->getTooltip('any-state'));
    }

    public function test_subclass_can_override_an_individual_default_via_protected_hook(): void
    {
        // The whole point of the abstract base + protected `configure*`
        // hooks is that an app subclass can replace one default without
        // re-stating the others. Here we register a second provider
        // that re-enables `createAnother`; the latest `configureUsing`
        // closure wins, proving the extension point works.
        $this->app->register(OverrideActionDefaultsProvider::class);

        $this->assertTrue(CreateAction::make('create')->canCreateAnother());
    }
}

/**
 * Minimal concrete provider used by the test only — `BaseFilamentServiceProvider`
 * is abstract so it cannot be registered directly.
 */
final class ConcreteFilamentServiceProvider extends BaseFilamentServiceProvider {}

/**
 * Demonstrates the documented extension point: subclass the base provider
 * and replace one `configure*` method without re-stating the rest.
 */
final class OverrideActionDefaultsProvider extends BaseFilamentServiceProvider
{
    public function boot(): void
    {
        $this->configureActionDefaults();
    }

    protected function configureActionDefaults(): void
    {
        CreateAction::configureUsing(static function (CreateAction $action): void {
            $action->createAnother(true);
        });
    }
}
