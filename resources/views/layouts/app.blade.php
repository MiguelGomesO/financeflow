<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'FinanceFlow' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body
    class="bg-canvas font-sans text-[15px] leading-normal text-ink antialiased"
    x-data="{ sidebarOpen: false }"
    x-on:keydown.escape.window="sidebarOpen = false"
>
    <div class="min-h-screen lg:flex">
        <x-sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
            <x-mobile-topbar />

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
