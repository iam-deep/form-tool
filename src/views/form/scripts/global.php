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
        } else {
            table.find('.d_remove').show();
        }

        if (items > 1) {
            table.find('.handle').show();
        } else {
            table.find('.handle').hide();
        }

        let i = 1;
        table.find('.order-value').each(function() {
            table.val(i++);
        });
    });

    // Add row into the multiple table
	$('body').on('click', '.d_add', function(e) {
        e.preventDefault();

		let table = $(this).closest('.table');
        let nextIndex = parseInt(table.attr('data-index'));
        table.attr('data-index', nextIndex + 1);

		let c = template[table.attr('id')];
        c = c.replace(/{__index}/gm, nextIndex);
		table.find('tbody').append(c);

        let totalItems = table.find('.d_block').length || 0;
        let requiredItems = parseInt(table.attr('data-required')) || 0;

        if (totalItems > requiredItems) {
            table.find('.d_remove').show();
        }

        table.find('.handle').show();

        multipleAfterAdd();

        let i = 1;
        table.find('.order-value').each(function() {
            $(this).val(i++);
        });
	});

    function multipleAfterAdd()
    {
        <?php echo getJsGroup('multiple_after_add'); ?>
    }

    // Remove row from the multiple table
	$('body').on('click', '.d_remove', function(e) {
        e.preventDefault();

        let table = $(this).closest('.table');
        let totalItems = table.find('.d_block').length || 0;
        let requiredItems = parseInt(table.attr('data-required')) || 0;

        let flag = false;

        if (! requiredItems  || totalItems > requiredItems) {
            flag = true;
        }

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
            table.find('.order-value').each(function() {
                $(this).val(i++);
            });
        }
	});

    // Form Submission
    var btnSubmit = "";
    $('.submit').click(function(evt) {
        btnSubmit = $(this).attr('value');
    });

    $('form').on('submit', function(e) {
        let form = $(this);
        let btns = form.find('.submit');

        btns.each(function() {
            if ($(this).attr('value') == btnSubmit && $(this).attr('name') != undefined) {
                form.append('<input type="hidden" name="'+ $(this).attr('name') +'" value="'+ $(this).attr('value') +'">')
            }

            $(this).attr('data-text', $(this).text()).html('<i class="fa fa-spinner fa-pulse"></i> ' + $(this).text()).prop('disabled', true);
        });
    });
    
    // Sort table rows
    $( ".table-orderable tbody" ).sortable({
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
            ui.item.closest('.table-orderable').find('.order-value').each(function() {
                $(this).val(i++);
            });
        }
    });

    // Password Toggle
    $(document).on("click", ".toggle-password", function() {
        let field = $("#" + $(this).attr("data-id"));
        let type = field.attr("type") == "password" ? "text" : "password";
        field.attr("type", type);

        if (type == "password") {
            $(this).attr("title", "Show Password");
            $(this).find("i").removeClass("fa-eye-slash").addClass("fa-eye");
        } else {
            $(this).attr("title", "Hide Password");
            $(this).find("i").removeClass("fa-eye").addClass("fa-eye-slash");
        }
    });
});

function revertSubmit() {
    let form = $('form');
    let btns = form.find('.submit');

    btns.each(function() {
        $(this).prop('disabled', false).html($(this).attr('data-text'));
    });
}
</script>