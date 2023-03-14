<?php

namespace App\DataTables;

use App\Traits\DataTableTrait;

use App\Models\Bank;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class BankDataTable extends DataTable
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
            ->editColumn('status', function ($bank) {
                $disabled = $bank->trashed() ? 'disabled' : '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input bg-success change_status" data-type="bank_status" ' . ($bank->status ? "checked" : "") . '  ' . $disabled . ' value="' . $bank->id . '" id="' . $bank->id . '" data-id="' . $bank->id . '">
                        <label class="custom-control-label" for="' . $bank->id . '" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->editColumn('provider_id', function ($bank) {
                return ($bank->provider_id != null && isset($bank->providers)) ? $bank->providers->display_name : '-';
            })
            ->addColumn('action', function ($bank) {
                return view('bank.action', compact('bank'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'status', 'is_featured']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Bank $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Bank $model)
    {
        if (auth()->user()->hasAnyRole(['admin'])) {
            $model = $model->withTrashed();
        }
        return $model->newQuery();
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
                ->title(__('messages.no'))
                ->orderable(false),
            Column::make('provider_id')
                ->title(__('messages.provider')),
            Column::make('bank_name'),
            Column::make('branch_name'),
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
        return 'Bank_' . date('YmdHis');
    }
}
