<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServicePackage;
use App\Http\Resources\API\ServicePackageResource;


class ServicePackageController extends Controller
{
    public function getServicePackageList(Request $request){
        $provider_id = !empty($request->provider_id) ?$request->provider_id :auth()->user()->id;
        $service_packages = ServicePackage::where('status',1)->where('provider_id',$provider_id);
        if($request->has('is_featured')){
            $service_packages->where('is_featured',$request->is_featured);
        }

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page)){
                $per_page = $request->per_page;
            }
            if($request->per_page === 'all' ){
                $per_page = $service_packages->count();
            }
        }

        $service_packages = $service_packages->orderBy('id','desc')->paginate($per_page);
        $items = ServicePackageResource::collection($service_packages);

        $response = [
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
                'next_page' => $items->nextPageUrl(),
                'previous_page' => $items->previousPageUrl(),
            ],
            'data' => $items,
        ];
        
        return comman_custom_response($response);
    }

}