@if (($wordWrap ?? null) === false)
    <style>
        .form-tool-no-wrap > thead > tr > th,
        .form-tool-no-wrap > thead > tr > th *,
        .form-tool-no-wrap > tbody > tr > td,
        .form-tool-no-wrap > tbody > tr > td * {
            white-space: nowrap !important;
            text-wrap: nowrap !important;
            overflow-wrap: normal;
            word-break: normal;
        }
    </style>
@endif
