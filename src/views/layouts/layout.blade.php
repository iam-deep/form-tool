@include('form-tool::layouts.header')

<aside class="main-sidebar">

    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">

      <!-- Sidebar Menu -->
      <ul class="sidebar-menu" data-widget="tree">
        <li class="header">MAIN NAVIGATION</li>
        @if (isset($sideMenu))
            @foreach ($sideMenu as $menuTitle => $menu)
                @if (is_array($menu[1]))
                    <li class="treeview">
                        <a href="#"><i class="{{ $menu[0] }}"></i> <span>{{ $menuTitle }}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            @foreach ($menu[1] as $subTitle => $subMenu)
                                <li @if (isset($subMenu[2]) && $subMenu[2] == 'active') class="active" @endif><a href="{{ URL::to(config('form-tool.adminURL') . '/' . $subMenu[0]) }}"> <span>{{ $subTitle }}</span></a></li>
                            @endforeach
                        </ul>
                    </li>
                @else
                    <li @if (isset($menu[2]) && $menu[2] == 'active') class="active" @endif><a href="{{ URL::to(config('form-tool.adminURL') . '/' . $menu[0]) }}"><i class="{{ $menu[1] }}"></i> <span>{{ $menuTitle }}</span></a></li>
                @endif
            @endforeach
        @endif

        {{-- sample multi level menu

        <li class="treeview">
          <a href="#"><i class="fa fa-link"></i> <span>Multilevel</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="#">Link in level 2</a></li>
            <li><a href="#">Link in level 2</a></li>
          </ul>
        </li> --}}

      </ul>
      <!-- /.sidebar-menu -->
    </section>
    <!-- /.sidebar -->
</aside>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        @if (isset($title)) {{ $title }} @else <i>Set a title</i> @endif
      </h1>
      <ol class="breadcrumb">
        <li><a href="/dashboard"><i class="fa fa-dashboard"></i> Dashboard</a></li>
        <li class="active"> @if (isset($title)) {{ $title }} @endif</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content container-fluid">

        <div class="row">
            <div class="col-sm-12">
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                    <?php session()->pull('error'); ?>
                @elseif (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                    <?php session()->pull('success'); ?>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <?php session()->pull('errors'); ?>
                @endif
            </div>
        </div>

        @yield('content')

    </section>
    <!-- /.content -->
</div>

@include('form-tool::layouts.footer')