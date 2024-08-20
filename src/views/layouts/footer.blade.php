  <!-- Main Footer -->
  <footer class="main-footer">
    <!-- To the right -->
    <div class="pull-right hidden-xs">
      Processed in {{ round(microtime(true) - LARAVEL_START, 2) }} secs
    </div>
    <!-- Default to the left -->
    <strong>
        Copyright &copy; {{ date('Y') }} <a href="/" target="_blank">{{ config('app.name', 'Admin') }}</a>.
    </strong> All rights reserved.
  </footer>
