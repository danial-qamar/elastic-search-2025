<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="icon" type="image/png" href="{{ asset('images/icons/pitc.png') }}">
    <link href="{{ asset('css/loader.css') }}" rel="stylesheet">
    <style>
        .pagination {
            font-size: 14px;
        }
        .page-link {
            padding: 5px 10px;
        }
        .sidebar {
            height: 100vh;
            width: 250px;
            background-color: #343a40;
            color: white;
            position: fixed;
            padding: 20px;
        }
        .sidebar a {
            color: white;
            display: block;
            padding: 10px;
            text-decoration: none;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .content {
            margin-left: 260px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div id="preloader">
        <div class="loader"></div>
    </div>
    <div class="sidebar">
        <h4>Menu</h4>
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <a href="{{ route('consumers.index') }}">Consumers</a>
        <a href="{{ route('consumers.search') }}">Search Consumers</a>
        <a href="{{ route('logout') }}" class="btn btn-danger mt-3">Logout</a>
    </div>

    <div class="content">
        @yield('content')
    </div>
</body>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
   var baseUrl = "{{ env('BASE_URL') }}";
    var preloader = $('#preloader');
    $(window).on('load', function() {
        setTimeout(function() {
            preloader.fadeOut('slow', function() {
                $(this).remove();
            });
        }, 300)
    });
</script>
@stack('scripts')
</html>
