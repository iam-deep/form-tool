@include('form-tool::layouts.header')

@include('form-tool::layouts.menu')

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        @if (isset($title)) {{ $title }} @else <i>Set a title</i> @endif
      </h1>
    </section>

    <!-- Main content -->
    <section class="content container-fluid">

        <div class="row">
            <div class="col-sm-12">
                @if (session('error'))
                    <div class="alert alert-danger">
                        {!! session('error') !!}
                    </div>
                @elseif (session('success'))
                    <div class="alert alert-success">
                        {!! session('success') !!}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        @yield('content')

    </section>
    <!-- /.content -->
</div>

@include('form-tool::layouts.footer')