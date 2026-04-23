<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\ViewerJs\Components;

use Closure;
use Filament\Infolists\Components\Entry;

/**
 * Infolist entry that renders a grid of images with ViewerJS lightbox support.
 *
 * Pass an `array<string, string>` of `label => url` pairs, or a closure
 * that resolves to one on render — clicking any thumbnail opens the
 * full gallery in ViewerJS.
 */
class ImageGallery extends Entry
{
    protected string $view = 'haykal-filament::viewer-js.image-gallery';

    /**
     * @var array<string, string>|Closure
     */
    protected array|Closure $images = [];

    /**
     * @param  array<string, string>|Closure  $images
     */
    public function images(array|Closure $images): static
    {
        $this->images = $images;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getImages(): array
    {
        return (array) $this->evaluate($this->images);
    }
}
