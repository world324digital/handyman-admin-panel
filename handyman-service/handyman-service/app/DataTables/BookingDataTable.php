<?php

namespace App\DataTables;
use App\Traits\DataTableTrait;

use App\Models\Booking;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use App\Models\BookingStatus;

class BookingDataTable extends DataTable
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
            ->editColumn('customer_id' , function ($booking){
                return ($booking->customer_id != null && isset($booking->customer)) ? $booking->customer->display_name : '';
            })
            ->filterColumn('customer_id',function($query,$keyword){
                $query->whereHas('customer',function ($q) use($keyword){
                    $q->where('display_name','like','%'.$keyword.'%');
                });
            })
            ->editColumn('service_id' , function ($booking){
                return ($booking->service_id != null && isset($booking->service)) ? $booking->service->name : '';
            })
            ->filterColumn('service_id',function($query,$keyword){
                $query->whereHas('service',function ($q) use($keyword){
                    $q->where('name','like','%'.$keyword.'%');
                });
            })
            ->editColumn('provider_id' , function ($booking){
                return ($booking->provider_id != null && isset($booking->provider)) ? $booking->provider->display_name : '';
            })
            ->filterColumn('provider_id',function($query,$keyword){
                $query->whereHas('provider',function ($q) use($keyword){
                    $q->where('display_name','like','%'.$keyword.'%');
                });
            })
            ->editColumn('status' , function ($booking){
                return bookingstatus(BookingStatus::bookingStatus($booking->status));
            })
            ->editColumn('payment_id' , function ($booking){
                $payment_status = optional($booking->payment)->payment_status;
                if($payment_status == 'pending'){
                    $status = '<span class="badge badge-pay-pending">'.__('messages.pending').'</span>';
                }else{
                    $status = '<span class="badge badge-paid">'.__('messages.paid').'</span>';
                }
                return  $status;
            })
            ->filterColumn('payment_id',function($query,$keyword){
                $query->whereHas('payment',function ($q) use($keyword){
                    $q->where('payment_status','like',$keyword.'%');
                });
            })
            ->editColumn('total_amount' , function ($booking){
                return $booking->total_amount ? getPriceFormat($booking->total_amount) : '-';
            })
            ->addColumn('action', function($booking){
                return view('booking.action',compact('booking'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action','status','payment_id']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Booking $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Booking $model)
    {
        if(auth()->user()->hasAnyRole(['admin'])){
            $model = $model->withTrashed();
        }
        return $model->list()->newQuery()->myBooking();
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
                ->width(30),
            Column::make('service_id')
                ->title(__('messages.service')),
            Column::make('date')
                ->title(__('messages.booking_date')),
            Column::make('customer_id')
                ->title(__('messages.user')),
            Column::make('provider_id')
                ->title(__('messages.provider')),
            Column::make('status'),
            Column::make('total_amount'),
            Column::make('payment_id')
                ->title(__('messages.payment_status')),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(30)
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
        return 'Booking_' . date('YmdHis');
    }
}
