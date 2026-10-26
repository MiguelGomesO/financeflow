{{-- Foto de perfil, ou as iniciais do nome quando não há foto. --}}
@props(['user'])

@if ($user?->avatar_url)
    <img src="{{ $user->avatar_url }}" alt="" {{ $attributes->class(['shrink-0 rounded-full object-cover']) }}>
@else
    <span {{ $attributes->class(['flex shrink-0 items-center justify-center rounded-full bg-mint font-bold text-ink']) }} aria-hidden="true">
        {{ str($user?->name ?? 'Você')->substr(0, 2)->upper() }}
    </span>
@endif
