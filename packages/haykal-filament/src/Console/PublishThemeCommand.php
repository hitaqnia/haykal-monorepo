<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Scaffold a Filament theme entry file for a named panel.
 *
 * Copies the Haykal base theme alongside a per-panel theme file populated
 * with the correct Tailwind 4 `@source` directives for the panel's PHP
 * and Blade paths. Applications pass the panel's kebab-case identifier:
 *
 *     php artisan haykal:publish-theme management
 *
 * The command is idempotent: the shared `resources/css/haykal/base-theme.css`
 * is copied once (re-run with `--force` to overwrite), and each subsequent
 * invocation creates the panel entry file without touching the base copy.
 */
final class PublishThemeCommand extends Command
{
    protected $signature = 'haykal:publish-theme {panel : The panel identifier (kebab-case)} {--force : Overwrite existing files}';

    protected $description = 'Publish the Haykal base theme and scaffold a per-panel theme entry file.';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $panelKebab = Str::of($this->argument('panel'))->kebab()->toString();
        $panelStudly = Str::studly($panelKebab);
        $force = (bool) $this->option('force');

        $this->publishBaseTheme($force);
        $this->scaffoldPanelTheme($panelKebab, $panelStudly, $force);

        return self::SUCCESS;
    }

    private function publishBaseTheme(bool $force): void
    {
        $source = __DIR__.'/../../resources/css/base-theme.css';
        $target = resource_path('css/haykal/base-theme.css');

        if ($this->files->exists($target) && ! $force) {
            $this->components->info("Base theme already present at {$target}. Use --force to overwrite.");

            return;
        }

        $this->files->ensureDirectoryExists(dirname($target));
        $this->files->copy($source, $target);

        $this->components->info("Published base theme to {$target}.");
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
            'Install `@tailwindcss/typography` if it is not already a devDependency.',
            'Run `npm run build` (or `bun run build`) to compile the panel theme.',
        ]);
    }
}
