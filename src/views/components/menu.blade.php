@foreach ($sideMenu as $menu)
    @if ($menu->isParent)
        <li class="treeview @if ($menu->active) active @endif">
            <a href="#"><i class="{{ $menu->icon }}"></i> <span>{{ $menu->label }}</span>
                <span class="pull-right-container">
                    <i class="fa fa-angle-left pull-right"></i>
                </span>
            </a>
            <ul class="treeview-menu">
                @foreach ($menu->childs as $subMenu)
                    <li @if ($subMenu->active) class="active" @endif><a href="{{ $subMenu->href }}"> <span>{{ $subMenu->label }}</span></a></li>
                @endforeach
            </ul>
        </li>
    @else
        <li @if ($menu->active) class="active" @endif><a href="{{ $menu->href }}"><i class="{{ $menu->icon }}"></i> <span>{{ $menu->label }}</span></a></li>
    @endif
@endforeach