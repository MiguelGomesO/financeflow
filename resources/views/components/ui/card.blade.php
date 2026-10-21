@props(['title' => null, 'action' => null, 'actionHref' => '#'])

<section {{ $attributes->class(['flex flex-col gap-5 rounded-[20px] border border-line bg-white p-6']) }}>
    @if ($title || isset($header))
        <div class="flex flex-wrap items-center justify-between gap-3">
            @if ($title)
                <h2 class="font-display text-[19px] font-semibold">{{ $title }}</h2>
            @endif

            {{ $header ?? '' }}

            @if ($action)
                <a href="{{ $actionHref }}" class="text-sm font-semibold text-brand hover:text-brand-dark">{{ $action }}</a>
            @endif
        </div>
    @endif

    {{ $slot }}
</section>
