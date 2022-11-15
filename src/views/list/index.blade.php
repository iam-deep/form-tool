@extends('form-tool::layouts.layout')

@section('content')

@php
// Called on top to set all the dependencies
// If called below getFormCss() then we will not get the css
$filter = getTableFilter($crudName ?? null);
@endphp

{!! getFormCss(); !!}

<style>
.table {
    margin-bottom:0;
}
ul.pagination {
    margin:0;
}
.box-header {
    padding-bottom:0px;
}
</style>

<div class="row">
    <div class="col-md-12">

        {{ $filter }}
        
        <div class="clearfix"></div>

        <div class="box box-primary">
            <div class="box-header">
                {{ getTableBulkAction($crudName ?? null) }}

                <div class="box-tools pull-right">
                    <input type="text" name="search" id="tableSearch" class="form-control input-sm pull-left" style="width: 200px;" value="{{ request()->query('search') }}" placeholder="Search" autocomplete="off">

                    @if (guard()::hasCreate())
                        <a href="{{ url()->current() }}/create" class="btn btn-primary btn-sm btn-flat pull-right" style="margin-left:15px;"><i class="fa fa-plus"></i> &nbsp;Add</a>
                    @endif
                </div>
            </div>
            <div class="box-body">
                {{ getTableContent($crudName ?? null) }}
            </div>
            <div class="box-footer">
                {{ getTablePagination($crudName ?? null) }}
            </div>
        </div>
    </div>
</div>

{!! getFormJs(); !!}

<script>
let oldBody = oldFooter = null;
const search = ($input) => {
    const input = $input.val().trim();
    const resultBody = $('.box-body');
    const resultFooter = $('.box-footer');

    if (! input && oldBody) {
        resultBody.html(oldBody);
        resultFooter.html(oldFooter);
        return;
    }

    // Let's only cache oldBody if this is non searched result
    if (! oldBody && @if (request()->query('search')) false @else true  @endif) {
        oldBody = resultBody.html();
        oldFooter = resultFooter.html();
    }

    $.ajax({
        // TODO: (optional) need to improve/change the request method
        url: "{{ URL::to(url()->current().'/search?' . \http_build_query(request()->except('search'))) }}",
        type: "get",
        dataType: "json",
        data: { search: input },
        beforeSend: function() {
            $("#loadingSearch").html("<i class=\"fa fa-refresh fa-spin\"></i>");
            $("#loadingSearch").show();
        },
        error: function() {
            $("#loadingSearch").html("<span style=\"color:#f00;\">Something goes wrong, Please refresh the page</span>");
            $("#loadingSearch").show();
        },
        success: function(json) {
            if (! json.isSuccess) {
                $("#loadingSearch").html("<span style=\"color:#f00;\">"+json["error"]+"</span>");
            }
            else {
                $("#loadingSearch").hide();

                resultBody.html(json.content);
                resultFooter.html(json.pagination);
            }
        }
    });
};

let searchCooldown;
$(document).on("input", "#tableSearch", function() {
    clearTimeout(searchCooldown);
    searchCooldown = setTimeout(() => {
      search($(this));
    }, 400);
});
</script>

@endsection