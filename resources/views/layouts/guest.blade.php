<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'FinanceFlow' }}</title>

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <meta name="theme-color" content="#13201c">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-canvas font-sans text-[15px] leading-normal text-ink antialiased">
    <main class="flex min-h-screen flex-col items-center justify-center gap-6 p-4">
        <x-logo tone="dark" />

        <div class="w-full max-w-md rounded-3xl border border-line bg-white p-6 shadow-sm sm:p-8">
            {{ $slot }}
        </div>
    </main>

    @livewireScripts
</body>
</html>
