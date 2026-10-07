<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CPD Portal') | CPD Portal</title>
    
    <!-- Google Fonts & Bootstrap Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="icon" type="image/png" href="{{ asset('images/icons/pitc.png') }}">
    
    <!-- Theme & Loader Styles -->
    <link href="{{ asset('css/loader.css') }}" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <!-- Preloader -->
    <div id="preloader">
        <div class="loader"></div>
    </div>

    <!-- App Layout Shell -->
    <div class="app-layout">
        <!-- Sidebar Backdrop for Mobile Screens -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- Sidebar Navigation -->
        <aside class="app-sidebar" id="appSidebar">
            <div class="sidebar-header">
                <a href="{{ route('dashboard') }}" class="brand-badge">
                    <div class="brand-icon">
                        <i class="bi bi-cpu-fill"></i>
                    </div>
                    <div class="brand-text">
                        <span class="brand-title">CPD PORTAL</span>
                        <span class="brand-subtitle">Consumer DB</span>
                    </div>
                </a>
                <button type="button" class="btn btn-sm btn-secondary d-lg-none" id="sidebarCloseBtn" aria-label="Close Sidebar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <span class="sidebar-section-title">Main Navigation</span>

                <a href="{{ route('dashboard') }}" class="nav-item-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('consumers.index') }}" class="nav-item-link {{ request()->routeIs('consumers.index') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i>
                    <span>Consumers</span>
                </a>

                <a href="{{ route('consumers.search') }}" class="nav-item-link {{ request()->routeIs('consumers.search') ? 'active' : '' }}">
                    <i class="bi bi-search"></i>
                    <span>Search Consumers</span>
                </a>

                <a href="{{ route('consumers.histories.all') }}" class="nav-item-link {{ request()->routeIs('consumers.histories.all') ? 'active' : '' }}">
                    <i class="bi bi-clock-history"></i>
                    <span>Audit Histories</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="{{ route('logout') }}" class="logout-link">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Sign Out</span>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="app-main">
            <!-- Sticky Topbar Header -->
            <header class="app-topbar">
                <div class="topbar-left">
                    <button type="button" class="topbar-toggle-btn" id="sidebarToggle" aria-label="Open Sidebar Menu">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <div class="page-breadcrumb">
                        <span class="current">@yield('title', 'Dashboard')</span>
                    </div>
                </div>

                <div class="topbar-right">
                    <div class="user-pill d-none d-sm-flex">
                        <span class="user-avatar-dot"></span>
                        <span>{{ auth()->user()->name ?? 'Administrator' }}</span>
                    </div>
                    <a href="{{ route('consumers.create') }}" class="btn btn-sm btn-primary d-none d-md-inline-flex">
                        <i class="bi bi-plus-lg"></i>
                        <span>New Consumer</span>
                    </a>
                </div>
            </header>

            <!-- Page Body Content -->
            <main class="app-content">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('modals')

    <!-- Core Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var baseUrl = "{{ url('/') }}";
        
        // Mobile Sidebar Toggle
        $(document).ready(function() {
            var $sidebar = $('#appSidebar');
            var $backdrop = $('#sidebarBackdrop');

            $('#sidebarToggle').on('click', function() {
                $sidebar.addClass('show');
                $backdrop.addClass('active');
                $('body').css('overflow', 'hidden');
            });

            function closeSidebar() {
                $sidebar.removeClass('show');
                $backdrop.removeClass('active');
                $('body').css('overflow', '');
            }

            $('#sidebarCloseBtn, #sidebarBackdrop').on('click', closeSidebar);
            
            // Preloader fadeout
            var $preloader = $('#preloader');
            $(window).on('load', function() {
                setTimeout(function() {
                    $preloader.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 150);
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
