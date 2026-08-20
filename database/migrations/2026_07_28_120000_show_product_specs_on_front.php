<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Attributes that already exist but were hidden from the product page.
     */
    protected array $codes = ['weight', 'length', 'width', 'height', 'color'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('attributes')
            ->whereIn('code', $this->codes)
            ->update(['is_visible_on_front' => 1]);

        $this->createSpecificationsAttribute();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('attributes')
            ->whereIn('code', $this->codes)
            ->update(['is_visible_on_front' => 0]);

        $attributeId = DB::table('attributes')->where('code', 'specifications')->value('id');

        if ($attributeId) {
            DB::table('attribute_group_mappings')->where('attribute_id', $attributeId)->delete();

            DB::table('attribute_translations')->where('attribute_id', $attributeId)->delete();

            DB::table('attributes')->where('id', $attributeId)->delete();
        }
    }

    /**
     * Free text field for specs that don't deserve their own attribute.
     */
    protected function createSpecificationsAttribute(): void
    {
        if (DB::table('attributes')->where('code', 'specifications')->exists()) {
            return;
        }

        $now = now();

        $attributeId = DB::table('attributes')->insertGetId([
            'code' => 'specifications',
            'admin_name' => 'Specifications',
            'type' => 'textarea',
            'validation' => null,
            'position' => (int) DB::table('attributes')->max('position') + 1,
            'is_required' => 0,
            'is_unique' => 0,
            'value_per_locale' => 1,
            'value_per_channel' => 0,
            'default_value' => null,
            'is_filterable' => 0,
            'is_configurable' => 0,
            'is_user_defined' => 1,
            'is_visible_on_front' => 1,
            'is_comparable' => 0,
            'enable_wysiwyg' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (DB::table('locales')->pluck('code') as $locale) {
            DB::table('attribute_translations')->insert([
                'attribute_id' => $attributeId,
                'locale' => $locale,
                'name' => 'Specifications',
            ]);
        }

        /**
         * Show it in every family, right below the description fields.
         */
        $groups = DB::table('attribute_groups')->where('code', 'description')->get();

        foreach ($groups as $group) {
            $position = (int) DB::table('attribute_group_mappings')
                ->where('attribute_group_id', $group->id)
                ->max('position') + 1;

            DB::table('attribute_group_mappings')->insert([
                'attribute_id' => $attributeId,
                'attribute_group_id' => $group->id,
                'position' => $position,
            ]);
        }
    }
};
