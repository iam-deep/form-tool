@extends('form-tool::layouts.layout')

@section('content')

<?php
// Called on top to set all the dependencies
// If called below getAllCss() then we will not get the css
$form = getHTMLForm();
?>

<?php echo getCssLinks(); ?>
<?php echo getCss(); ?>

<div class="row">
    <div class="col-md-8 col-sm-offset-2">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $title }}</h3>
            </div>

            <?php echo $form; ?>

        </div>
    </div>
</div>

@if (isset($script))
    {!! $script !!}
@endif

<?php echo getJsLinks(); ?>
<?php echo getJs(); ?>

@stop