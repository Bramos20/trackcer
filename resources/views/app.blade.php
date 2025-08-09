<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <!-- Theme color meta tags for Safari overscroll -->
    <meta name="theme-color" content="#E4E4E4" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#37297E" media="(prefers-color-scheme: dark)">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'TrackCer') }}</title>
    @viteReactRefresh

    @routes
    {{-- Vite: React + Sass --}}
    @vite(['resources/js/app.tsx', 'resources/sass/app.scss'])
    @inertiaHead

    {{-- Fonts and Icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">

    {{-- SweetAlert --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>


    {{-- Custom Styles --}}

    {{-- Favicon & Manifest --}}
    <link rel="icon" type="image/png" href="/my-favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/my-favicon/favicon.svg" />
    <link rel="shortcut icon" href="/my-favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/my-favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="TrackCer" />
    <link rel="manifest" href="/my-favicon/site.webmanifest" />

    {{-- Initialize theme before React loads to prevent flash --}}
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            const isDark = savedTheme === 'dark';

            // Ensure proper class is set
            if (isDark) {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            } else {
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
            }

            // Update theme color meta tags dynamically
            const updateThemeColor = () => {
                const isDarkMode = document.documentElement.classList.contains('dark');
                const isMobile = window.innerWidth <= 768 || /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
                const metaTags = document.querySelectorAll('meta[name="theme-color"]');
                metaTags.forEach(tag => tag.remove());

                const newMeta = document.createElement('meta');
                newMeta.name = 'theme-color';

                // Different colors for mobile vs desktop
                if (isMobile) {
                    // Mobile colors - using main background colors
                    newMeta.content = isDarkMode ? '#382980' : '#E4E4E4';
                    document.documentElement.style.backgroundColor = isDarkMode ? '#382980' : '#E4E4E4';
                } else {
                    // Desktop colors - using sidebar colors
                    newMeta.content = isDarkMode ? '#37297E' : '#E4E4E4';
                    document.documentElement.style.backgroundColor = isDarkMode ? '#37297E' : '#E4E4E4';
                }

                document.head.appendChild(newMeta);
            };

            // Initial update
            updateThemeColor();

            // Watch for theme changes
            const observer = new MutationObserver(() => {
                updateThemeColor();
            });
            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });

            // Update on window resize (for responsive changes)
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(updateThemeColor, 100);
            });
        })();
    </script>
</head>
<body class="antialiased">
{{-- Dark mode gradient background --}}
<div class="fixed inset-0 pointer-events-none" id="gradient-bg"></div>

{{-- Tailwind Flash Messages --}}
@if (session('success'))
<div class="fixed top-4 left-1/2 transform -translate-x-1/2 w-full max-w-sm z-50">
    <div class="bg-green-600 text-white px-4 py-3 rounded-lg shadow-md flex items-center justify-between space-x-4">
        <span class="font-medium">✅ {{ session('success') }}</span>
        <button onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200">&times;</button>
    </div>
</div>
@endif

@if (session('error'))
<div class="fixed top-4 left-1/2 transform -translate-x-1/2 w-full max-w-sm z-50">
    <div class="bg-red-600 text-white px-4 py-3 rounded-lg shadow-md flex items-center justify-between space-x-4">
        <span class="font-medium">⚠️ {{ session('error') }}</span>
        <button onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200">&times;</button>
    </div>
</div>
@endif

{{-- Inertia mount point --}}
@inertia

{{-- Theme scripts --}}
{{-- <script>
    const getTheme = () => {
        const saved = localStorage.getItem('theme');
        return saved ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    };

    const applyTheme = (theme) => {
        document.documentElement.classList.remove('dark', 'light');
        document.documentElement.classList.add(theme);
        document.querySelector('meta[name="theme-color"]')?.setAttribute('content', theme === 'dark' ? '#242526' : '#ffffff');
    };

    document.addEventListener('DOMContentLoaded', () => {
        const theme = getTheme();
        applyTheme(theme);
    });

    window.addEventListener('themeChanged', () => {
        applyTheme(getTheme());
    });

    window.addEventListener('storage', (e) => {
        if (e.key === 'theme') applyTheme(getTheme());
    });
</script> --}}

@stack('scripts')
</body>
</html>
