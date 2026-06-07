@foreach ($cards as $card)
    @if ($card['open_url'])
        <a
            href="{{ $card['open_url'] }}"
            class="block rounded-md border border-default bg-surface p-3 shadow-sm transition-colors hover:border-fg-brand/40"
        >
            @include('velm-admin::pages.analytics.partials.kanban-card', ['card' => $card])
        </a>
    @else
        <article class="rounded-md border border-default bg-surface p-3 shadow-sm">
            @include('velm-admin::pages.analytics.partials.kanban-card', ['card' => $card])
        </article>
    @endif
@endforeach
