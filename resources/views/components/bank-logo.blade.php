{{--
    Selo do banco achado em $name (e, se não achar, em $fallback).
    Sem banco reconhecido, mostra o conteúdo do slot.
--}}
@props(['name' => null, 'fallback' => null])

@php($bank = \App\Support\Banks::find($name, $fallback))

@if ($bank && $bank['logo'])
    <span {{ $attributes->class(['flex shrink-0 items-center justify-center overflow-hidden bg-white ring-1 ring-line']) }}>
        <img src="{{ $bank['logo'] }}" alt="{{ $bank['name'] }}" class="size-full object-contain p-1" loading="lazy">
    </span>
@elseif ($bank)
    <span
        {{ $attributes->class(['flex shrink-0 items-center justify-center overflow-hidden font-bold tracking-tight']) }}
        style="background: {{ $bank['bg'] }}; color: {{ $bank['fg'] }}"
        role="img"
        aria-label="{{ $bank['name'] }}"
    >{{ $bank['mark'] }}</span>
@else
    {{ $slot }}
@endif
