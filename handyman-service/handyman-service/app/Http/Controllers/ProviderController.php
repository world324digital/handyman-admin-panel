<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\Booking;
use App\Models\Service;
use App\DataTables\ProviderDataTable;
use App\DataTables\ServiceDataTable;
use App\Http\Requests\UserRequest;
use App\Models\ProviderPayout;
use App\Models\ProviderSubscription;
use App\Models\PaymentGateway;

use Yajra\DataTables\DataTables;
class ProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(ProviderDataTable $dataTable, Request $request)
    {
        $pageTitle = __('messages.list_form_title',['form' => __('messages.provider')] );
        if($request->status === 'pending'){
            $pageTitle = __('messages.pending_list_form_title',['form' => __('messages.provider')] );
        }
        if($request->status === 'subscribe'){
            $pageTitle = __('messages.list_form_title',['form' => __('messages.subscribe')] );
        }
        
        $auth_user = authSession();
        $assets = ['datatable'];
        return $dataTable
                ->with('list_status',$request->status)
                ->render('provider.index', compact('pageTitle','auth_user','assets'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $id = $request->id;
        $auth_user = authSession();

        $providerdata = User::find($id);
        $pageTitle = __('messages.update_form_title',['form'=> __('messages.provider')]);
        
        if($providerdata == null){
            $pageTitle = __('messages.add_button_form',['form' => __('messages.provider')]);
            $providerdata = new User;
        }
        
        return view('provider.create', compact('pageTitle' ,'providerdata' ,'auth_user' ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        $loginuser = \Auth::user();
        if(demoUserPermission()){
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $data = $request->all();
        $id = $data['id'];
        $data['user_type'] = $data['user_type'] ?? 'provider';
        $data['is_featured'] = 0;
        
        if($request->has('is_featured')){
			$data['is_featured'] = 1;
		}

        $data['display_name'] = $data['first_name']." ".$data['last_name'];
        // Save User data...
        if($id == null){
            $data['password'] = bcrypt($data['password']);
            $user = User::create($data);
        }else{
            $user = User::findOrFail($id);
            // User data...
            // $user->removeRole($user->user_type);
            $user->fill($data)->update();
        }
        if($data['status'] == 1 && auth()->user()->hasAnyRole(['admin'])){
            try {
                \Mail::send('verification.verification_email',
                array(), function($message) use ($user)
                {
                    $message->from(env('MAIL_FROM_ADDRESS'));
                    $message->to($user->email);
                });
            } catch (\Throwable $th) {
                //throw $th;
            }
           
        }
        $user->assignRole($data['user_type']);
        storeMediaFile($user,$request->profile_image, 'profile_image');
        $message = __('messages.update_form',[ 'form' => __('messages.provider') ] );
		if($user->wasRecentlyCreated){
			$message = __('messages.save_form',[ 'form' => __('messages.provider') ] );
		}
        if($user->providerTaxMapping()->count() > 0)
        {
            $user->providerTaxMapping()->delete();
        }
        if($request->tax_id != null) {
            foreach($request->tax_id as $tax) {
                $provider_tax = [
                    'provider_id'   => $user->id,
                    'tax_id'   => $tax,
                ];
                $user->providerTaxMapping()->insert($provider_tax);
            }
        }

        if($request->is('api/*')) {
            return comman_message_response($message);
		}

		return redirect(route('provider.index'))->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ServiceDataTable $dataTable,$id)
    {
        $auth_user = authSession();
        $providerdata = User::with('providerDocument', 'booking')->where('user_type', 'provider')->where('id', $id)->first();

        $data =  Booking::where('provider_id', $id)->selectRaw(
            'COUNT(CASE WHEN status = "pending" THEN "pending" END) AS pendingStatusCount,
                                    COUNT(CASE WHEN status = "Cancelled"  THEN "Cancelled" END) AS cancelledstatuscount,
                                    COUNT(CASE WHEN status = "Completed"  THEN "Completed" END) AS Completedstatuscount,
                                    COUNT(CASE WHEN status = "Accepted"  THEN "Accepted" END) AS Acceptedstatuscount,
                                    COUNT(CASE WHEN status = "Ongoing"  THEN "Ongoing" END) AS Ongoingstatuscount'
        )->first()->toArray();


        $providerTotEarning = User::withSum('providerBooking', 'total_amount')->find($id);

        $providerPayout  = ProviderPayout::where('provider_id',$id)->sum('amount');

        $providerData = [
            'providerTotEarning' => $providerTotEarning->provider_booking_sum_total_amount,
            'providerTotWithdrableAmt' => $providerTotEarning->provider_booking_sum_total_amount,
            'providerAlreadyWithdrawAmt' => $providerPayout,
            'pendWithdrwan' => $providerTotEarning->provider_booking_sum_total_amount - $providerPayout,
        ];

        $pageTitle = __('messages.view_form_title', ['form' => __('messages.provider')]);
        return $dataTable
            ->with('provider_id', $id)
            ->render('provider.view', compact('pageTitle', 'providerdata', 'auth_user', 'data','providerTotEarning','providerPayout','providerData'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(demoUserPermission()){
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $provider = User::find($id);
        $msg= __('messages.msg_fail_to_delete',['name' => __('messages.provider')] );
        
        if($provider != '') { 
            $provider->delete();
            $msg= __('messages.msg_deleted',['name' => __('messages.provider')] );
        }
        if(request()->is('api/*')) {
            return comman_message_response($msg);
		}
        return redirect()->back()->withSuccess($msg);
    }
    public function action(Request $request){
        $id = $request->id;

        $provider  = User::withTrashed()->where('id',$id)->first();
        $msg = __('messages.not_found_entry',['name' => __('messages.provider')] );
        if($request->type == 'restore') {
            $provider->restore();
            $msg = __('messages.msg_restored',['name' => __('messages.provider')] );
        }

        if($request->type === 'forcedelete'){
            $provider->forceDelete();
            $msg = __('messages.msg_forcedelete',['name' => __('messages.provider')] );
        }
        if(request()->is('api/*')) {
            return comman_message_response($msg);
		}
        return comman_custom_response(['message'=> $msg , 'status' => true]);
    }
    public function bankDetails(ServiceDataTable $dataTable, Request $request)
    {
        $auth_user = authSession();
        $providerdata = User::with('getServiceRating')->where('user_type', 'provider')->where($request->id)->first();
        if (empty($providerdata)) {
            $msg = __('messages.not_found_entry', ['name' => __('messages.provider')]);
            return redirect(route('provider.index'))->withError($msg);
        }
        $pageTitle = __('messages.view_form_title', ['form' => __('messages.provider')]);
        return $dataTable
            ->with('provider_id', $request->id)
            ->render('provider.bank-details', compact('pageTitle', 'providerdata', 'auth_user'));
    }

    public function review(Request $request, $id)
    {
        $auth_user = authSession();
        $providerdata = User::with('getServiceRating')->where('user_type', 'provider')->where('id', $id)->first();
        $earningData = array();             
        foreach ($providerdata->getServiceRating as $bookingreview) {
           
            $booking_id = $bookingreview->id;
            $date = optional($bookingreview->booking)->date ?? '-';
            $rating = $bookingreview->rating;
            $review = $bookingreview->review;
            $earningData[] = [
                'booking_id'=>$booking_id,
                'date' => $date,
                'rating' => $rating,
                'review' => $review,  
            ];
        }
        if ($request->ajax()) {
            return Datatables::of($earningData)
                ->addIndexColumn()
                ->rawColumns(['action'])
                ->make(true);
        }
        
        if (empty($providerdata)) {
            $msg = __('messages.not_found_entry', ['name' => __('messages.provider')]);
            return redirect(route('provider.index'))->withError($msg);
        }
        $pageTitle = __('messages.view_form_title', ['form' => __('messages.provider')]);
        return view('provider.review', compact('pageTitle','earningData', 'auth_user', 'providerdata'));
    }
    public function providerDetail(Request $request)
    { 

        $tabpage = $request->tabpage;
        $pageTitle = __('messages.list_form_title', ['form' => __('messages.service')]);
        $auth_user = authSession();
        $user_id = $auth_user->id;
        $user_data = User::find($user_id);
        $earningData = array();
        $payment_data = PaymentGateway::where('type', $tabpage)->first();  
        $provideId = $request->providerId;
        $plandata = ProviderSubscription::where('user_id',$request->providerid)->get(); 
        if($request->tabpage == 'subscribe-plan'){
            $plandata = $plandata->where('plan_type','subscribe');
        }if($request->tabpage == 'unsubscribe-plan'){
            $plandata = $plandata->where('plan_type','unsubscribe');
        }
        switch ($tabpage) {                    
            case 'all-plan':
                
                if ($request->ajax() && $request->type == 'tbl') {
                 return  Datatables::of($plandata)
                   ->addIndexColumn()
                   ->rawColumns([])
                   ->make(true);
                }

               return view('providerdetail.all-plan', compact('user_data', 'earningData', 'tabpage', 'auth_user', 'payment_data','provideId'));
                break;
            case 'subscribe-plan':
                if ($request->ajax() && $request->type == 'tbl') {
                    return  Datatables::of($plandata)
                      ->addIndexColumn()
                      ->rawColumns([])
                      ->make(true);
                   }
                   return view('providerdetail.subscribe-plan', compact('user_data', 'earningData', 'tabpage', 'auth_user', 'payment_data','provideId'));
                
                break;
            case 'unsubscribe-plan':
                if ($request->ajax() && $request->type == 'tbl') {
                    return  Datatables::of($plandata)
                      ->addIndexColumn()
                      ->rawColumns([])
                      ->make(true);
                   }
                   return view('providerdetail.unsubscribe-plan', compact('user_data', 'earningData', 'tabpage', 'auth_user', 'payment_data','provideId'));
                
                break;
            default:
                $data  = view('providerdetail.' . $tabpage, compact('tabpage', 'auth_user', 'payment_data'))->render();
                break;
        }

       return response()->json($data);
    }

}
