<div class="form-group">
    <label class="col-sm-2 control-label">
        <span>{{ $table->label }}</span>
    </label>
    <div class="col-sm-10">
        <table class="table table-multiple table-bordered {{ $table->classes }}" id="{{ $table->id }}"
            data-required="{{ $table->required }}">
            <thead>
                <tr class="active">
                    @foreach ($table->header as $header)
                        <th>
                            <span class="@if($header->help) has-help @endif" data-toggle="tooltip" data-placement="top" data-bs-original-title="{{ $header->help }}">
                                {{ $header->label }}
                            </span>
                            @if ($header->isRequired) <span class="text-danger">*</span> @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {!! $table->rows !!}
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="{{ $table->totalColumns }}" class="text-right">
                        <a class="btn btn-primary btn-xs d_add"><i class="fa fa-plus"></i></a>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
