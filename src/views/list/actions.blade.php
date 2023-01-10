<div class="btn-group">
    @if ($buttons->primary)
        @if ($buttons->primary->isLink())
            <a href="{{ $buttons->primary->getFullLink() }}" class="btn btn-default btn-sm btn-flat">{!! $buttons->primary->getIcon() !!} {!! $buttons->primary->getName() !!}</a>
        @elseif ($buttons->primary->isHtml())
            {!! $buttons->primary->getHtml('btn btn-default btn-sm btn-flat') !!}
        @endif
    @endif

    @if ($buttons->secondaries)
        <button type="button" class="btn btn-default btn-sm btn-flat dropdown-toggle" data-toggle="dropdown">
            @if ($buttons->more->isActive)
                {!! $buttons->more->name !!}
            @endif
            <span class="caret"></span>
            <span class="sr-only">Toggle Dropdown</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right" role="menu">
            @foreach ($buttons->secondaries as $button)
                @if ($button->isDivider())
                    <li class="divider" {!! $button->getHtml() !!}></li>
                @elseif ($button->isLink())
                    <li><a href="{{ $button->getFullLink() }}" {!! $button->getHtml() !!}>{!! $button->getIcon() !!} {!! $button->getName() !!}</a></li>
                @elseif ($button->isHtml())
                    <li>{!! $button->getHtml() !!}</li>
                @endif
            @endforeach
        </ul>
    @endif
</div>
