@extends('form-tool::layouts.layout')

@section('content')

{!! $page->style !!}

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
.loader {
    position: absolute;
    top: 50px;
    width: 100%;
    height: 95%;
    text-align: center;
    background-color: #efefef55;
}
.loader i {
    margin-top: 25px;
}
</style>

<div class="row">
    <div class="col-md-12">

        {{ $page->filter }}

        @if ($page->hasCreate)
            <a href="{{ $page->createLink }}" class="btn btn-success btn-sm btn-flat pull-right" style="margin-left:15px;"><i class="fa fa-plus"></i> &nbsp;Add</a>
        @endif
        
        <div class="clearfix"></div>

        <div class="box box-primary">
            <div class="box-header">
                {{ $page->bulkAction }}

                <div class="box-tools pull-right">
                    <input type="text" name="search" id="tableSearch" class="form-control input-sm pull-left" style="width: 200px;" value="{{ $page->searchQuery }}" placeholder="Search" autocomplete="off">
                </div>
            </div>
            <div class="box-body">
                {{ $page->tableContent }}
            </div>
            <div class="box-footer">
                {{ $page->pagination }}
            </div>
            <div class="loader" style="display:none;">
                <i class="fa fa-refresh fa-spin fa-3x"></i>
                <span class="sr-only">Loading...</span>
            </div>
        </div>
    </div>
</div>

{!! $page->script !!}

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
    if (! oldBody && @if ($page->searchQuery) false @else true  @endif) {
        oldBody = resultBody.html();
        oldFooter = resultFooter.html();
    }

    $('.loader').show();

    $.ajax({
        // TODO: (optional) need to improve/change the request method
        url: "{!! $page->searchLink !!}",
        type: "get",
        dataType: "json",
        data: { search: input },
        beforeSend: function() {
            $("#loadingSearch").html("<i class=\"fa fa-refresh fa-spin\"></i>");
            $("#loadingSearch").show();
        },
        error: function(json) {
            let msg = 'Something went wrong! Please refresh the page.';
            if (json.responseJSON.message) {
                msg = json.responseJSON.message;
            }
            $("#loadingSearch").html("<span style=\"color:#f00;\">"+ msg +"</span>");
            $("#loadingSearch").show();
            $('.loader').hide();
        },
        success: function(json) {
            if (! json.isSuccess) {
                $("#loadingSearch").html("<span style=\"color:#f00;\">"+json["message"]+"</span>");
            }
            else {
                $("#loadingSearch").hide();

                resultBody.html(json.content);
                resultFooter.html(json.pagination);
                $('.loader').hide();
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