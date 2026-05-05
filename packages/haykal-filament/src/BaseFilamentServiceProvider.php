<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\View\Components\ModalComponent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

/**
 * Application-side base provider that applies HiTaqnia's shared
 * Filament UX defaults: locked-down modals, no "Create another"
 * action, slide-over column manager + filters, and a "Click to copy"
 * tooltip with em-dash placeholder for any copyable text column or
 * entry.
 *
 * Apps subclass this provider, register the subclass in
 * `bootstrap/providers.php`, and override the individual
 * `configure*()` hooks to relax or extend a single concern without
 * having to re-state the rest:
 *
 *     final class FilamentServiceProvider extends BaseFilamentServiceProvider
 *     {
 *         protected function configureModalDefaults(): void
 *         {
 *             // Allow modals to close on outside click in this app.
 *             ModalComponent::closedByClickingAway(true);
 *         }
 *     }
 */
abstract class BaseFilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureModalDefaults();
        $this->configureActionDefaults();
        $this->configureTableDefaults();
        $this->configureTextDefaults();
    }

    protected function configureModalDefaults(): void
    {
        ModalComponent::closedByClickingAway(false);
    }

    protected function configureActionDefaults(): void
    {
        CreateAction::configureUsing(static function (CreateAction $action): void {
            $action->createAnother(false);
        });
    }

    protected function configureTableDefaults(): void
    {
        Table::configureUsing(static function (Table $table): void {
            $table
                ->columnManagerTriggerAction(
                    static fn (Action $action) => $action
                        ->slideOver()
                        ->modalCancelAction(false)
                )
                ->filtersLayout(FiltersLayout::Modal)
                ->filtersTriggerAction(
                    static fn (Action $action) => $action
                        ->slideOver()
                        ->closeModalByClickingAway()
                        ->modalCancelAction(false)
                );
        });
    }

    /**
     * Show a "Click to copy" tooltip on any copyable text column or text entry
     * by default, plus an em-dash placeholder for empty states. Per-call
     * `->tooltip(...)` / `->placeholder(...)` calls still override these.
     */
    protected function configureTextDefaults(): void
    {
        TextColumn::configureUsing(static function (TextColumn $column): void {
            $column
                ->placeholder('—')
                ->tooltip(static fn (TextColumn $column, mixed $state): ?string => $column->isCopyable($state)
                    ? __('haykal-filament::copyable.tooltip')
                    : null);
        });

        TextEntry::configureUsing(static function (TextEntry $entry): void {
            $entry
                ->placeholder('—')
                ->tooltip(static fn (TextEntry $entry, mixed $state): ?string => $entry->isCopyable($state)
                    ? __('haykal-filament::copyable.tooltip')
                    : null);
        });
    }
}
