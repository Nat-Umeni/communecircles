<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#184D3B">

        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="manifest" href="/site.webmanifest">

        {{-- Apply the saved or system theme before first paint so dark mode doesn't flash light. Keep in sync with resources/js/composables/useTheme.ts. --}}
        <script>
            (() => {
                let theme = 'system';
                try {
                    theme = localStorage.getItem('theme') ?? 'system';
                } catch {}
                const dark = theme === 'dark' || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            })();
        </script>

        @fonts
        {{-- The named routes, for Ziggy's route() helper in the Vue pages. --}}
        @routes
        @vite('resources/js/app.ts')
        <x-inertia::head>
            <title>{{ config('app.name', 'Commune Circles') }}</title>
        </x-inertia::head>
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>
