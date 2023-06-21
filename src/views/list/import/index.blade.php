@extends('form-tool::layouts.layout')

@section('content')
    <div class="row">
        <div class="col-md-6">
            <form role="form" action="" method="post" enctype="multipart/form-data" id="formImport">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Import {{ $title }}</h3>
                    </div>
                    <div class="box-body">
                        <div class="box-body">
                            <div class="form-group">
                                <label for="file">Upload CSV File <span class="text-danger">*</span></label>
                                <input name="file" type="file" id="file" accept=".csv" required />
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-flat">Upload</button>
                        <a class="pull-right" href="{{ url(config('form-tool.adminURL') . '/' . $route) }}/sample">
                            Download Sample File
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-sm-12">
            <div id="errors"></div>

            <div class="well">
                <div class="row">
                    <div class="col-sm-6">
                        <p><b>Note:</b></p>
                        <ul>
                            <li>Download the <b>sample file</b> and update the data as per the column name.</li>
                            <li>Date format should be like this: <b>25-Sep-1985</b></li>
                            <li>Save the file as .csv (Comma-Separated Values)</li>
                        </ul>
                    </div>
                    <div class="col-sm-6">
                        <p>Steps to change date format in MS Excel:</p>
                        <ul>
                            <li>Select the first date column</li>
                            <li>Press CTRL + SHIFT + Down Arrow</li>
                            <li>Right click on the selection and select "Format Cells..."</li>
                            <li>Select "Date" in the left category under "Number" tab</li>
                            <li>Select "English (US)" in Locale (location)</li>
                            <li>Scroll down to the bottom in "Type" and select format "14-Mar-2001"</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $('#formImport').on('submit', function(e){
            e.preventDefault();

            $('#errors').html('');

            let form = $(this);

            $.ajax({
                type: "POST",
                url: '{{ url()->current() }}',
                data: new FormData($(this)[0]),
                processData: false,
                contentType: false,
                dataType: "json",
                beforeSend: function() {
                    form.find('button').html('<i class="fa fa-refresh fa-spin"></i> &nbsp; Uploading...')
                        .prop('disabled', true);
                },
                error: function(json, textStatus, jqXHR) {
                    let msg = 'Something went wrong! Please refresh the page.';
                    if (json.status == 421 && json.responseJSON?.errors) {
                        msg = json.responseJSON?.message;
                        if (typeof showErrors === "function") {
                            showErrors(json.responseJSON?.errors);
                        }
                    } else if (json.responseJSON?.message) {
                        msg = json.responseJSON.message;
                    } else if (json.status == 401) {
                        msg = 'Session expired! Please login again.';
                    } else if (json.status == 403) {
                        msg = 'Access denied! You are not authorized to access the page.';
                    }

                    alert(msg);
                },
                complete: function() {
                    form.find('button').prop('disabled', false).html('Upload');
                    form.find('input[type="file"]').val('');
                },
                success: function(json, textStatus, jqXHR) {
                    if (json.status) {
                        alert(json.message);
                    }
                },
            });
        });

        function showErrors(errors) {
            let html = `<div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title text-danger">Error(s) in the uploaded file:</h3>
                </div>
                <div class="box-body">`;

            $.each(errors, function(rowIndex, row) {
                html += '<h4 class="text-danger">Error in Row no. '+ rowIndex +':</h4><ul>';

                $.each(row, function(index, errorList) {
                    $.each(errorList, function(errorIndex, error) {
                        html += '<li>'+ error +'</li>';
                    });
                });

                html += '</ul>';
            });

            html += '</div></div>';

            $('#errors').html(html);
        }
    </script>
@endsection
