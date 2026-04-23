<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Scaffold a Filament theme entry file for a named panel.
 *
 * Creates `resources/css/filament/<panel>/theme.css` preconfigured to
 * import Filament's default theme and the Haykal base theme directly
 * from the vendor directory, plus the Tailwind 4 `@source` directives
 * for the panel's PHP and Blade paths. Because both imports reference
 * the vendor paths, theme updates flow through `composer update` — no
 * re-publishing required.
 *
 *     php artisan haykal:publish-theme management
 *
 * Existing panel themes are preserved unless `--force` is passed.
 *
 * Applications that want to fork the base theme for per-application
 * changes can publish it explicitly via:
 *
 *     php artisan vendor:publish --tag=haykal-filament-theme
 *
 * That path is rare — prefer extending the panel theme file instead
 * so the shared base continues to receive package updates.
 */
final class PublishThemeCommand extends Command
{
    protected $signature = 'haykal:publish-theme {panel : The panel identifier (kebab-case)} {--force : Overwrite an existing panel theme}';

    protected $description = 'Scaffold a Filament panel theme that references the Haykal base theme from the vendor directory.';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $panelKebab = Str::of($this->argument('panel'))->kebab()->toString();
        $panelStudly = Str::studly($panelKebab);

        $this->scaffoldPanelTheme($panelKebab, $panelStudly, (bool) $this->option('force'));

        return self::SUCCESS;
    }

    private function scaffoldPanelTheme(string $panelKebab, string $panelStudly, bool $force): void
    {
        $stub = __DIR__.'/../../stubs/panel-theme.css.stub';
        $target = resource_path("css/filament/{$panelKebab}/theme.css");

        if ($this->files->exists($target) && ! $force) {
            $this->components->warn("Panel theme already present at {$target}. Use --force to overwrite.");

            return;
        }

        $this->files->ensureDirectoryExists(dirname($target));

        $contents = str_replace(
            ['{{ panelKebab }}', '{{ panelStudly }}'],
            [$panelKebab, $panelStudly],
            $this->files->get($stub),
        );

        $this->files->put($target, $contents);

        $this->components->info("Scaffolded panel theme at {$target}.");
        $this->components->bulletList([
            'Register the panel theme in `vite.config.js` under `laravel()->input()`.',
            'Wire the theme from your panel provider with `->viteTheme(...)`.',
            'Install `@tailwindcss/typography` if it is not already a devDependency.',
            'Run `npm run build` (or `bun run build`) to compile the panel theme.',
        ]);
    }
}
