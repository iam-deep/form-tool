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

            <form action="{{ $page->form->action }}" method="POST" enctype="multipart/form-data" class="form-horizontal">
                <div class="box-body">
                    @csrf

                    @if ($page->form->isEdit)
                        <input type="hidden" name="_method" value="PUT">
                    @else
                        <!-- this is only needed when you use run() -->
                        <!-- name="method" is different from the above edit -->
                        <input type="hidden" name="method" value="CREATE">
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
                            <button class="btn btn-success btn-flat submit">Save</button>
                            &nbsp; <a href="{{ $page->form->cancel }}" class="btn btn-default btn-flat">Cancel</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function() {

    // Setup the multiple table
    $('.table-multiple').each(function() {
        let table = $(this);
        let requiredItems = parseInt(table.attr('data-required')) || 0;
        let items = table.find('.d_block').length || 0;

        table.attr('data-index', items);

        if (items <= requiredItems) {
            table.find('.d_remove').hide();
        }
        else {
            table.find('.d_remove').show();
        }

        if (items > 1)
            table.find('.handle').show();
        else
            table.find('.handle').hide();

        let i = 1;
        table.find('.sort-value').each(function(){
            table.val(i++);
        });
    });

    // Add row into the multiple table
	$('body').on('click', '.d_add', function(e){
        e.preventDefault();

		let table = $(this).closest('.table');
        let nextIndex = parseInt(table.attr('data-index'));
        table.attr('data-index', nextIndex + 1);

		let c = template[table.attr('id')];
        c = c.replace(/{__index}/gm, nextIndex);
		table.find('tbody').append(c);

        let totalItems = table.find('.d_block').length || 0;
        let requiredItems = parseInt(table.attr('data-required')) || 0;

        if (totalItems > requiredItems)
            table.find('.d_remove').show();

        table.find('.handle').show();

        let i = 1;
        table.find('.sort-value').each(function(){
            $(this).val(i++);
        });
	});

    // Remove row from the multiple table
	$('body').on('click', '.d_remove', function(e){
        e.preventDefault();

        let table = $(this).closest('.table');
        let totalItems = table.find('.d_block').length || 0;
        let requiredItems = parseInt(table.attr('data-required')) || 0;

        let flag = false;

        if (! requiredItems  || totalItems > requiredItems)
            flag = true;

        if (flag && (! table.hasClass('confirm-delete') || confirm('Are you sure you want to delete?'))) {
            $(this).closest('.d_block').remove();

            totalItems--;
            if (requiredItems > 0 && totalItems <= requiredItems) {
                table.find('.d_remove').hide();
            }

            if (totalItems == 1) {
                table.find('.handle').hide();
            }

            let i = 1;
            table.find('.sort-value').each(function(){
                $(this).val(i++);
            });
        }
	});

    $('form').on('submit', function(){
        $('.submit').html('<i class="fa fa-spinner fa-pulse"></i> ' + $('.submit').text()).prop('disabled', true);
    });
    
    // Sort table rows
    $( ".table-sortable tbody" ).sortable({
        placeholder : "ui-state-highlight",
        handle: ".handle",
        cursor: "move",
        /*forcePlaceholderSize: true,
        forceHelperSize :true,
        start: function( event, ui ) {
            let width = ui.item.find('fonr-control').width();
            console.log(width);
            //ui.item.find('input')[0].css('width', width);
        },*/
        update  : function(event, ui)
        {
            let i = 1;
            ui.item.closest('.table-sortable').find('.sort-value').each(function(){
                $(this).val(i++);
            });
        }
    });
});
</script>

{!! $page->script !!}

@endsection