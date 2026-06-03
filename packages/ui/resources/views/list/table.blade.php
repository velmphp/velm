@php
    $columns = $this->listColumns();
    $headers = $this->listHeaders();
    $headerByName = collect($headers)->keyBy('name');
    $rowActions = $this->listRowActions();
    $hasRowActions = $this->hasListRowActions();
    $grouped = filled($this->listGroupBy);
    $records = $grouped ? null : $this->paginatedListRecords();
    $groups = $grouped ? $this->groupedListRecords() : [];
@endphp

<div class="overflow-visible rounded-xl border border-default bg-neutral-primary">
    <table class="w-full text-sm">
        <thead class="border-b border-default bg-neutral-tertiary">
            <tr>
                <th class="w-9 px-3 py-3" aria-hidden="true"></th>
                @foreach ($columns as $column)
                    @if ($this->isListColumnVisible($column->name))
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-body-subtle uppercase">
                            {{ $headerByName[$column->name]['label'] ?? $column->name }}
                        </th>
                    @endif
                @endforeach
                @if ($hasRowActions)
                    <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-body-subtle uppercase">
                        {{ __('Actions') }}
                    </th>
                @endif
            </tr>
        </thead>

        @if ($grouped)
            @foreach ($groups as $group)
                <tbody class="divide-y divide-default" x-data="{ open: true }">
                    <tr class="cursor-pointer select-none bg-neutral-tertiary/60 hover:bg-neutral-tertiary" @click="open = ! open">
                        <td colspan="{{ collect($columns)->filter(fn ($c) => $this->isListColumnVisible($c->name))->count() + ($hasRowActions ? 2 : 1) }}" class="px-4 py-3 text-xs font-semibold text-heading">
                            <span class="inline-flex items-center gap-2">
                                <svg class="h-3 w-3 text-body-subtle transition-transform" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                                {{ $group['label'] }}
                                <span class="text-2xs font-medium text-body-subtle">({{ count($group['rows']) }})</span>
                            </span>
                        </td>
                    </tr>
                    @foreach ($group['rows'] as $record)
                        @include('velm-ui::list.row', [
                            'record' => $record,
                            'columns' => $columns,
                            'rowActions' => $rowActions,
                            'xShow' => 'open',
                        ])
                    @endforeach
                </tbody>
            @endforeach
        @else
            <tbody class="divide-y divide-default">
                @forelse ($records as $record)
                    @include('velm-ui::list.row', ['record' => $record, 'columns' => $columns, 'rowActions' => $rowActions])
                @empty
                    <tr>
                        <td colspan="{{ collect($columns)->filter(fn ($c) => $this->isListColumnVisible($c->name))->count() + ($hasRowActions ? 2 : 1) }}" class="px-4 py-8 text-center text-sm text-body-subtle">
                            {{ __('No records found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        @endif
    </table>
</div>

@if (! $grouped && $records->hasPages())
    <div class="mt-3">
        {{ $records->links() }}
    </div>
@endif
