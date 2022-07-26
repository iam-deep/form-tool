@extends('form-tool::layouts.layout')

@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $title }}</h3>
                <a href="{{ url()->current() }}/create" class="btn btn-primary btn-sm btn-flat pull-right"><i class="fa fa-plus"></i> &nbsp;Add</a>
            </div>
            <div class="box-body">
                {{ getTableContent() }}
            </div>
            <div class="box-footer">
                {{ getTablePagination() }}
            </div>
        </div>
    </div>
</div>

@stop