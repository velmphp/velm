<h3 class="text-sm font-semibold text-heading">{{ $card['title'] }}</h3>

@if ($card['subtitle'] !== '')
    <p class="mt-0.5 text-xs text-body-subtle">{{ $card['subtitle'] }}</p>
@endif

@if ($card['fields'] !== [])
    <dl class="mt-2 space-y-1">
        @foreach ($card['fields'] as $field)
            <div class="flex items-baseline justify-between gap-2 text-xs">
                <dt class="text-body-subtle">{{ $field['label'] }}</dt>
                <dd class="font-medium text-body">{{ $field['value'] }}</dd>
            </div>
        @endforeach
    </dl>
@endif

@if ($card['badges'] !== [])
    <div class="mt-2 flex flex-wrap gap-1">
        @foreach ($card['badges'] as $badge)
            <span class="inline-flex items-center rounded-full bg-surface-muted px-2 py-0.5 text-2xs font-medium text-body">
                {{ $badge['value'] }}
            </span>
        @endforeach
    </div>
@endif
