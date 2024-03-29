<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>@if (isset($title)) {{ $title }} @endif | {{ config('app.name', 'Admin') }}</title>
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="stylesheet" href="{{ URL::asset('/assets/form-tool/plugins/bootstrap/dist/css/bootstrap.min.css') }}">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ URL::asset('/assets/form-tool/plugins/font-awesome/css/font-awesome.min.css') }}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{ URL::asset('/assets/form-tool/dist/css/AdminLTE.min.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('/assets/form-tool/dist/css/skins/skin-blue.min.css') }}">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->

  <!-- Google Font -->
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

<!-- jQuery 3 -->
<script src="{{ URL::asset('/assets/form-tool/plugins/jquery/dist/jquery.min.js') }}"></script>
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

  <!-- Main Header -->
  <header class="main-header">

    <!-- Logo -->
    <a href="{{ URL::to(config('form-tool.adminURL') . '/dashboard') }}" class="logo">
      <!-- mini logo for sidebar mini 50x50 pixels -->
      <span class="logo-mini">Adm</span>
      <!-- logo for regular state and mobile devices -->
      <span class="logo-lg">{{ config('app.name', 'Admin') }}</span>
    </a>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation">
      <!-- Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
        <span class="sr-only">Toggle navigation</span>
      </a>
      <!-- Navbar Right Menu -->
      <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">

            @if ('website' == env('APP_TYPE'))
				<li>
                    <a href="{{ URL::to('/') }}" target="_blank" title="View Website" data-toggle="tooltip">
                        <i class="fa fa-globe"></i>
                    </a>
                </li>
			@endif

          <!-- User Account Menu -->
          <li class="dropdown user user-menu">
            <!-- Menu Toggle Button -->
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <!-- The user image in the navbar-->
              <?php /*<img src="/assets/{{ ADMIN_DIR }}/dist/img/avatar.png" class="user-image" alt="User Image"> */ ?>
              <!-- hidden-xs hides the username on small devices so only the image appears. -->
              <span class="hidden-xs">{{ auth()->user()->name ?? null }}</span>
              &nbsp; <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu">
              <!-- The user image in the menu -->
              <li class="user-header">
                <img src="{{ URL::asset('assets/form-tool/dist/img/avatar.png') }}" class="img-circle" alt="User Image">

                <p>
                  {{ auth()->user()->name ?? null }}
                </p>
              </li>
              <li class="user-footer">
                <div class="pull-left">
                  <a href="{{ URL::to(config('form-tool.adminURL') . '/change-password') }}"
                    class="btn btn-default btn-flat">Password</a>
                </div>
                <div class="pull-right">
                  <!-- <a href="{{ URL::to(config('form-tool.adminURL') . '/logout') }}"
                    class="btn btn-default btn-flat">Sign out</a>-->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a href="route('logout')" onclick="event.preventDefault();this.closest('form').submit();"
                            class="btn btn-default btn-flat">
                            {{ __('Log Out') }}
                        </a>
                    </form>
                </div>
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </nav>
  </header>
