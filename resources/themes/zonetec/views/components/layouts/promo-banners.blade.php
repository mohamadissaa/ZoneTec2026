@props(['options' => null])

@php
    /**
     * Full-bleed promotional banners, laid out as a two-up.
     *
     * Content comes from a `promo_banners` theme customization block (Admin →
     * Settings → Themes) and is passed in as `$options`. Like every other block
     * type, the row's sort order decides where on the home page it lands, and a
     * block with no banners renders nothing.
     */
    $banners = $options['banners'] ?? [];

    /**
     * Colours are stored hex-only, but they land in an inline `style`, so the
     * shape is re-checked here rather than trusted.
     */
    $bannerStyle = function (array $banner): string {
        $styles = [];

        if (($banner['surface_type'] ?? 'color') === 'image' && ! empty($banner['image'])) {
            $styles[] = "background-image:url('".e(asset($banner['image']))."')";
            $styles[] = 'background-size:cover';
            $styles[] = 'background-position:center';
        } elseif (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', (string) ($banner['surface_color'] ?? ''))) {
            $styles[] = 'background-color:'.$banner['surface_color'];
        }

        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', (string) ($banner['text_color'] ?? ''))) {
            $styles[] = 'color:'.$banner['text_color'];
        }

        return implode(';', $styles);
    };

    /**
     * Call-to-action colours, defaulting to the white-on-ink pill the block
     * shipped with when a banner leaves them unset.
     */
    $ctaStyle = function (array $banner): string {
        $styles = [];

        foreach (['button_color' => 'background-color', 'button_text_color' => 'color'] as $key => $property) {
            if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', (string) ($banner[$key] ?? ''))) {
                $styles[] = $property.':'.$banner[$key];
            }
        }

        return implode(';', $styles);
    };

    /**
     * The block is designed as a two-up, but the admin decides how many banners
     * there are; the classes are spelled out so Tailwind keeps them in the build.
     */
    $columns = match (count($banners)) {
        1       => 'grid-cols-1',
        3       => 'grid-cols-3',
        default => 'grid-cols-2',
    };
@endphp

@if (! empty($banners))
    <section
        class="mt-10 grid w-full {{ $columns }} max-md:mt-8 max-md:grid-cols-1 max-sm:mt-7"
        aria-label="Promotions"
    >
        @foreach ($banners as $banner)
            <a
                href="{{ $banner['url'] ?? '#' }}"
                class="flex min-h-[260px] flex-col justify-center gap-4 px-14 py-12 transition-opacity hover:opacity-95 max-1180:px-9"
                style="{{ $bannerStyle($banner) }}"
            >
                @if (! empty($banner['headline']))
                    <p class="text-[44px] font-extrabold leading-[1.05] tracking-tight max-1180:text-[34px]">
                        {{ $banner['headline'] }}
                    </p>
                @endif

                @if (! empty($banner['sub']))
                    <p class="text-base font-medium opacity-80">
                        {{ $banner['sub'] }}
                    </p>
                @endif

                @if (! empty($banner['cta']))
                    <span
                        class="mt-2 w-max bg-white px-9 py-3 text-base font-bold text-zonetec-ink"
                        style="{{ $ctaStyle($banner) }}"
                    >
                        {{ $banner['cta'] }}
                    </span>
                @endif
            </a>
        @endforeach
    </section>
@endif
