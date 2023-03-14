<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog;
use App\DataTables\BlogDataTable;
use App\Http\Requests\BlogRequest;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(BlogDataTable $dataTable)
    {
        $pageTitle = trans('messages.list_form_title',['form' => trans('messages.blog')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        return $dataTable->render('blog.index', compact('pageTitle','auth_user','assets'));
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

        $blogdata = Blog::find($id);
        $pageTitle = trans('messages.update_form_title',['form'=>trans('messages.blog')]);
        
        if($blogdata == null){
            $pageTitle = trans('messages.add_button_form',['form' => trans('messages.blog')]);
            $blogdata = new Blog;
        }
        
        return view('blog.create', compact('pageTitle' ,'blogdata' ,'auth_user' ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(BlogRequest $request)
    {
        if(demoUserPermission()){
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $data = $request->all();
        $data['author_id'] = !empty($request->author_id) ? $request->author_id : auth()->user()->id;
        $data['is_featured'] = 0;
        if($request->has('is_featured')){
			$data['is_featured'] = 1;
		}
        $result = blog::updateOrCreate(['id' => $data['id'] ],$data);

        if($request->is('api/*')){
			if($request->has('attachment_count')) {
				for($i = 0 ; $i < $request->attachment_count ; $i++){
					$attachment = "blog_attachment_".$i;
					if($request->$attachment != null){
						$file[] = $request->$attachment;
					}
				}
				storeMediaFile($result,$file, 'blog_attachment');
			}
		}else{
			storeMediaFile($result,$request->blog_attachment, 'blog_attachment');
		}

        $message = trans('messages.update_form',['form' => trans('messages.blog')]);
        if($result->wasRecentlyCreated){
            $message = trans('messages.save_form',['form' => trans('messages.blog')]);
        }
        if($request->is('api/*')) {
            return comman_message_response($message);
		}
        return redirect(route('blog.index'))->withSuccess($message);        
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
        if(demoUserPermission()){
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $blog = Blog::find($id);
        $msg= __('messages.msg_fail_to_delete',['name' => __('messages.blog')] );
        
        if($blog!='') { 

            $blog->delete();
            $msg= __('messages.msg_deleted',['name' => __('messages.blog')] );
        }
        if(request()->is('api/*')) {
            return comman_message_response($msg);
		}
        return redirect()->back()->withSuccess($msg);
    }   
    public function action(Request $request){
        $id = $request->id;

        $blog  = Blog::withTrashed()->where('id',$id)->first();
        $msg = __('messages.not_found_entry',['name' => __('messages.blog')] );
        if($request->type == 'restore') {
            $blog->restore();
            $msg = __('messages.msg_restored',['name' => __('messages.blog')] );
        }
        if($request->type === 'forcedelete'){
            $blog->forceDelete();
            $msg = __('messages.msg_forcedelete',['name' => __('messages.blog')] );
        }
        if(request()->is('api/*')){
            return comman_message_response($msg);
		}
        return comman_custom_response(['message'=> $msg , 'status' => true]);
    }
    
}
