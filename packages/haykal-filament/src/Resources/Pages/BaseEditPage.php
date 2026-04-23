<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Resources\Pages;

use Filament\Resources\Pages\EditRecord;

/**
 * Edit page with read-only fallback for view-only users.
 *
 * Users who can view but not edit a record still reach this page via the
 * resource's table action; the page detects the missing `canEdit`
 * permission and renders the form in disabled mode with no save actions.
 * Authorization is enforced in `authorizeAccess()` — users with neither
 * `canEdit` nor `canView` receive a 403.
 */
class BaseEditPage extends EditRecord
{
    protected function authorizeAccess(): void
    {
        $resource = static::getResource();
        $record = $this->getRecord();

        abort_unless(
            $resource::canEdit($record) || $resource::canView($record),
            403,
        );
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        $resource = static::getResource();
        $record = $parameters['record'] ?? null;

        if ($record === null) {
            return parent::shouldRegisterNavigation($parameters);
        }

        return $resource::canEdit($record) || $resource::canView($record);
    }

    protected function fillForm(): void
    {
        if (! static::getResource()::canEdit($this->getRecord())) {
            $this->form->disabled();
        }

        parent::fillForm();
    }

    protected function getFormActions(): array
    {
        if (! static::getResource()::canEdit($this->getRecord())) {
            return [];
        }

        return parent::getFormActions();
    }
}
