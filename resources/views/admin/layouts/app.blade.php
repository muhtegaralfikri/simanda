<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - SIMANDA</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('assets/admin/css/style.css') }}">
    
    <!-- Alpine.js & Chart.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    @stack('styles')
</head>
<body>
    <div class="app-container" x-data="{ sidebarOpen: false }">
        @include('admin.partials.sidebar')
        
        <div class="main-content">
            @include('admin.partials.topbar')
            
            <main class="content-wrapper">
                @include('admin.partials.flash')
                @yield('content')
            </main>
            
            <footer class="app-footer">
                <div>&copy; {{ date('Y') }} <strong>SIMANDA</strong> — Sistem Monitoring Anggaran dan Dokumen Kegiatan</div>
                <div>Versi 1.0.0 Monolith Production</div>
            </footer>
        </div>
    </div>
    
    @stack('scripts')
</body>
</html>
