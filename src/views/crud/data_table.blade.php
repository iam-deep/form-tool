@extends('form-tool::layouts.layout')

@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $title }}</h3>

                <div class="box-tools pull-right">
                    <input type="text" name="search" id="tableSearch" class="form-control input-sm pull-left" style="width: 200px;margin-right:15px;" value="" placeholder="Search" autocomplete="off">
                    <a href="{{ url()->current() }}/create" class="btn btn-primary btn-sm btn-flat pull-right"><i class="fa fa-plus"></i> &nbsp;Add</a>
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

<script>
let oldBody = oldFooter = null;
const search = ($input) => {
    const input = $input.val().trim();
    const resultBody = $('.box-body');
    const resultFooter = $('.box-footer');

    if (! input) {
        resultBody.html(oldBody);
        resultFooter.html(oldFooter);
        return;
    }

    if (! oldBody) {
        oldBody = resultBody.html();
        oldFooter = resultFooter.html();
    }

    $.ajax({
        url: "{{ URL::to(url()->current().'/search') }}",
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

@stop