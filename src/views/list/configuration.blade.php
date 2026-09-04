@php($section = $section ?? null)

@if ($section !== 'settings' && $configuration->perPageEnabled())
    {{-- Request-only page size; the saved default is updated only by the Listing Settings form below. --}}
    <form method="GET" action="{{ url()->current() }}" class="mb-0">
        @foreach (request()->except(['page', 'per_page']) as $key => $value)
            @if (is_scalar($value))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <select name="per_page" class="form-control form-select form-control-sm form-select-sm w-auto" style="font-size:.75rem; padding-top:.15rem; padding-bottom:.15rem;" onchange="this.form.submit()" aria-label="Rows per page">
            @foreach ($configuration->perPageOptions() as $option)
                <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} per page</option>
            @endforeach
        </select>
    </form>
@endif

@if ($section !== 'perPage' && $configuration->canUpdate())
    <style>
        .form-tool-list-settings { position:relative; }
        .form-tool-list-settings summary { list-style:none; cursor:pointer; }
        .form-tool-list-settings summary::-webkit-details-marker { display:none; }
        .form-tool-list-settings-panel {
            position:absolute; top:0; right:100%; z-index:1050; width:360px; max-width:85vw;
            padding:15px; background:#fff; border:1px solid #ddd; box-shadow:0 4px 12px rgba(0,0,0,.2);
            color:#212529;
        }
        .form-tool-list-settings-options { max-height:180px; overflow:auto; border:1px solid #eee; padding:8px; }
        .form-tool-list-settings-options label { display:block; margin-bottom:5px; font-weight:normal; }
    </style>

        <div class="dropdown-divider"></div>
        <details class="form-tool-list-settings" onclick="event.stopPropagation()">
            <summary class="dropdown-item">
                <i class="fa fa-cog fas fa-cog"></i> Listing Settings
            </summary>
            <div class="form-tool-list-settings-panel">
                <form method="POST" action="{{ $configuration->saveUrl() }}">
                    @csrf
                    <div class="form-group mb-3">
                        <strong>Columns</strong>
                        <div class="form-tool-list-settings-options mt-1">
                            @foreach ($configuration->columns() as $key => $label)
                                <label>
                                    <input type="checkbox" name="columns[]" value="{{ $key }}"
                                        @checked(in_array($key, $configuration->selectedColumns(), true))>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <strong>Filters</strong>
                        <div class="form-tool-list-settings-options mt-1">
                            @foreach ($configuration->filters() as $key => $label)
                                <label>
                                    <input type="checkbox" name="filters[]" value="{{ $key }}"
                                        @checked(in_array($key, $configuration->selectedFilters(), true))>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    @if ($configuration->perPageEnabled())
                        <div class="form-group mb-3">
                            <label for="formToolDefaultPerPage"><strong>Default rows per page</strong></label>
                            <select id="formToolDefaultPerPage" name="perPage" class="form-control form-select">
                                @foreach ($configuration->perPageOptions() as $option)
                                    <option value="{{ $option }}" @selected($configuration->defaultPerPage() === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <button type="submit" class="btn btn-primary btn-sm rounded-0">Save</button>
                </form>
            </div>
        </details>
@endif
