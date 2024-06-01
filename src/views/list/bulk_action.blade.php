@if ($bulkActions)
    <form action="{{ $formAction }}" method="post" class="form-inline pull-left" onSubmit="return beforeBulkSubmit()">
        <select class="form-control input-sm" name="bulkAction" required>
            <option value="">Bulk Action</option>
            @foreach ($bulkActions as $key => $text)
                <option value="{{ $key }}">{{ $text }}</option>
            @endforeach
        </select>
        <input type="hidden" id="bulkIds" name="ids" value="">
        @csrf
        <button class="btn btn-sm btn-default" name="bulk-action">Apply</button>
    </form>
@else
    <p></p>
@endif
