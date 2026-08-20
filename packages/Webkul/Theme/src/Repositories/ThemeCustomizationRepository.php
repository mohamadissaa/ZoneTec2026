<?php

namespace Webkul\Theme\Repositories;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;
use Webkul\Core\Eloquent\Repository;
use Webkul\Theme\Contracts\ThemeCustomization;

class ThemeCustomizationRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return ThemeCustomization::class;
    }

    /**
     * Update the specified theme
     *
     * @param  array  $data
     * @param  int  $id
     */
    public function update($data, $id): ThemeCustomization
    {
        $locale = core()->getRequestedLocaleCode();

        if ($data['type'] == 'static_content') {
            $config = [
                'HTML.Allowed' => null,
                'HTML.ForbiddenElements' => 'script,iframe,form',
                'CSS.AllowedProperties' => null,
            ];

            $data[$locale]['options']['html'] = Purify::config($config)->clean($data[$locale]['options']['html'] ?? '');

            $data[$locale]['options']['css'] = $this->sanitizeStaticCss($data[$locale]['options']['css'] ?? '');
        }

        if (in_array($data['type'], ['image_carousel', 'services_content', 'promo_banners'])) {
            unset($data[$locale]['options']);
        }

        $theme = parent::update($data, $id);

        if (in_array($data['type'], ['image_carousel', 'services_content', 'promo_banners'])) {
            $this->uploadImage(request()->all(), $theme);
        }

        return $theme;
    }

    /**
     * Sanitize custom static-content CSS.
     *
     * CSS is not HTML, so it must not be passed through the HTML purifier - doing
     * so entity-encodes valid characters (e.g. the ">" child combinator becomes
     * "&gt;") and breaks the stylesheet. Because the value is rendered verbatim
     * inside a <style> block, the only way to break out of that context is a
     * literal "</style" sequence, so that (and null bytes) is all we neutralize.
     */
    protected function sanitizeStaticCss(?string $css): string
    {
        $css = str_replace("\0", '', (string) $css);

        return str_ireplace('</style', '<\/style', $css);
    }

    /**
     * Mass update the status of themes in the repository.
     *
     * This method updates multiple records in the database based on the provided
     * theme IDs.
     *
     * @param  int  $themeIds
     * @return int The number of records updated.
     */
    public function massUpdateStatus(array $data, array $themeIds)
    {
        return $this->model->whereIn('id', $themeIds)->update($data);
    }

    /**
     * Upload images
     *
     * @return void|string
     */
    public function uploadImage(array $data, ThemeCustomization $theme)
    {
        $locale = core()->getRequestedLocaleCode();

        if (isset($data[$locale]['deleted_sliders'])) {
            foreach ($data[$locale]['deleted_sliders'] as $slider) {
                Storage::delete(str_replace('storage/', '', $slider['image']));
            }
        }

        if (! isset($data[$locale]['options'])) {
            return;
        }

        $options = [];

        $type = $data['type'] ?? $theme->type;

        foreach ($data[$locale]['options'] as $entry) {
            if (isset($entry['service_icon'])) {
                $options['services'][] = [
                    'service_icon' => $entry['service_icon'],
                    'description' => $entry['description'],
                    'title' => $entry['title'],
                ];

                continue;
            }

            $image = $entry['image'] ?? null;

            if ($image instanceof UploadedFile) {
                try {
                    $path = 'theme/'.$theme->id.'/'.Str::random(40).'.webp';

                    $encoded = image_manager()->read($image)->encodeByExtension('webp');

                    Storage::put($path, (string) $encoded);
                } catch (\Exception $e) {
                    session()->flash('error', $e->getMessage());

                    return redirect()->back();
                }

                if ($type == 'static_content') {
                    return Storage::url($path);
                }

                $image = 'storage/'.$path;
            }

            if ($type == 'promo_banners') {
                $options['banners'][] = $this->bannerAttributes($entry, $image);
            } else {
                $options['images'][] = $this->sliderAttributes($entry, $image);
            }
        }

        $translatedModel = $theme->translate($locale);
        $translatedModel->options = $options ?? [];
        $translatedModel->theme_customization_id = $theme->id;
        $translatedModel->save();
    }

    /**
     * Normalize a single carousel slide before it is persisted.
     *
     * Beyond the image itself a slide carries the overlay content the
     * storefront renders on top of it (heading, description and call-to-action
     * button). Keys are whitelisted here so nothing unexpected posted with the
     * form ends up in the options JSON, and empty values are dropped so a slide
     * that only has an image stays as compact as it was before.
     */
    protected function sliderAttributes(array $slider, ?string $image): array
    {
        return array_filter([
            'image' => $image,
            'title' => $slider['title'] ?? null,
            'link' => $this->sanitizeUrl($slider['link'] ?? null),
            'heading' => $slider['heading'] ?? null,
            'description' => $slider['description'] ?? null,
            'button_text' => $slider['button_text'] ?? null,
            'button_link' => $this->sanitizeUrl($slider['button_link'] ?? null),
            'button_color' => $this->sanitizeColor($slider['button_color'] ?? null),
            'button_text_color' => $this->sanitizeColor($slider['button_text_color'] ?? null),
        ], fn ($value) => ! is_null($value) && $value !== '');
    }

    /**
     * Normalize a single promotional banner before it is persisted.
     *
     * A banner's surface is either a flat colour or an uploaded image; both are
     * kept so switching back and forth in the admin does not lose the other.
     */
    protected function bannerAttributes(array $banner, ?string $image): array
    {
        $surfaceType = ($banner['surface_type'] ?? 'color') === 'image' ? 'image' : 'color';

        return array_filter([
            'surface_type' => $surfaceType,
            'surface_color' => $this->sanitizeColor($banner['surface_color'] ?? null),
            'image' => $image,
            'text_color' => $this->sanitizeColor($banner['text_color'] ?? null),
            'headline' => $banner['headline'] ?? null,
            'sub' => $banner['sub'] ?? null,
            'cta' => $banner['cta'] ?? null,
            'button_color' => $this->sanitizeColor($banner['button_color'] ?? null),
            'button_text_color' => $this->sanitizeColor($banner['button_text_color'] ?? null),
            'url' => $this->sanitizeUrl($banner['url'] ?? null),
        ], fn ($value) => ! is_null($value) && $value !== '');
    }

    /**
     * Keep slide links to http(s), absolute paths and relative slugs.
     *
     * The value is rendered into an `href`, so any other scheme (`javascript:`,
     * `data:`, ...) is dropped rather than stored.
     */
    protected function sanitizeUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $hasScheme = preg_match('/^[a-z][a-z0-9+.-]*:/i', $url);

        if ($hasScheme && ! preg_match('#^https?://#i', $url)) {
            return null;
        }

        return $url;
    }

    /**
     * Only accept hex colors, as they are rendered into an inline `style`.
     */
    protected function sanitizeColor(?string $color): ?string
    {
        $color = trim((string) $color);

        return preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color)
            ? $color
            : null;
    }
}
