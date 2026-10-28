@props(['tone' => 'light'])

<a href="{{ route('dashboard') }}" wire:navigate {{ $attributes->class(['flex items-center gap-2.5 no-underline', 'text-white' => $tone === 'light', 'text-strong' => $tone === 'dark']) }}>
    <span @class(['flex size-9 items-center justify-center rounded-[10px]', 'bg-mint text-ink' => $tone === 'light', 'bg-ink text-mint' => $tone === 'dark'])>
        <x-icon name="trend" stroke-width="2.6" class="size-[18px]" />
    </span>
    <span class="font-display text-[19px] font-bold">FinanceFlow</span>
</a>
