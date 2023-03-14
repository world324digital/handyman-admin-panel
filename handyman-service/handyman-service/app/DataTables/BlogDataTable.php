<?php

namespace App\DataTables;

use App\Traits\DataTableTrait;

use App\Models\Blog;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class BlogDataTable extends DataTable
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
            ->editColumn('author_id' , function ($blog){
                return ($blog->author_id != null && isset($blog->author)) ? $blog->author->display_name : '';
            })
            ->filterColumn('author_id',function($query,$keyword){
                $query->whereHas('providers',function ($q) use($keyword){
                    $q->where('display_name','like','%'.$keyword.'%');
                });
            })
            ->editColumn('status', function ($blog) {
                $disabled = $blog->trashed() ? 'disabled' : '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input bg-success change_status" data-type="blog_status" ' . ($blog->status ? "checked" : "") . '  ' . $disabled . ' value="' . $blog->id . '" id="' . $blog->id . '" data-id="' . $blog->id . '">
                        <label class="custom-control-label" for="' . $blog->id . '" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->addColumn('action', function ($blog) {
                return view('blog.action', compact('blog'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'status', 'is_featured']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Blog $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Blog $model)
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
            Column::make('title'),
            Column::make('author_id')->title(__('messages.author')),
            Column::make('total_views'),
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
        return 'blog_' . date('YmdHis');
    }
}
