<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#184D3B">

        {{-- Apply the saved or system theme before first paint so dark mode doesn't flash light. Keep in sync with resources/js/composables/useTheme.js. --}}
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
        @vite('resources/js/app.js')
        <x-inertia::head>
            <title>{{ config('app.name', 'Commune Circles') }}</title>
        </x-inertia::head>
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>
