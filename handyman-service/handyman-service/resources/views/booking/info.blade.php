{{ Form::hidden('id',$bookingdata->id) }}
@php
$extraValue = 0;
$attachments = optional($bookingdata->service)->getMedia('service_attachment');
if(!$attachments->isEmpty()){
$image = $attachments[0]->getFullUrl();
} else {
$image = getSingleMedia(optional($bookingdata->service),'service_attachment');
}
$status = App\Models\BookingStatus::where('status',1)->orderBy('sequence','ASC')->get()->pluck('label', 'value');
@endphp

<div class="card-body p-0">
    <div class="border-bottom pb-3 d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div>
            <h3 class="c1 mb-2">{{__('messages.book_id')}} {{ '#' . $bookingdata->id ?? '-'}}</h3>
            <p class="opacity-75 fz-12">
                {{__('messages.book_placed')}} {{ $bookingdata->date ?? '-'}}
            </p>
        </div>

        <div class="d-flex flex-wrap flex-xxl-nowrap gap-3" data-select2-id="select2-data-8-5c7s">
              
            <div class="w3-third">
                {{ Form::select('status',['paid'=> __('messages.paid') ,'pending'=>__('messages.pending')],optional($bookingdata->payment)->payment_status,
                    [ 'id' => 'payment_status' , 'type'=>'payment','class' =>'form-control select2js paymentStatus', 'data-id' => $bookingdata->id]) }}
            </div>
            <div class="w3-third">
                {{ Form::select('status', $status, $bookingdata->status, [
                        'class' =>'form-control select2js bookingstatus',
                        'data-id' => $bookingdata->id,
                        'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.status') ]),
                    ])
                }}

            </div> 
            <div class="w3-third">
                @if($bookingdata->handymanAdded->count() == 0)
                    @hasanyrole('admin|demo_admin|provider')
                        <a href="{{ route('booking.assign_form',['id'=> $bookingdata->id ]) }}" class="float-right btn btn-sm btn-primary loadRemoteModel"><i class="lab la-telegram-plane"></i> {{ __('messages.assign') }}</a>
                    @endhasanyrole
                @endif
            </div>
           @if($bookingdata->payment_id !== null)
            <a href="{{route('invoice_pdf',$bookingdata->id)}}" class="btn btn-primary" target="_blank">
                <i class="ri-file-text-line"></i>
                
                {{__('messages.invoice')}}
            </a>
            @endif
        </div>

    </div>
    <div class="pay-box">
        <div class="pay-method-details">
            <h4 class="mb-2">{{__('messages.payment_method')}}</h4>
            <h5 class="c1 mb-2">{{__('messages.cash_after')}}</h5>
            <p><span>{{__('messages.amount')}} : </span><strong>{{!empty($bookingdata->total_amount) ? getPriceFormat($bookingdata->total_amount): 0}}</strong></p>
        </div>
        <div class="pay-booking-details">
            <div class="row mb-2">
                <div class="col-sm-6"><span>{{__('messages.booking_status')}} :</span></div>
                <div class="col-sm-6 align-text"><span class="c1" id="booking_status__span">{{  App\Models\BookingStatus::bookingStatus($bookingdata->status)}}</span></div>
            </div>
            <div class="row mb-2">
                <div class="col-sm-6"> <span>{{__('messages.payment_status')}} : </span></div>
                <div class="col-sm-6 align-text">  <span class="text-success" id="payment_status__span">{{optional($bookingdata->payment)->payment_status}} </div>
            </div>
            <div class="row">
                <div class="col-sm-6"> 
                    <h5>
                        {{__('messages.booking_date')}} :
                    </h5>
                </div>
                <div class="col-sm-6 align-text"> 
                    <span id="service_schedule__span">{{ $bookingdata->date ?? '-'}}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="py-3 d-flex gap-3 flex-wrap customer-info-detail mb-2">
        <div class="c1-light-bg radius-10 py-3 px-4 flex-grow-1">
            <h4 class="mb-2">{{__('messages.customer_information')}}</h4>
            <h5 class="c1 mb-3">{{optional($bookingdata->customer)->display_name ?? '-'}}</h5>
            <ul class="list-info">
                <li>
                    <span class="material-icons customer-info-text">{{__('messages.phone_information')}}</span>
                    <a href="" class="customer-info-value">
                        <p class="mb-0">{{ optional($bookingdata->customer)->contact_number ?? '-' }}</p>
                    </a>
                </li>
                <li>
                    <span class="material-icons  customer-info-text">{{__('messages.address')}}</span>
                    <p class="customer-info-text">{{ optional($bookingdata->customer)->address ?? '-' }}</p>
                </li>
            </ul>
        </div>

        <div class="c1-light-bg radius-10 py-3 px-4 flex-grow-1">
            <h4 class="mb-2">{{__('messages.provider_information')}}</h4>
            <h5 class="c1 mb-3">{{optional($bookingdata->provider)->display_name ?? '-'}}</h5>
            <ul class="list-info">
                <li>
                    <span class="material-icons customer-info-text">{{__('messages.phone_information')}}</span>
                    <a href="" class="customer-info-value">
                        <p class="mb-0">{{ optional($bookingdata->provider)->contact_number ?? '-' }}</p>
                    </a>
                </li>
                <li>
                    <span class="material-icons customer-info-text">{{__('messages.address')}}</span>
                    <p class=" customer-info-value">{{ optional($bookingdata->provider)->address ?? '-' }}</p>
                </li>
            </ul>
        </div>

        <div class="c1-light-bg radius-10 py-3 px-4 flex-grow-1">
            @if(count($bookingdata->handymanAdded) > 0)
            @foreach($bookingdata->handymanAdded as $booking)
            <h4 class="mb-2">{{__('messages.handyman_information')}}</h4>
            <h5 class="c1 mb-3">{{optional($booking->handyman)->username ?? '-'}}</h5>
            <ul class="list-info">
                <li>
                    <span class="material-icons  customer-info-text">{{__('messages.phone_information')}}</span>
                    <a href="" class=" customer-info-value">
                        <p class="mb-0">{{optional($booking->handyman)->contact_number ?? '-'}}</p>
                    </a>
                </li>
                <li>
                    <span class="material-icons  customer-info-text">{{__('messages.address')}}</span>
                    <p  class=" customer-info-value">{{optional($booking->handyman)->address ?? '-'}}</p>
                </li>
            </ul>
            @endforeach
            @else
            <h4 class="mb-2">{{__('messages.handyman_information')}}</h4>
            <h5 class="mb-3">-</h5>
            <ul class="list-info">
                <li>
                    <span class="material-icons  customer-info-text">{{__('messages.phone_information')}}</span>
                    <a href="" class="customer-info-text">
                        <p>-</p>
                    </a>
                </li>
                <li>
                    <span class="material-icons  customer-info-text">{{__('messages.address')}}</span>
                    <p class="customer-info-text">-</p>
                </li>
            </ul>
            @endif
        </div>
    </div>
    @if($bookingdata->bookingExtraCharge->count() > 0 )
    <h3 class="mb-3 mt-3">{{__('messages.extra_charge')}}</h3>
    <div class="table-responsive border-bottom">
        <table class="table text-nowrap align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-lg-3">{{__('messages.title')}}</th>
                    <th>{{__('messages.price')}}</th>
                    <th>{{__('messages.quantity')}}</th>
                    <th class="text-end">{{__('messages.total_amount')}}</th>
                </tr>
            </thead>
            <tbody>
                 @foreach($bookingdata->bookingExtraCharge as $chrage)
                        @php
                            $extraValue += $chrage->price * $chrage->qty;
                        @endphp
                    <tr>
                        <td class="text-wrap ps-lg-3">
                            <div class="d-flex flex-column">
                                <a href="" class="booking-service-link fw-bold">{{$chrage->title}}</a>
                            </div>
                        </td>
                        <td>{{getPriceFormat($chrage->price)}}</td> 
                        <td>{{$chrage->qty}}</td>
                        <td class="text-end">{{getPriceFormat($chrage->price * $chrage->qty)}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    <h3 class="mb-3 mt-3">{{__('messages.booking_summery')}}</h3>
    <div class="table-responsive border-bottom">
        <table class="table text-nowrap align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-lg-3">{{__('messages.service')}}</th>
                    <th>{{__('messages.price')}}</th>
                    <th>{{__('messages.quantity')}}</th>
                    <th>{{__('messages.discount')}}</th>
                    <th class="text-end">{{__('messages.sub_total')}}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-wrap ps-lg-3">
                        <div class="d-flex flex-column">
                            <a href="" class="booking-service-link fw-bold">{{optional($bookingdata->service)->name ?? '-'}}</a>
                        </div>
                    </td>
                    <td>{{ isset($bookingdata->amount) ? getPriceFormat($bookingdata->amount) : 0 }}</td> 
                    <td>{{!empty($bookingdata->quantity) ? $bookingdata->quantity : 0}}</td>
                    <td>{{!empty($bookingdata->discount) ? $bookingdata->discount : 0}}%</td>
                    @php
                    if($bookingdata->service->type === 'fixed'){
                    $sub_total = ($bookingdata->amount) * ($bookingdata->quantity);
                    }else{
                    $sub_total = $bookingdata->amount;
                    }
                    @endphp
                    <td class="text-end">{{!empty($sub_total) ? getPriceFormat($sub_total) : 0}}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="row justify-content-end mt-3">
        <div class="col-sm-10 col-md-6 col-xl-5">
            <div class="table-responsive bk-summary-table">
                <table class="table-sm title-color align-right w-100">
                    <tbody>
                        <tr>
                            <td>{{__('messages.discount')}} (-)</td>
                            <td class="bk-value">{{!empty($bookingdata->discount) ? $bookingdata->discount : 0}}%</td>
                        </tr>
                        <tr>
                            <td>{{__('messages.coupon')}} (-)</td> 
                            @php
                            $discount = '';
                            if($bookingdata->couponAdded != null){
                                $discount = optional($bookingdata->couponAdded)->discount ?? '-';
                                $discount_type = optional($bookingdata->couponAdded)->discount_type ?? 'fixed';
                                $discount = (float)$discount;
                                if($discount_type == 'percentage'){
                                    $discount = $discount .'%';
                                }
                            }
                            @endphp
                            <td class="bk-value">{{ optional($bookingdata->couponAdded)->code ?? '0' }}{{ $discount }}%</td>
                        </tr>
                        <tr>
                            <td>{{__('messages.tax')}}</td>

                            @php
                            if($bookingdata->tax != ""){
                            foreach(json_decode($bookingdata->tax) as $key => $value){
                                if($value->type === 'percent'){
                                    $tax = $value->value;
                                    $tax_per = $sub_total * $tax / 100;
                                }else{
                                    $tax_fix = $value->value;
                                }
                            }
                            
                            $tax_amount = $tax_per ?? 0 + $tax_fix ?? 0;
                        }else{
                            $tax_amount =0;
                        }
                     
                            @endphp
                            <td class="bk-value">{{!empty($tax_amount) ? getPriceFormat($tax_amount) : 0}}</td>
                        </tr>
                        <tr class="grand-sub-total">
                            <td>{{__('messages.subtotal_vat')}}</td>
                            @php
                            $sub_total = $bookingdata->amount + $tax_amount;
                            @endphp
                            <td class="bk-value">{{!empty($sub_total) ? getPriceFormat($sub_total) : 0}}</td>
                        </tr>
                        @if($bookingdata->bookingExtraCharge->count() > 0 )
                        <tr>
                            <td>{{__('messages.extra_charge')}} (+)</td>
                            <td class="text-right">{{getPriceFormat($extraValue)}}</td>
                        </tr>
                        @endif
                        <tr class="grand-total">
                            <td><strong>{{__('messages.grand_total')}}</strong></td>
                            <td class="bk-value">
                                @php
                                $coupon_discount = $sub_total * (float)$discount / 100;
                                $discount = $sub_total * $bookingdata->discount / 100;
                                $total_amount = $sub_total - ($coupon_discount + $discount);
                                @endphp
                                <h3>{{!empty($total_amount) ? getPriceFormat($total_amount + $extraValue) : 0}}</h3>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>

$(document).on('change','.bookingstatus', function() {

    var status = $(this).val();

    var id = $(this).attr('data-id');
    $.ajax({
        type: "POST",
        dataType: "json",
        url: "{{ route('bookingStatus.update') }}",
        data: { 'status': status, 'bookingId': id  },
        success: function(data){
        }
    });
})

$(document).on('change','.paymentStatus', function() {

var status = $(this).val();

var id = $(this).attr('data-id');
$.ajax({
    type: "POST",
    dataType: "json",
    url: "{{ route('bookingStatus.update') }}",
    data: { 'status': status, 'bookingId': id  },
    success: function(data){
    }
});
})
</script>
