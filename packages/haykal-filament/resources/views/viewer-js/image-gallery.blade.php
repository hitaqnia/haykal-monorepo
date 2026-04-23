@php
    use Filament\Support\Facades\FilamentAsset;
@endphp

<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    <div
        x-load
        x-load-css="[@js(FilamentAsset::getStyleHref('viewerjs'))]"
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('image-gallery') }}"
        x-data="imageGallery({
            id: @js($getName())
        })"
        x-ignore
    >

        <div id="{{ $getName() }}" class="grid grid-cols-1 md:grid-cols-3 gap-2">
            @foreach($getImages() as $name => $image)
                <div class="overflow-hidden rounded-lg h-64">
                    <img src="{{ $image }}"
                         alt="{{ $name }}"
                         class="cursor-pointer rounded-lg transition-transform duration-300 hover:scale-110 w-full h-full object-cover">
                </div>
            @endforeach
        </div>

    </div>

</x-dynamic-component>
