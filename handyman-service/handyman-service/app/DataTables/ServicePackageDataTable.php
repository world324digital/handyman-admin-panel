<?php

namespace App\DataTables;

use App\Traits\DataTableTrait;

use App\Models\ServicePackage;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class ServicePackageDataTable extends DataTable
{
    use DataTableTrait;
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('status', function ($servicepackage) {
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input bg-primary change_status" data-type="coupon_status" ' . ($servicepackage->status ? "checked" : "") . ' value="' . $servicepackage->id . '" id="' . $servicepackage->id . '" data-id="' . $servicepackage->id . '">
                        <label class="custom-control-label" for="' . $servicepackage->id . '" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->editColumn('name', function ($servicepackage) {
                return '<a class="btn-link btn-link-hover"  href='.route('servicepackage.service',$servicepackage->id).'>'.$servicepackage->name.'</a>';
            })
            ->editColumn('category_id', function ($servicepackage) {
                return ($servicepackage->category_id != null && isset($servicepackage->category)) ? $servicepackage->category->name : '-';
            })
            ->editColumn('package_type', function ($servicepackage) {
                return ($servicepackage->package_type != null && isset($servicepackage->package_type)) ? ucfirst($servicepackage->package_type) : '-';
            })
            ->editColumn('price', function ($servicepackage) {
                return ($servicepackage->price != null && isset($servicepackage->price)) ? getPriceFormat($servicepackage->price) : '-';
            })
            ->addColumn('action', function ($servicepackage) {
                return view('servicepackage.action', compact('servicepackage'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'status','name']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Coupon $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(ServicePackage $model)
    {
        
        return $model->orderBy('id','desc')->newQuery()->myPackage();
    }


    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            Column::make('DT_RowIndex')
                ->searchable(false)
                ->title(__('messages.srno'))
                ->orderable(false)
                ->width(60),
            Column::make('name'),
            Column::make('category_id')
                ->title(__('messages.category')),
            Column::make('package_type')
                ->title(__('messages.package_category')),
            Column::make('price'),
            Column::make('status'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'Service Package_' . date('YmdHis');
    }
}
