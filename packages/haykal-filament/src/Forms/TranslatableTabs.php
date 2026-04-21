<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Forms;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Arr;

/**
 * Tabbed multi-language editor for `spatie/laravel-translatable` fields.
 *
 * Renders one Filament tab per supported locale, cloning each field to
 * bind its state path to the locale (e.g., `name.en`, `name.ar`). The
 * tab icon reflects state at a glance: filled, required-but-empty, or
 * optional-and-empty.
 *
 * Usage:
 *
 *     TranslatableTabs::make('translations')
 *         ->languages(['en', 'ar', 'ku'])
 *         ->primaryLanguage('en')
 *         ->requirePrimaryLanguageOnly()
 *         ->fields([
 *             TextInput::make('name')->required(),
 *             Textarea::make('description'),
 *         ]);
 *
 * The primary language drives required-field validation by default. Use
 * `requireAllLanguages()` to enforce every locale, or pass a list of
 * locale codes to `requiredLanguages()` for a mixed policy.
 */
class TranslatableTabs extends Tabs
{
    /** @var string[] */
    protected array $languages = [];

    protected ?string $primaryLanguage = null;

    /** @var Field[] */
    protected array $fields = [];

    /**
     * `'primary'`, `'all'`, or a list of locale codes.
     *
     * @var string|string[]
     */
    protected string|array $requiredLanguages = 'primary';

    protected function setUp(): void
    {
        $this->tabs(function (): array {
            $primary = $this->getPrimaryLanguage();

            $tabs = [$this->makeLanguageTab($primary)];

            foreach (Arr::exceptValues($this->getLanguages(), $primary) as $language) {
                $tabs[] = $this->makeLanguageTab($language);
            }

            return $tabs;
        });
    }

    protected function makeLanguageTab(string $language): Tabs\Tab
    {
        return Tabs\Tab::make(__("languages.{$language}"))
            ->components(fn () => $this->getFields($language))
            ->icon(function (Get $get) use ($language) {
                if ($this->isLanguageFilled($language, $get)) {
                    return 'phosphor-check-circle-duotone';
                }

                if ($this->isLanguageRequired($language)) {
                    return 'phosphor-warning-circle-duotone';
                }

                return 'phosphor-circle-dashed-duotone';
            })
            ->iconPosition(IconPosition::After);
    }

    protected function isLanguageFilled(string $language, Get $get): bool
    {
        foreach ($this->fields as $field) {
            $value = $get("{$field->getStatePath(false)}.{$language}");

            if (filled($value)) {
                return true;
            }
        }

        return false;
    }

    // -------------------------------------------------------------
    // Configuration
    // -------------------------------------------------------------

    /**
     * @param  string[]  $languages
     */
    public function languages(array $languages): static
    {
        $this->languages = $languages;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    public function primaryLanguage(string $primaryLanguage): static
    {
        $this->primaryLanguage = $primaryLanguage;

        return $this;
    }

    public function getPrimaryLanguage(): ?string
    {
        return $this->primaryLanguage ?? $this->getLanguages()[0] ?? null;
    }

    /**
     * @param  Field[]  $fields
     */
    public function fields(array $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @param  'primary'|'all'|string[]  $languages
     */
    public function requiredLanguages(string|array $languages): static
    {
        $this->requiredLanguages = $languages;

        return $this;
    }

    public function requireAllLanguages(): static
    {
        $this->requiredLanguages = 'all';

        return $this;
    }

    public function requirePrimaryLanguageOnly(): static
    {
        $this->requiredLanguages = 'primary';

        return $this;
    }

    protected function isLanguageRequired(string $language): bool
    {
        return match (true) {
            $this->requiredLanguages === 'all' => true,
            $this->requiredLanguages === 'primary' => $language === $this->getPrimaryLanguage(),
            is_array($this->requiredLanguages) => in_array($language, $this->requiredLanguages, strict: true),
            default => false,
        };
    }

    /**
     * @return Field[]
     */
    public function getFields(string $language): array
    {
        $enforceRequired = $this->isLanguageRequired($language);
        $fields = [];

        foreach ($this->fields as $field) {
            $isRequired = $field->isRequired();

            $fields[] = $field
                ->getClone()
                ->live(onBlur: true)
                ->required($isRequired && $enforceRequired)
                ->name("{$field->getName()}.{$language}")
                ->statePath("{$field->getStatePath(false)}.{$language}");
        }

        return $fields;
    }
}
