<div class="form-horizontal">
    @if ($page->form->method != 'GET')
        @csrf
    @endif

    @if ($page->form->isEdit)
        <input type="hidden" name="_method" value="PUT">
    @endif

    <div id="beforeForm"></div>

    @foreach ($page->form->fields as $field)
        {!! $field !!}
    @endforeach

    <div id="afterForm"></div>
</div>

{!! $page->script !!}
