<?php

namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'sku' => $this->sku,
            'name' => $this->name,
            'price' => $this->price,
            'formatted_price' => core()->formatPrice($this->price),
            'images' => $this->images,
            'inventories' => $this->inventories,
            'qty_available' => $this->resolveAvailableQuantity(),
            'is_options_required' => ! $this->getTypeInstance()->canBeAddedToCartWithoutOptions(),
            'is_saleable' => $this->getTypeInstance()->isSaleable(),
        ];
    }

    /**
     * Resolves the quantity available for sale. For stockable types this is the
     * sum of inventory quantities. Non-stockable types return null so the UI can
     * treat them as "unlimited / N/A" rather than displaying a misleading 0.
     */
    protected function resolveAvailableQuantity(): ?int
    {
        if ($this->getTypeInstance()->isStockable()) {
            return (int) $this->inventories->sum('qty');
        }

        return null;
    }
}
