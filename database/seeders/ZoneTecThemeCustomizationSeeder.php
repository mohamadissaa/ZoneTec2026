<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Builds the home page block layout for the `zonetec` shop theme.
 *
 * Theme customization rows are keyed by `theme_code`, so switching a channel to
 * a newly registered theme yields an empty home page until rows exist for it.
 * This seeder is idempotent: it clears any existing `zonetec` rows first, so it
 * can be re-run after tweaking the layout.
 *
 * The hero slides, footer links and services strip are copied from whatever the
 * `default` theme already has, so the seeded imagery and CMS links carry over
 * instead of being hard-coded here.
 */
class ZoneTecThemeCustomizationSeeder extends Seeder
{
    /**
     * The theme these rows belong to.
     */
    protected const THEME = 'zonetec';

    /**
     * The theme to inherit hero/footer/services content from.
     */
    protected const SOURCE_THEME = 'default';

    /**
     * URL fragments of inherited footer links this storefront does not carry.
     *
     * `terms-of-use` is Bagisto's stock placeholder page and duplicates what
     * Terms & Conditions already covers, so it is not linked here.
     */
    protected const EXCLUDED_FOOTER_LINKS = [
        'page/terms-of-use',
    ];

    public function run(): void
    {
        $channelIds = DB::table('channels')->pluck('id');

        foreach ($channelIds as $channelId) {
            $this->seedChannel($channelId);
        }
    }

    /**
     * Departments that each get a section pair on the home page: a category
     * carousel (the parent's subcategories) followed by a product carousel
     * (products in that parent + its children). Keyed by parent slug.
     */
    protected array $departments = [
        ['slug' => 'computer-accessories', 'label' => 'Computer Accessories'],

        ['slug' => 'computers',            'label' => 'Computers & Laptops'],

        ['slug' => 'networking',           'label' => 'Networking'],
        ['slug' => 'storage',              'label' => 'Storage'],

        ['slug' => 'printing',             'label' => 'Printing'],
        ['slug' => 'audio-video',          'label' => 'Audio & Video'],
    ];

    protected function seedChannel(int $channelId): void
    {
        $this->purge($channelId);

        /**
         * `$sort` increments as sections are added so the ordering stays
         * correct however many departments are configured.
         */
        $sort = 1;

        // Full-bleed hero.
        $this->create($channelId, 'image_carousel', 'Hero Carousel', $sort++, $this->inherited($channelId, 'image_carousel'));

        // Top-level category strip.
        $this->create($channelId, 'category_carousel', 'Shop by Category', $sort++, [
            'title' => 'Shop by Category',
            'filters' => ['sort' => 'asc', 'limit' => 20, 'parent_id' => 1],
        ]);

        // Newest products across the whole catalogue.
        $this->create($channelId, 'product_carousel', 'New Arrivals', $sort++, [
            'title' => 'New Arrivals',
            'filters' => ['sort' => 'created_at-desc', 'limit' => 10],
        ]);

        /**
         * Per-department pair: subcategory carousel, then a product carousel.
         * `category_id` accepts a comma-separated list, so a parent and its
         * children are pooled. Slugs are resolved to ids at seed time so they
         * survive a catalogue rebuild; a missing slug is skipped.
         */
        foreach ($this->departments as $dept) {
            $parentId = $this->categoryId($dept['slug']);

            if (! $parentId) {
                continue;
            }

            $this->create($channelId, 'category_carousel', 'Shop '.$dept['label'], $sort++, [
                'title' => 'Shop '.$dept['label'],
                'filters' => ['sort' => 'asc', 'limit' => 20, 'parent_id' => $parentId],
            ]);

            $this->create($channelId, 'product_carousel', $dept['label'], $sort++, [
                'title' => $dept['label'],
                'filters' => ['sort' => 'created_at-desc', 'limit' => 10, 'category_id' => $this->categoryIds($dept['slug'])],
            ]);
        }

        /**
         * Copy for the two-up promo strip. It is rendered at a fixed spot in
         * the home page (after the `Computer Accessories` carousel) rather than
         * by sort order, so its `$sort` value only affects its position in the
         * admin listing.
         */
        $this->create($channelId, 'promo_banners', 'Promo Banners', $sort++, [
            'banners' => [
                [
                    'surface_type' => 'color',
                    'surface_color' => '#ffe600',
                    'text_color' => '#282828',
                    'headline' => 'Enjoy an instant 10% off all accessories!',
                    'sub' => 'Limited time offer across selected brands.',
                    'url' => url('computer-accessories'),
                ],
                [
                    'surface_type' => 'color',
                    'surface_color' => '#1754c3',
                    'text_color' => '#ffffff',
                    'headline' => 'Apply Code: (SAVE10)',
                    'sub' => 'At checkout on orders over $250.',
                    'cta' => 'Shop Now',
                    'button_color' => '#ffffff',
                    'button_text_color' => '#282828',
                    'url' => url('/checkout/onepage'),
                ],
            ],
        ]);

        // General product rails to round out the page.
        $this->create($channelId, 'product_carousel', 'Trending Now', $sort++, [
            'title' => 'Trending Now',
            'filters' => ['sort' => 'price-desc', 'limit' => 10],
        ]);

        $this->create($channelId, 'product_carousel', 'Best Sellers', $sort++, [
            'title' => 'Best Sellers',
            'filters' => ['sort' => 'name-asc', 'limit' => 10],
        ]);

        $this->create($channelId, 'footer_links', 'Footer Links', $sort++, $this->withoutLinks(
            $this->inherited($channelId, 'footer_links'),
            self::EXCLUDED_FOOTER_LINKS
        ));

        $this->create($channelId, 'services_content', 'Services Content', $sort++, $this->inherited($channelId, 'services_content'));
    }

