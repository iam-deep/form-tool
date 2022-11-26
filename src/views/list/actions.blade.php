<div class="btn-group">
    @if ($primary)
        <a href="{{ $primary->link }}" class="btn btn-default btn-sm btn-flat"><i class="fa fa-pencil"></i> {{ $primary->text }}</a>
    @endif

    @if ($secondaries)
        <button type="button" class="btn btn-default btn-sm btn-flat dropdown-toggle" data-toggle="dropdown">
            <span class="caret"></span>
            <span class="sr-only">Toggle Dropdown</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right" role="menu">
            @foreach ($secondaries as $button)
                @if ($button->type == 'divider')
                    <li class="divider"></li>
                @elseif ($button->type == 'link')
                    <li><a href="{{ $button->link }}" @if($button->blank) target="_blank" @endif>{{ $button->text }}</a></li>
                @elseif ($button->type == 'html')
                    <li>{!! $button->html !!}</li>
                @endif
            @endforeach
        </ul>
    @endif
</div>

