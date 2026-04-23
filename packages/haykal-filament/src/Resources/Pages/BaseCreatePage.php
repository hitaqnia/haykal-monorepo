<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;

/**
 * Create page that suppresses Filament's "Create another" action.
 *
 * Most Haykal workflows return to a list or detail view after creation
 * rather than immediately creating a second record, so the extra action
 * is hidden by default. Override `canCreateAnother()` on a subclass to
 * re-enable it where the workflow warrants.
 */
class BaseCreatePage extends CreateRecord
{
    public function canCreateAnother(): bool
    {
        return false;
    }
}
