<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'POA'))</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="icon" type="image/png" href="{{ asset('Logo/icon-72x72.png') }}" />
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body>
        <div class="font-sans text-gray-900 antialiased">
            @isset($slot)
                {{ $slot }}
            @else
                @hasSection('content')
                    @yield('content')
                @elseif(View::exists('errors.error') && trim($__env->yieldContent('code')) !== '')
                    <div class="min-h-screen flex items-center justify-center px-4">
                        <div class="max-w-xl w-full text-center">
                            <h1 class="text-5xl font-bold text-zinc-900">@yield('code')</h1>
                            <p class="mt-4 text-lg text-zinc-700">@yield('message')</p>
                            <div class="mt-6">
                                @yield('action-buttons')
                            </div>
                        </div>
                    </div>
                @else
                    @yield('body')
                @endif
            @endisset
        </div>

        @livewireScripts
    </body>
    <!-- Script para detectar el modo oscuro desde localStorage -->
    <script>
        // Comprobar si hay una preferencia de tema guardada
        if (localStorage.getItem('color-theme') === 'dark' || 
            (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</html>
