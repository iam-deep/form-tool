@extends('form-tool::layouts.layout')

@section('content')

<div class="row">
    <div class="col-md-8 col-sm-offset-2">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $title }}</h3>
            </div>

            <?php echo getHTMLForm(); ?>

        </div>
    </div>
</div>

@stop