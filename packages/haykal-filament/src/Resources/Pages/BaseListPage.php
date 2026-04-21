<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Component;

/**
 * List page that enables the `custom` class hook used by the Haykal
 * base theme to restyle the tabs strip above record tables.
 */
class BaseListPage extends ListRecords
{
    public function getTabsContentComponent(): Component
    {
        return parent::getTabsContentComponent()->extraAttributes(['class' => 'custom']);
    }
}
