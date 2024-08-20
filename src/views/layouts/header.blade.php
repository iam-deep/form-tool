<!-- Main Header -->
<header class="main-header">

  <!-- Logo -->
  <a href="{{ url(config('form-tool.adminURL') . '/dashboard') }}" class="logo">
    <!-- mini logo for sidebar mini 50x50 pixels -->
    <span class="logo-mini">{{ substr(config('app.name', 'Admin'), 0, 3) }}</span>
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
        @if ('website' == config('app.type'))
          <li>
            <a href="{{ url('/') }}" target="_blank" title="View Website" data-toggle="tooltip">
              <i class="fa fa-globe"></i>
            </a>
          </li>
        @endif

        <!-- User Account Menu -->
        <li class="dropdown user user-menu">
          <!-- Menu Toggle Button -->
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <!-- The user image in the navbar-->
            {{-- <img src="/assets/{{ ADMIN_DIR }}/dist/img/avatar.png" class="user-image" alt="User"> --}}
            <!-- hidden-xs hides the username on small devices so only the image appears. -->
            <span class="hidden-xs">{{ auth()->user()->name ?? null }}</span>
            &nbsp; <i class="fa fa-caret-down"></i>
          </a>
          <ul class="dropdown-menu">
            <!-- The user image in the menu -->
            <li class="user-header">
              <img src="{{ asset('assets/form-tool/dist/img/avatar.png') }}" class="img-circle" alt="User Image">

              <p>
                {{ auth()->user()->name ?? null }}
              </p>
            </li>
            <li class="user-footer">
              <div class="pull-left">
                <a href="{{ url(config('form-tool.adminURL') . '/change-password') }}"
                  class="btn btn-default btn-flat">Password</a>
              </div>
              <div class="pull-right">
                {{-- <a href="{{ url(config('form-tool.adminURL') . '/logout') }}" class="btn btn-default btn-flat">Sign out</a> --}}
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <a href="{{ route('logout') }}" onclick="event.preventDefault();this.closest('form').submit();"
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
