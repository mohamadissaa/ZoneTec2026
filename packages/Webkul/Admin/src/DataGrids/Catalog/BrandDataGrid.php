<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DataGrid\DataGrid;

/**
 * Brands are options on the catalogue's `brand` attribute rather than a table of
 * their own, so the storefront's brand filter and this grid can never drift
 * apart. The logo lives in the option's `swatch_value` column, which is unused
 * while the attribute stays a plain dropdown.
 */
class BrandDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $attributeId = DB::table('attributes')
            ->where('code', 'brand')
            ->value('id');

        $queryBuilder = DB::table('attribute_options')
            ->select(
                'attribute_options.id',
                'attribute_options.admin_name as name',
                'attribute_options.sort_order',
                'attribute_options.swatch_value as logo',
            )
            ->where('attribute_options.attribute_id', $attributeId);

        $this->addFilter('id', 'attribute_options.id');
        $this->addFilter('name', 'attribute_options.admin_name');
        $this->addFilter('sort_order', 'attribute_options.sort_order');

        return $queryBuilder;
    }

    /**
     * Add columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('admin::app.catalog.brands.index.datagrid.id'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'logo',
            'label' => trans('admin::app.catalog.brands.index.datagrid.logo'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable' => false,
            'closure' => function ($row) {
                return $row->logo ? Storage::url($row->logo) : null;
            },
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('admin::app.catalog.brands.index.datagrid.name'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'sort_order',
            'label' => trans('admin::app.catalog.brands.index.datagrid.sort-order'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('catalog.brands.edit')) {
            $this->addAction([
                'index' => 'edit',
                'icon' => 'icon-edit',
                'title' => trans('admin::app.catalog.brands.index.datagrid.edit'),
                'method' => 'GET',
                'url' => function ($row) {
                    return route('admin.catalog.brands.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('catalog.brands.delete')) {
            $this->addAction([
                'index' => 'delete',
                'icon' => 'icon-delete',
                'title' => trans('admin::app.catalog.brands.index.datagrid.delete'),
                'method' => 'DELETE',
                'url' => function ($row) {
                    return route('admin.catalog.brands.delete', $row->id);
                },
            ]);
        }
    }
}
