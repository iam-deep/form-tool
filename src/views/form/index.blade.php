@extends('form-tool::layouts.layout')

@section('content')

<style>
.ui-state-highlight {
    background-color:#ffffcc;
    border:1px dotted #ccc;
    cursor:move;
}
.handle {
    cursor:move;
}
.table tbody tr {
    width:100%;
}
</style>
<script>
// Declaring empty template is important as multiple table depends on this variable
let template = [];
</script>

{!! $page->style !!}

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $title ?? '' }}</h3>
            </div>

            <form action="{{ $page->form->action }}" method="{{ $page->form->method }}" enctype="multipart/form-data" class="form-horizontal">
                <div class="box-body">
                    @if ($page->form->method != 'GET')
                        @csrf
                    @endif

                    @if ($page->form->isEdit)
                        <input type="hidden" name="_method" value="PUT">
                    @endif

                    <div id="beforeForm"></div>

                    @foreach ($page->form->fields as $field)
                        {!! $field !!}
                    @endforeach

                    <div id="afterForm"></div>
                </div>
                <div class="box-footer footer-sticky">
                    <div class="row">
                        <div class="col-sm-8 col-sm-offset-2">
                            @if ($page->form->isButtonSubmit)
                                <button class="btn btn-success btn-flat submit">{{ $page->form->buttonSubmit }}</button>
                            @endif

                            @if ($page->form->isButtonCancel)
                                &nbsp; <a href="{{ $page->form->cancel }}" class="btn btn-default btn-flat">{{ $page->form->buttonCancel }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Quick Add Modal --}}
<form action="" method="POST" enctype="multipart/form-data" class="form-horizontal" id="quickAddForm">
    <div class="modal fade" id="quickAddModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-backdrop="false" style="z-index: 999999;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="quickAddTitle">&nbsp;</h4>
                </div>
                <div class="modal-body" id="quickAddBody">
                    <i class='fa fa-spinner fa-pulse'></i> Loading...
                </div>
                <div class="modal-footer">
                    <div class="hide" style="display: none;" id="quickSelectToUpdate" data-name="" data-is-chosen=""></div>
                    <input type="hidden" name="_option" id="quickOption" >
                    <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">Close</button>
                    <button class="btn btn-success btn-flat submit" style="display:none">Submit</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    $('#quickAddForm').on('submit', function(e) {
        e.preventDefault();

        var csrf_token = $('meta[name="csrf-token"]').attr('content');
        let form = $(this);
        let formData = new FormData(this);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            dataType: "json",
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            error: function(json) {
                if (json.responseJSON?.message) {
                    alert(json.responseJSON.message);
                } else {
                    alert("Something went wrong! Please refresh the page.");
                }
                revertSubmit();
            },
            success: function(json) {
                revertSubmit();

                if (json.status) {
                    $('#quickAddModal').modal('hide');

                    let selectId = $('#quickSelectToUpdate').attr('data-name');
                    let isChosen = $('#quickSelectToUpdate').attr('data-is-chosen');

                    $('#'+selectId).html(json.data.options);
                    if (isChosen) {
                        $('#'+selectId).trigger("chosen:updated");
                    }
                } else {
                    alert("Something went wrong! Please refresh the page.");
                }
            }
        });
    });
</script>
{{-- End Quick Add Modal --}}

@include('form-tool::form.scripts.global')

{!! $page->script !!}

@endsection
