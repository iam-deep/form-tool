<div class="form-group @if ($field->error) has-error @endif">
    @if ($field->type == 'editor')
        <label for="{{ $field->dbField }}" class="col-sm-2 control-label" data-toggle="tooltip" data-placement="right"
            title="{{ $field->help }}">
            <span class="@if ($field->help) has-help @endif">{{ $field->label }}</span>
            @if ($field->isRequired)
                <span class="text-danger">*</span>
            @endif
        </label>

        <div class="col-sm-10">
            {!! $field->input !!}
            
            @if ($field->error)
                <p class="help-block">{{ $field->error }}</p>
            @endif
        </div>
    @else
        <label for="{{ $field->dbField }}" class="col-sm-2 control-label" data-toggle="tooltip" data-placement="right"
            title="{{ $field->help }}">
            <span class="@if ($field->help) has-help @endif">{{ $field->label }}</span>
            @if ($field->isRequired)
                <span class="text-danger">*</span>
            @endif
        </label>

        <div class="col-sm-6">
            {!! $field->input !!}
        </div>
        <div class="col-sm-4">
            @if ($field->error)
                <p class="help-block">{{ $field->error }}</p>
            @endif
        </div>
    @endif
</div>
