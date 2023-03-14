<?php

namespace App\Http\Controllers;

use App\DataTables\BankDataTable;
use App\Models\Bank;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\BankRequest;

class BankController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(BankDataTable $dataTable)
    {
        $pageTitle = trans('messages.list_form_title', ['form' => trans('messages.bank')]);
        $auth_user = authSession();
        $assets = ['datatable'];
        return $dataTable->render('bank.index', compact('pageTitle', 'auth_user', 'assets'));
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

        $bankdata = Bank::find($id);
        // dd($bankdata);
        $pageTitle = trans('messages.update_form_title', ['form' => trans('messages.bank')]);

        if ($bankdata == null) {
            $pageTitle = trans('messages.add_button_form', ['form' => trans('messages.bank')]);
            $bankdata = new Bank();
        }

        return view('bank.create', compact('pageTitle', 'bankdata', 'auth_user'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\BankRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(BankRequest $request)
    {
        if (demoUserPermission()) {
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $data = $request->all();
        if (!$request->is('api/*')) {
            if ($request->id == null) {
                if (!isset($data['bank_attachment'])) {
                    return  redirect()->back()->withErrors(__('validation.required', ['attribute' => 'attachments']));
                }
            }
        }
        $result = Bank::updateOrCreate(['id' => $data['id']], $data);
        if ($request->is('api/*')) {
            if ($request->has('attachment_count')) {
                for ($i = 0; $i < $request->attachment_count; $i++) {
                    $attachment = "bank_attachment_" . $i;
                    if ($request->$attachment != null) {
                        $file[] = $request->$attachment;
                    }
                }
                storeMediaFile($result, $file, 'bank_attachment');
            }
        } else {
            storeMediaFile($result, $request->bank_attachment, 'bank_attachment');
        }
        $message = trans('messages.update_form', ['form' => trans('messages.bank')]);
        if ($result->wasRecentlyCreated) {
            $message = trans('messages.save_form', ['form' => trans('messages.bank')]);
        }
        return redirect(route('bank.index'))->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(BankDataTable $dataTable,$id)
    {
        $auth_user = authSession();
        $providerdata = User::with('providerbank')->where('user_type','provider')->where('id',$id)->first();
        if(empty($providerdata))
        {
            $msg = __('messages.not_found_entry',['name' => __('messages.provider')] );
            return redirect(route('provider.index'))->withError($msg);
        }
        $pageTitle = __('messages.view_form_title',['form'=> __('messages.provider')]);
        return $dataTable
        ->with('provider_id',$id)
        ->render('bank.view', compact('pageTitle' ,'providerdata' ,'auth_user' ));
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
            if(request()->is('api/*')){
                return comman_message_response( __('messages.demo_permission_denied') );
            }
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $bank = Bank::find($id);
        $msg= __('messages.msg_fail_to_delete',['item' => __('messages.bank')] );
        
        if($bank!='') {
            $bank->delete();
            $msg= __('messages.msg_deleted',['name' => __('messages.bank')] );
        }
        if(request()->is('api/*')){
            return comman_custom_response(['message'=> $msg , 'status' => true]);
        }
        return redirect()->back()->withSuccess($msg);
    }
    public function action(Request $request){
        $id = $request->id;
        $bank = bank::withTrashed()->where('id',$id)->first();
        $msg = __('messages.not_found_entry',['name' => __('messages.bank')] );
        if($request->type === 'restore'){
            $bank->restore();
            $msg = __('messages.msg_restored',['name' => __('messages.bank')] );
        }

        if($request->type === 'forcedelete'){
            $bank->forceDelete();
            $msg = __('messages.msg_forcedelete',['name' => __('messages.bank')] );
        }

        return comman_custom_response(['message'=> $msg , 'status' => true]);
    }
}
