<style>
.ui-state-highlight
{
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
let template = [];
</script>

<form action="{{ $action }}" method="POST" enctype="multipart/form-data">
    <div class="box-body">
        @csrf

        @if ($isEdit)
            <input type="hidden" name="_method" value="PUT">
        @endif

        {!! Blade::render($inputs) !!}

        <div id="afterForm"></div>
    </div>
    <div class="box-footer">
        <button class="btn btn-primary btn-flat">Submit</button>
    </div>
</form>

<script>
$(function(){
	$('body').on('click', '.d_add', function(e){
        e.preventDefault();

		//var b = $(this).closest('.table').find('.d_block:first');
		var table = $(this).closest('.table');
		var c = template[table.attr('id')];

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

    $('.table').each(function() {
        let requiredItems = parseInt($(this).attr('data-required')) || 0;
        let items = $(this).find('.d_block').length || 0;

        if (items <= requiredItems) {
            $(this).find('.d_remove').hide();
        }
        else {
            $(this).find('.d_remove').show();
        }

        if (items > 1)
            $(this).find('.handle').show();
        else
            $(this).find('.handle').hide();

        let i = 1;
        $(this).find('.sort-value').each(function(){
            $(this).val(i++);
        });
    });
    
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