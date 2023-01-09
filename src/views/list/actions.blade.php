<div class="btn-group">
    @if ($primary)
        @if ($primary->getType() == 'link')
            <a href="{{ $primary->getFullLink() }}" class="btn btn-default btn-sm btn-flat">{!! $primary->getIcon() !!} {!! $primary->getName() !!}</a>
        @elseif ($primary->getType() == 'html')
            {!! $primary->getHtml('btn btn-default btn-sm btn-flat') !!}
        @endif
    @endif

    @if ($secondaries)
        <button type="button" class="btn btn-default btn-sm btn-flat dropdown-toggle" data-toggle="dropdown">
            <span class="caret"></span>
            <span class="sr-only">Toggle Dropdown</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right" role="menu">
            @foreach ($secondaries as $button)
                @if ($button->getType() == 'divider')
                    <li class="divider" {!! $button->getHtml() !!}></li>
                @elseif ($button->getType() == 'link')
                    <li><a href="{{ $button->getFullLink() }}" {!! $button->getHtml() !!}>{!! $button->getIcon() !!} {!! $button->getName() !!}</a></li>
                @elseif ($button->getType() == 'html')
                    <li>{!! $button->getHtml() !!}</li>
                @endif
            @endforeach
        </ul>
    @endif
</div>
