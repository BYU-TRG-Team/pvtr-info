<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'PVTR License Lookup') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
                <a href="{{ route('verification.index') }}" class="text-lg font-semibold tracking-tight">
                    PVTR License Lookup
                </a>

                <nav class="flex items-center gap-4 text-sm">
                    <a href="{{ route('verification.index') }}" class="text-slate-600 hover:text-slate-950">Look up</a>
                    <a href="{{ route('complaints.create') }}" class="text-slate-600 hover:text-slate-950">File a complaint</a>
                    @auth
                        <a href="{{ route('admin.imports.index') }}" class="text-slate-600 hover:text-slate-950">Imports</a>
                        <a href="{{ route('admin.complaints.index') }}" class="text-slate-600 hover:text-slate-950">Complaints</a>
                        <a href="{{ route('admin.users.index') }}" class="text-slate-600 hover:text-slate-950">Users</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-slate-600 hover:text-slate-950">Log out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-slate-600 hover:text-slate-950">Admin login</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </body>
</html>
