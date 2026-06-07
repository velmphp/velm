@php($switcher = $this->analyticsViewSwitcher())

@if (count($switcher) > 1)
    <nav aria-label="{{ __('View switcher') }}" class="flex items-center gap-1 text-xs">
        @foreach ($switcher as $item)
            <a
                href="{{ $item['url'] }}"
                @class([
                    'inline-flex items-center gap-1 rounded-md border px-2.5 py-1 transition-colors',
                    'border-fg-brand bg-brand-soft font-semibold text-fg-brand' => $item['active'],
                    'border-default text-body hover:bg-surface-muted' => ! $item['active'],
                ])
            >
                @switch($item['type'])
                    @case('list')
                        <x-velm-ui::icon icon="heroicon-o-list-bullet" class="h-3.5 w-3.5" />
                        @break
                    @case('kanban')
                        <x-velm-ui::icon icon="heroicon-o-view-columns" class="h-3.5 w-3.5" />
                        @break
                    @case('graph')
                        <x-velm-ui::icon icon="heroicon-o-chart-bar" class="h-3.5 w-3.5" />
                        @break
                    @case('pivot')
                        <x-velm-ui::icon icon="heroicon-o-table-cells" class="h-3.5 w-3.5" />
                        @break
                @endswitch
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
@endif
