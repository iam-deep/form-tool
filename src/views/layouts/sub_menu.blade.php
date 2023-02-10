<li class="treeview @if ($menu->active) active @endif">
    <a href="#"><i class="{{ $menu->icon }}"></i> <span>{{ $menu->label }}</span>
        <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
        </span>
    </a>
    <ul class="treeview-menu">
        @foreach ($menu->childs as $subMenu)
            @if ($subMenu->isParent)
                @include('form-tool::layouts.sub_menu', ['menu' => $subMenu])
            @else
                <li @if ($subMenu->active) class="active" @endif>
                    <a href="{{ $subMenu->href ?? null }}"> <span>{{ $subMenu->label }}</span></a>
                </li>
            @endif
        @endforeach
    </ul>
</li>