    /**
     * Remove existing rows for this theme so the seeder can be re-run.
     */
    protected function purge(int $channelId): void
    {
        $ids = DB::table('theme_customizations')
            ->where('theme_code', self::THEME)
            ->where('channel_id', $channelId)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('theme_customization_translations')->whereIn('theme_customization_id', $ids)->delete();
        DB::table('theme_customizations')->whereIn('id', $ids)->delete();
    }

    /**
     * Insert one block plus a translation row per locale.
     *
     * `$options` is either a plain array (used for every locale) or a map of
     * locale => options when inherited content differs per locale.
     */
    protected function create(int $channelId, string $type, string $name, int $sortOrder, array $options): void
    {
        $id = DB::table('theme_customizations')->insertGetId([
            'type' => $type,
            'name' => $name,
            'sort_order' => $sortOrder,
            'status' => 1,
            'channel_id' => $channelId,
            'theme_code' => self::THEME,
        ]);

        $perLocale = $this->isLocaleMap($options)
            ? $options
            : array_fill_keys($this->locales(), $options);

        foreach ($perLocale as $locale => $localeOptions) {
            DB::table('theme_customization_translations')->insert([
                'theme_customization_id' => $id,
                'locale' => $locale,
                'options' => json_encode($localeOptions),
            ]);
        }
    }

    /**
     * Drop inherited footer links whose URL contains any of the given fragments.
     *
     * The `default` theme's footer block is inherited wholesale, so pages we do
     * not carry on this storefront have to be filtered out here — otherwise
     * re-running the seeder puts them back.
     *
     * `$perLocale` is the locale => [column => links] map returned by
     * `inherited()`; the column grouping is preserved, and a column that empties
     * out entirely is dropped so the footer does not render a blank list.
     */
    protected function withoutLinks(array $perLocale, array $urlFragments): array
    {
        foreach ($perLocale as $locale => $columns) {
            foreach ($columns as $column => $links) {
                if (! is_array($links)) {
                    continue;
                }

                $kept = array_filter($links, function ($link) use ($urlFragments) {
                    foreach ($urlFragments as $fragment) {
                        if (str_contains($link['url'] ?? '', $fragment)) {
                            return false;
                        }
                    }

                    return true;
                });

                if (empty($kept)) {
                    unset($perLocale[$locale][$column]);

                    continue;
                }

                // Re-index so the column serialises as a JSON array, not an object.
                $perLocale[$locale][$column] = array_values($kept);
            }
        }

        return $perLocale;
    }

    /**
     * Pull an existing block's options from the source theme, keyed by locale.
     *
     * Returns an empty per-locale map when the source block is absent, which
     * renders as an empty section rather than breaking the page.
     */
    protected function inherited(int $channelId, string $type): array
    {
        $source = DB::table('theme_customizations')
            ->where('theme_code', self::SOURCE_THEME)
            ->where('channel_id', $channelId)
            ->where('type', $type)
            ->first();

        if (! $source) {
            return array_fill_keys($this->locales(), []);
        }

        $translations = DB::table('theme_customization_translations')
            ->where('theme_customization_id', $source->id)
            ->get();

        $options = [];

        foreach ($translations as $translation) {
            $options[$translation->locale] = json_decode($translation->options, true) ?: [];
        }

        return $options ?: array_fill_keys($this->locales(), []);
    }

    /**
     * Resolve a parent category (by slug) and its descendants to a
     * comma-separated id list for a carousel's `category_id` filter. Falls back
     * to the parent id alone, or an empty string if the slug is missing.
     */
    protected function categoryIds(string $slug): string
    {
        $parent = $this->categoryId($slug);

        if (! $parent) {
            return '';
        }

        $children = DB::table('categories')->where('parent_id', $parent)->pluck('id')->all();

        return implode(',', array_merge([$parent], $children));
    }

    /**
     * Resolve a single category id from its slug (null if absent).
     */
    protected function categoryId(string $slug): ?int
    {
        $id = DB::table('category_translations')
            ->where('slug', $slug)
            ->value('category_id');

        return $id ? (int) $id : null;
    }

    /**
     * Distinguish a locale => options map from a bare options array.
     */
    protected function isLocaleMap(array $options): bool
    {
        if ($options === []) {
            return false;
        }

        return ! array_intersect(['filters', 'images', 'services', 'html', 'css', 'title'], array_keys($options));
    }

    /**
     * @return array<int, string>
     */
    protected function locales(): array
    {
        $locales = DB::table('locales')->pluck('code')->all();

        return $locales ?: ['en'];
    }
}
