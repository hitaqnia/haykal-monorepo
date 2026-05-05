<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Fixtures;

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Livewire\Component;

/**
 * Minimal Livewire host used by tests that need a real `HasSchemas`
 * instance to back a `Schema` (so closures can resolve `$get`, etc.).
 *
 * Filament's `Schema` requires a Livewire component implementing
 * `HasSchemas`; an in-memory schema with no host throws as soon as
 * any state-related code runs. This fixture is the smallest possible
 * thing that satisfies that contract.
 */
final class TestSchemaLivewire extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function render(): string
    {
        return '<div></div>';
    }
}
