<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Category\Models\Category;

/**
 * Replaces the demo (fashion) catalogue with the ZoneTec tech taxonomy:
 *
 *   1. wipes all demo products and every category below the root,
 *   2. builds the tech category tree (top level + subcategories),
 *   3. adds the retailer's brands as options on the native `brand` attribute.
 *
 * Idempotent — deletes what it manages before rebuilding, so it can be re-run.
 * Products are intentionally left empty; real products are imported afterwards
 * (Admin → Data Transfer → Imports, or the sample products.xml format).
 */
class ZoneTecCatalogSeeder extends Seeder
{
    /**
     * The tech category tree. Each top-level entry may carry `children`.
     * Slugs are derived from names but pinned here so they stay stable even if
     * a name is later reworded.
     */
    protected array $tree = [
        ['name' => 'Printing', 'slug' => 'printing', 'children' => [
            ['name' => 'Compatible Toners', 'slug' => 'compatible-toners'],
            ['name' => 'Ink Cartridges',    'slug' => 'ink-cartridges'],
            ['name' => 'Printers',          'slug' => 'printers'],
        ]],
        ['name' => 'Networking', 'slug' => 'networking', 'children' => [
            ['name' => 'Switches',         'slug' => 'switches'],
            ['name' => 'Routers',          'slug' => 'routers'],
            ['name' => 'Hubs',             'slug' => 'hubs'],
            ['name' => 'Patch Panels',     'slug' => 'patch-panels'],
            ['name' => 'Keystone Jacks',   'slug' => 'keystone-jacks'],
            ['name' => 'Connectors',       'slug' => 'connectors'],
            ['name' => 'Network Cabinets', 'slug' => 'network-cabinets'],
            ['name' => 'Patch Cables',     'slug' => 'patch-cables'],
        ]],
        ['name' => 'Cables & Adapters', 'slug' => 'cables-adapters', 'children' => [
            ['name' => 'HDMI Cables',   'slug' => 'hdmi-cables'],
            ['name' => 'VGA',           'slug' => 'vga'],
            ['name' => 'DisplayPort',   'slug' => 'displayport'],
            ['name' => 'USB',           'slug' => 'usb'],
            ['name' => 'USB-C',         'slug' => 'usb-c'],
            ['name' => 'Audio Cables',  'slug' => 'audio-cables'],
        ]],
        ['name' => 'Computers', 'slug' => 'computers', 'children' => [
            ['name' => 'Laptops',  'slug' => 'laptops'],
            ['name' => 'Desktops', 'slug' => 'desktops'],
            ['name' => 'Mini PCs', 'slug' => 'mini-pcs'],
        ]],
        ['name' => 'Monitors', 'slug' => 'monitors'],
        ['name' => 'Storage', 'slug' => 'storage', 'children' => [
            ['name' => 'SSDs',              'slug' => 'ssds'],
            ['name' => 'HDDs',              'slug' => 'hdds'],
            ['name' => 'USB Flash Drives',  'slug' => 'usb-flash-drives'],
            ['name' => 'Memory Cards',      'slug' => 'memory-cards'],
        ]],
        ['name' => 'Memory (RAM)', 'slug' => 'memory-ram'],
        ['name' => 'Computer Accessories', 'slug' => 'computer-accessories', 'children' => [
            ['name' => 'Mice',      'slug' => 'mice'],
            ['name' => 'Keyboards', 'slug' => 'keyboards'],
            ['name' => 'Webcams',   'slug' => 'webcams'],
            ['name' => 'Headsets',  'slug' => 'headsets'],
        ]],
        ['name' => 'Power', 'slug' => 'power', 'children' => [
            ['name' => 'UPS',            'slug' => 'ups'],
            ['name' => 'PDUs',           'slug' => 'pdus'],
            ['name' => 'Power Supplies', 'slug' => 'power-supplies'],
        ]],
        ['name' => 'Audio & Video', 'slug' => 'audio-video', 'children' => [
            ['name' => 'LED Displays',  'slug' => 'led-displays'],
            ['name' => 'Multiviewers',  'slug' => 'multiviewers'],
            ['name' => 'Speakers',      'slug' => 'speakers'],
        ]],
        ['name' => 'Tablets', 'slug' => 'tablets'],
        ['name' => 'Bags', 'slug' => 'bags', 'children' => [
            ['name' => 'Backpacks',   'slug' => 'backpacks'],
            ['name' => 'Laptop Bags', 'slug' => 'laptop-bags'],
        ]],
        ['name' => 'Office Supplies', 'slug' => 'office-supplies', 'children' => [
            ['name' => 'Paper', 'slug' => 'paper'],
            ['name' => 'Rolls', 'slug' => 'rolls'],
        ]],
    ];

