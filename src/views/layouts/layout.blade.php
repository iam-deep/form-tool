<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>@if (isset($title)) {{ $title }} @endif | {{ config('app.name', 'Admin') }}</title>
  <meta name="csrf-token" content="{{ csrf_token() }}" />

  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="stylesheet" href="{{ asset('/assets/form-tool/plugins/bootstrap/dist/css/bootstrap.min.css') }}">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ asset('/assets/form-tool/plugins/font-awesome/css/font-awesome.min.css') }}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{ asset('/assets/form-tool/dist/css/AdminLTE.min.css') }}">
  <link rel="stylesheet" href="{{ asset('/assets/form-tool/dist/css/skins/skin-blue.min.css') }}">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->

  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

  <!-- jQuery 3 -->
  <script src="{{ asset('/assets/form-tool/plugins/jquery/dist/jquery.min.js') }}"></script>
  <script src="https://code.jquery.com/ui/1.11.2/jquery-ui.js"></script>
  <style>
    .has-help {
        border-bottom:1px dashed grey;
    }
    input[type=checkbox], input[type=radio] {
        margin: 10px 0 0;
    }
    .footer-sticky {
        position: fixed;
        bottom: 0;
        left: 230px;
        width: 89%;
        z-index: 99999;
        box-shadow: 0 -4px 5px -3px rgb(0 0 0 / 10%);
    }
    .table>thead>tr>th, .table>tbody>tr>th, .table>tfoot>tr>th, .table>thead>tr>td, .table>tbody>tr>td, .table>tfoot>tr>td {
        vertical-align: middle;
    }
  </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
    <div class="wrapper">
        @include('form-tool::layouts.header')

        @include('form-tool::layouts.menu')

        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <h1>@if (isset($title)) {{ $title }} @else <i>Set a title</i> @endif</h1>
            </section>

            <!-- Main content -->
            <section class="content container-fluid">
                <div class="row">
                    <div class="col-sm-12">
                        @if (session('error'))
                            <div class="alert alert-danger">
                                {!! session('error') !!}
                            </div>
                        @elseif (session('success'))
                            <div class="alert alert-success">
                                {!! session('success') !!}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{!! $error !!}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>

                @yield('content')

            </section>
            <!-- /.content -->
        </div>

        @include('form-tool::layouts.footer')
    </div>
    <!-- ./wrapper -->

    <!-- REQUIRED JS SCRIPTS -->
    <!-- Bootstrap 3.3.7 -->
    <script src="{{ asset('/assets/form-tool/plugins/bootstrap/dist/js/bootstrap.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('/assets/form-tool/dist/js/adminlte.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            $("body").tooltip({ selector: '[data-toggle=tooltip]' });
        });

        // To make dropdown visible over the table
        $('.table-responsive').on('show.bs.dropdown', function () {
            $('.table-responsive').css( "overflow", "inherit" );
        });
        $('.table-responsive').on('hide.bs.dropdown', function () {
            $('.table-responsive').css( "overflow", "auto" );
        });
    </script>
</body>
</html>
