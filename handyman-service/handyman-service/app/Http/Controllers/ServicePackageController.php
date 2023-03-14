<?php

namespace App\Http\Controllers;

use App\DataTables\ServicePackageDataTable;
use Illuminate\Http\Request;
use App\Models\ServicePackage;
use App\Models\PackageServiceMapping;

class ServicePackageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(ServicePackageDataTable $dataTable)
    {
        $pageTitle = __('messages.list_form_title',['form' => __('messages.service_package')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        return $dataTable->render('servicepackage.index', compact('pageTitle','auth_user','assets'));
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

        $servicepackage = ServicePackage::find($id);   
        $pageTitle = trans('messages.update_form_title', ['form' => trans('messages.package')]);

        if ($servicepackage == null) {
            $pageTitle = trans('messages.add_button_form', ['form' => trans('messages.package')]);
            $servicepackage = new ServicePackage;
        }

        return view('servicepackage.create', compact('pageTitle', 'servicepackage', 'auth_user'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $provider_id = !empty($request->provider_id) ? $request->provider_id : \Auth::user()->id;
        $service_package = [
            'name' => $request->name,
            'description' => $request->description,
            'provider_id' => $provider_id,
            'status' => $request->status,
            'price' => $request->price,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'category_id' => $request->category_id,
            'subcategory_id' => $request->subcategory_id,
            'package_type' => $request->package_type,
        ];
        if(!$request->is('api/*')) {
            $service_package['is_featured'] = 0;
            if($request->has('is_featured')){
                $service_package['is_featured'] = 1;
            }
        }
        $result = ServicePackage::updateOrCreate(['id' => $data['id']], $service_package);
        if ($result->packageServices()->count() > 0) {
            $result->packageServices()->delete();
        }
        if (!empty($request->service_id)) {
            $service = $request->service_id;
            if(!$request->is('api/*')) {
                $service = implode(",", $request->service_id);
            }
            foreach (explode(',', $service) as $key => $value) {
                $mapping_array = [
                    'service_package_id' => $result->id,
                    'service_id' => $value
                ];
                $result->packageServices()->create($mapping_array);
            }
        }
        if ($request->is('api/*')) {
            if ($request->has('attachment_count')) {
                for ($i = 0; $i < $request->attachment_count; $i++) {
                    $attachment = "package_attachment_" . $i;
                    if ($request->$attachment != null) {
                        $file[] = $request->$attachment;
                    }
                }
                storeMediaFile($result, $file, 'package_attachment');
            }
        } else {
            storeMediaFile($result, $request->package_attachment, 'package_attachment');
        }

        $message = trans('messages.update_form', ['form' => trans('messages.package')]);
        if ($result->wasRecentlyCreated) {
            $message = trans('messages.save_form', ['form' => trans('messages.package')]);
        }
        if ($request->is('api/*')) {
            return comman_message_response($message);
        }
        return redirect(route('servicepackage.index'))->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        if (demoUserPermission()) {
            if (request()->is('api/*')) {
                return comman_message_response(__('messages.demo_permission_denied'));
            }
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $service_package = ServicePackage::find($id);
        $msg = __('messages.msg_fail_to_delete', ['item' => __('messages.package')]);

        if ($service_package != '') {
            // if($service_package->postServiceMapping()->count() > 0)
            // {
            //     $service_package->packageServices()->delete();
            // }
            $service_package->delete();
            $msg = __('messages.msg_deleted', ['name' => __('messages.package')]);
        }
        if (request()->is('api/*')) {
            return comman_custom_response(['message' => $msg, 'status' => true]);
        }
        return redirect()->back()->withSuccess($msg);
    }

    public function action(Request $request){
        $id = $request->id;
        $servicepackage = ServicePackage::where('id',$id)->first();
        $msg = __('messages.not_found_entry',['name' => __('messages.service_package')] );
        if($request->type === 'forcedelete'){
            $servicepackage->forceDelete();
            $msg = __('messages.msg_forcedelete',['name' => __('messages.service_package')] );
        }

        return comman_custom_response(['message'=> $msg , 'status' => true]);
    }
}
