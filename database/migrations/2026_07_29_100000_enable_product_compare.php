<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Config flag that gates the compare icon on cards, the product page and
     * the header, plus the `shop.compare.index` page itself.
     */
    protected string $configCode = 'catalog.products.settings.compare_option';

    /**
     * Attributes worth putting side by side for a computer-hardware catalogue.
     *
     * `name` and `price` are deliberately absent: the compare page renders them
     * as the column header rather than as rows, and
     * `getComparableAttributesBelongsToFamily()` filters them out regardless.
     */
    protected array $comparable = [
        'sku',
        'brand',
        'specifications',
        'short_description',
        'description',
        'weight',
        'length',
        'width',
        'height',
        'color',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->setCompareOption(1);

        DB::table('attributes')->whereIn('code', $this->comparable)->update(['is_comparable' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->setCompareOption(0);

        DB::table('attributes')->whereIn('code', $this->comparable)->update(['is_comparable' => 0]);
    }

    /**
     * Write the default-scope config row, creating it when absent.
     *
     * The field defaults to enabled in `Admin/src/Config/system.php`, but an
     * explicit `0` row overrides that default, so the row has to be written
     * rather than deleted.
     */
    protected function setCompareOption(int $value): void
    {
        $exists = DB::table('core_config')
            ->where('code', $this->configCode)
            ->whereNull('channel_code')
            ->whereNull('locale_code')
            ->exists();

        if ($exists) {
            DB::table('core_config')
                ->where('code', $this->configCode)
                ->whereNull('channel_code')
                ->whereNull('locale_code')
                ->update([
                    'value'      => $value,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('core_config')->insert([
            'code'       => $this->configCode,
            'value'      => $value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
