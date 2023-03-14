<?php
$auth_user = authSession();
?>
{{ Form::open(['route' => ['servicepackage.destroy', $servicepackage->id], 'method' => 'delete','data--submit'=>'servicepackage'.$servicepackage->id]) }}
<div class="d-flex justify-content-end align-items-center">

    <a class="mr-2" href="{{ route('servicepackage.create',['id' => $servicepackage->id]) }}" title="{{ __('messages.update_form_title',['form' => __('messages.service_package') ]) }}"><i class="fas fa-pen text-primary"></i></a>

    <a href="{{ route('servicepackage.action',['id' => $servicepackage->id, 'type' => 'forcedelete']) }}" title="{{ __('messages.forcedelete_form_title',['form' => __('messages.service_package') ]) }}" data--submit="confirm_form" data--confirmation='true' data--ajax='true' data-title="{{ __('messages.forcedelete_form_title',['form'=>  __('messages.service_package') ]) }}" data-message='{{ __("messages.forcedelete_msg") }}' data-datatable="reload" class="mr-2">
        <i class="far fa-trash-alt text-danger"></i>
    </a>
</div>
{{ Form::close() }}