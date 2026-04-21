<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Resources;

use Filament\Resources\Resource;
use Illuminate\Support\Str;

/**
 * Base Filament resource with translation-key-driven UI metadata.
 *
 * Resolves every user-facing label — model name, plural, navigation
 * label/group/parent — from a convention-based translation key so
 * applications localize panels via Laravel's translation files instead
 * of overriding individual methods on every resource.
 *
 * Translation keys follow the pattern:
 *
 *     panels/{panel-id}/resources/{resource-kebab-plural}.{key}
 *
 * For example, a `PropertyResource` on an `admin` panel reads:
 *
 *     panels/admin/resources/properties.model.singular
 *     panels/admin/resources/properties.model.plural
 *     panels/admin/resources/properties.navigation.label
 *     panels/admin/resources/properties.navigation.group   (optional)
 *     panels/admin/resources/properties.navigation.parent  (optional)
 */
abstract class BaseResource extends Resource
{
    protected static function getTranslationKeyPrefix(): string
    {
        $panelId = filament()->getCurrentPanel()?->getId() ?? 'default';
        $resourceId = Str::of(class_basename(static::class))
            ->remove('Resource')
            ->kebab()
            ->plural();

        return "panels/{$panelId}/resources/{$resourceId}";
    }

    protected static function getTranslation(string $key): string
    {
        return __(static::getTranslationKeyPrefix().'.'.$key);
    }

    /**
     * Translate the given key or fall back to `$default` when the key is
     * missing. Useful for optional navigation metadata.
     */
    protected static function getTranslationOrDefault(string $key, ?string $default = null): ?string
    {
        $fullKey = static::getTranslationKeyPrefix().'.'.$key;
        $value = __($fullKey);

        return $value === $fullKey ? $default : $value;
    }

    public static function getModelLabel(): string
    {
        return static::getTranslation('model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return static::getTranslation('model.plural');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return static::getTranslationOrDefault('navigation.group');
    }

    public static function getNavigationParentItem(): ?string
    {
        return static::getTranslationOrDefault('navigation.parent');
    }

    public static function getNavigationLabel(): string
    {
        return static::getTranslation('navigation.label');
    }
}
