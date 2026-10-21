@use('App\Support\Money')
@props(['goals'])

<x-ui.card title="Metas" action="Ver metas" :action-href="route('goals')">
    <ul class="flex flex-col gap-3">
        @forelse ($goals as $goal)
            @php($percent = $goal['target'] > 0 ? (int) round($goal['saved'] / $goal['target'] * 100) : 0)

            <li wire:key="goal-{{ $goal['name'] }}" class="flex items-center gap-4 rounded-2xl bg-canvas p-3.5">
                <span class="flex size-13 shrink-0 items-center justify-center rounded-full bg-brand-light font-display text-sm font-bold text-brand-dark">
                    {{ $percent }}%
                </span>
                <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                    <div class="flex flex-wrap justify-between gap-x-2 text-sm">
                        <span class="font-semibold">{{ $goal['name'] }}</span>
                        <span class="text-muted tabular-nums">
                            {{ Money::brl($goal['saved'], decimals: 0) }} / {{ Money::brl($goal['target'], decimals: 0) }}
                        </span>
                    </div>
                    <x-ui.progress-bar :value="$percent" track="bg-line" size="h-1.5" :label="'Progresso de '.$goal['name']" />
                </div>
            </li>
        @empty
            <li class="py-6 text-center text-muted">Nenhuma meta ainda.</li>
        @endforelse
    </ul>
</x-ui.card>
