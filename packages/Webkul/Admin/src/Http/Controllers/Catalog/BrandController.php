<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Catalog\BrandDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Contracts\AttributeOption;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Traits\Sanitizer;
use Webkul\Product\Repositories\ProductAttributeValueRepository;

/**
 * Brands are options on the catalogue's `brand` attribute, not a table of their
 * own. That keeps one list behind both this screen and the storefront's brand
 * filter — a brand added here is immediately selectable on a product and
 * filterable in the shop.
 *
 * The logo is kept in the option's `swatch_value` column. That column is only
 * read by the storefront for swatch-type attributes, so while `brand` stays a
 * plain dropdown it is free for this, and Bagisto's own attribute screen never
 * submits it — editing the Brand attribute there cannot wipe the logos.
 */
class BrandController extends Controller
{
    use Sanitizer;

    /**
     * Attribute code the brand options hang off.
     */
    const ATTRIBUTE_CODE = 'brand';

    /**
     * Directory (on the public disk) logos are stored in.
     */
    const LOGO_DIRECTORY = 'brands';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected AttributeOptionRepository $attributeOptionRepository,
        protected ProductAttributeValueRepository $productAttributeValueRepository,
        protected LocaleRepository $localeRepository
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View|JsonResponse
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(BrandDataGrid::class)->process();
        }

        return view('admin::catalog.brands.index', [
            'attribute' => $this->attribute(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): JsonResponse
    {
        if (! $attribute = $this->attribute()) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.brands.index.missing-attribute'),
            ], 400);
        }

        $this->validate(request(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attribute_options', 'admin_name')->where('attribute_id', $attribute->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'logo' => ['array'],
            'logo.*' => ['image', 'extensions:jpeg,jpg,png,svg,webp'],
        ]);

        $option = $this->attributeOptionRepository->create(array_merge([
            'attribute_id' => $attribute->id,
            'admin_name' => request()->input('name'),
            'sort_order' => request()->input('sort_order') ?? $this->nextSortOrder($attribute->id),
        ], $this->labels(request()->input('name'))));

        $this->syncLogo($option);

        return new JsonResponse([
            'message' => trans('admin::app.catalog.brands.index.create-success'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): JsonResponse
    {
        $option = $this->attributeOptionRepository->findOrFail($id);

        return new JsonResponse([
            'data' => [
                'id' => $option->id,
                'name' => $option->admin_name,
                'sort_order' => $option->sort_order,
                'logo_url' => $option->swatch_value ? Storage::url($option->swatch_value) : null,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(int $id): JsonResponse
    {
        $attribute = $this->attribute();

        $option = $this->attributeOptionRepository->findOrFail($id);

        $this->validate(request(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attribute_options', 'admin_name')
                    ->where('attribute_id', $attribute?->id)
                    ->ignore($option->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'logo' => ['array'],
            'logo.*' => ['image', 'extensions:jpeg,jpg,png,svg,webp'],
        ]);

        $option = $this->attributeOptionRepository->update(array_merge([
            'admin_name' => request()->input('name'),
            'sort_order' => request()->input('sort_order') ?? $option->sort_order,
        ], $this->labels(request()->input('name'))), $option->id);

        $this->syncLogo($option);

        return new JsonResponse([
            'message' => trans('admin::app.catalog.brands.index.update-success'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $option = $this->attributeOptionRepository->findOrFail($id);

        /**
         * Nothing cleans up a product's brand value when its option disappears,
         * so an assigned brand is kept rather than left dangling on products.
         */
        $assigned = $this->productAttributeValueRepository->findWhere([
            'attribute_id' => $option->attribute_id,
            'integer_value' => $option->id,
        ])->count();

        if ($assigned) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.brands.index.in-use-error'),
            ], 400);
        }

        try {
            $logo = $option->swatch_value;

            $this->attributeOptionRepository->delete($option->id);

            $this->deleteLogo($logo);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.brands.index.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.brands.index.delete-failed'),
            ], 500);
        }
    }

    /**
     * The `brand` attribute the options belong to, or null when the catalogue
     * has no such attribute.
     */
    protected function attribute()
    {
        return $this->attributeRepository->findOneWhere(['code' => self::ATTRIBUTE_CODE]);
    }

    /**
     * Position a new brand at the end of the strip, so leaving the field blank
     * appends rather than jumping the queue with a 0.
     */
    protected function nextSortOrder(int $attributeId): int
    {
        return (int) $this->attributeOptionRepository
            ->getModel()
            ->where('attribute_id', $attributeId)
            ->max('sort_order') + 1;
    }

    /**
     * Translated labels for every locale, keyed the way the translatable model
     * expects them.
     *
     * The storefront renders an option's translated label, so a brand saved
     * here has to carry one for each locale or it would fall back to the raw
     * admin name. Brand names are proper nouns, so the same value is used
     * throughout; per-locale wording can still be set on the attribute screen,
     * but saving here overwrites it.
     */
    protected function labels(string $name): array
    {
        return $this->localeRepository->all()
            ->mapWithKeys(fn ($locale) => [$locale->code => ['label' => $name]])
            ->all();
    }

    /**
     * Apply the submitted logo to an option.
     *
     * The media component omits the field entirely once its image is removed,
     * which is what distinguishes "logo cleared" from "logo untouched": a
     * present-but-unchanged logo is posted back as a plain string.
     */
    protected function syncLogo(AttributeOption $option): void
    {
        $logos = request()->file('logo');

        if (empty($logos)) {
            if (! request()->has('logo')) {
                $this->deleteLogo($option->swatch_value);

                $this->attributeOptionRepository->update(['swatch_value' => null], $option->id);
            }

            return;
        }

        $logo = is_array($logos) ? reset($logos) : $logos;

        if (! $logo instanceof UploadedFile) {
            return;
        }

        $this->deleteLogo($option->swatch_value);

        $path = $logo->storeAs(
            self::LOGO_DIRECTORY,
            Str::slug($option->admin_name).'-'.$option->id.'.'.$logo->getClientOriginalExtension()
        );

        $this->sanitizeSVG($path, $logo->getMimeType());

        $this->attributeOptionRepository->update(['swatch_value' => $path], $option->id);
    }

    /**
     * Drop a stored logo, ignoring paths that point elsewhere so a swatch set
     * from the attribute screen is never removed by this screen.
     */
    protected function deleteLogo(?string $path): void
    {
        if (
            $path
            && Str::startsWith($path, self::LOGO_DIRECTORY.'/')
        ) {
            Storage::delete($path);
        }
    }
}