    /**
     * Brands added as options on the native (filterable) `brand` attribute.
     */
    protected array $brands = [
        'HP', 'Dell', 'ASUS', 'MSI', 'TP-Link', 'D-Link', 'Ruijie', 'Tenda',
        'Mercusys', 'Logitech', 'Intel', 'WD', 'Seagate', 'Kingston',
        'Crucial', 'Lexar', 'SanDisk', 'Thermaltake',
    ];

    /**
     * Demo (fashion) brand options seeded by the installer that don't belong in
     * a tech store. Removed by name on each run.
     */
    protected array $demoBrands = ['Adidas', 'Nike', 'Elegance'];

    public function run(): void
    {
        $locales = DB::table('locales')->pluck('code')->all() ?: ['en'];

        $this->wipeProducts();
        $this->wipeCategories();
        $this->buildCategoryTree($locales);
        $this->seedBrands($locales);

        /**
         * Recompute the nested-set boundaries for the whole tree, including the
         * root, so `_lft`/`_rgt` are consistent after the rebuild.
         */
        Category::fixTree();
    }

    /**
     * Remove every product and its dependent rows. FK checks are disabled so
     * the tables can be cleared in any order; each child table has an ON DELETE
     * CASCADE, but clearing explicitly also drops flat/index rows and demo
     * reviews that would otherwise dangle.
     */
    protected function wipeProducts(): void
    {
        $tables = [
            'cart_items', 'wishlist_items', 'compare_items', 'product_reviews',
            'product_flat', 'product_categories', 'product_attribute_values',
            'product_customer_group_prices', 'product_inventories',
            'product_inventory_indices', 'product_ordered_inventories',
            'product_price_indices', 'product_images', 'product_videos',
            'product_relations', 'product_grouped_products',
            'product_bundle_options', 'product_super_attributes',
            'product_downloadable_links', 'product_downloadable_samples',
            'products',
        ];

        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Delete every category except the root (id 1). Translations and the
     * category<->filterable-attribute pivot cascade on delete.
     */
    protected function wipeCategories(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::table('category_translations')->where('category_id', '!=', 1)->delete();
        DB::table('categories')->where('id', '!=', 1)->delete();

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Create the tech tree via the nested-set model so `_lft`/`_rgt` are
     * maintained automatically.
     */
    protected function buildCategoryTree(array $locales): void
    {
        $position = 1;

        foreach ($this->tree as $node) {
            $parent = $this->createCategory($node, $locales, 1, $position++);

            $childPosition = 1;

            foreach ($node['children'] ?? [] as $child) {
                $this->createCategory($child, $locales, $parent->id, $childPosition++);
            }
        }
    }

    protected function createCategory(array $node, array $locales, int $parentId, int $position): Category
    {
        $category = new Category;
        $category->position = $position;
        $category->status = 1;
        $category->display_mode = 'products_and_description';
        $category->parent_id = $parentId;

        foreach ($locales as $locale) {
            $translation = $category->translateOrNew($locale);
            $translation->name = $node['name'];
            $translation->slug = $node['slug'];
            $translation->description = '';
            $translation->meta_title = '';
            $translation->meta_description = '';
            $translation->meta_keywords = '';
        }

        $category->save();

        return $category;
    }

    /**
     * Add each brand as an option on the `brand` attribute, skipping any that
     * already exist (case-insensitive) so re-runs don't duplicate.
     */
    protected function seedBrands(array $locales): void
    {
        $attribute = Attribute::where('code', 'brand')->first();

        if (! $attribute) {
            return;
        }

        /**
         * Drop the installer's demo fashion brands first.
         */
        AttributeOption::where('attribute_id', $attribute->id)
            ->whereIn('admin_name', $this->demoBrands)
            ->get()
            ->each->delete();

        $existing = AttributeOption::where('attribute_id', $attribute->id)
            ->get()
            ->map(fn ($option) => Str::lower($option->admin_name))
            ->all();

        $sortOrder = AttributeOption::where('attribute_id', $attribute->id)->max('sort_order') ?? 0;

        foreach ($this->brands as $brand) {
            if (in_array(Str::lower($brand), $existing, true)) {
                continue;
            }

            $option = new AttributeOption;
            $option->admin_name = $brand;
            $option->attribute_id = $attribute->id;
            $option->sort_order = ++$sortOrder;

            foreach ($locales as $locale) {
                $option->translateOrNew($locale)->label = $brand;
            }

            $option->save();
        }
    }
}
