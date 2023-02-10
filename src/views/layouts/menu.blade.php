<aside class="main-sidebar">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
        <!-- Sidebar Menu -->
        <ul class="sidebar-menu" data-widget="tree">
            <li class="header">MAIN NAVIGATION</li>
            
            @foreach ($sidebar as $menu)
                @if ($menu->isParent)
                    @include('form-tool::layouts.sub_menu', ['menu' => $menu])
                @else
                    <li @if ($menu->active) class="active" @endif>
                        <a href="{{ $menu->href }}">
                            <i class="{{ $menu->icon }}"></i> <span>{{ $menu->label }}</span>
                        </a>
                    </li>
                @endif
            @endforeach
            
        </ul>
        <!-- /.sidebar-menu -->
    </section>
    <!-- /.sidebar -->
</aside>
