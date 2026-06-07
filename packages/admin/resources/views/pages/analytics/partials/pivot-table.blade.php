@php
    $rowLabelCount = count($pivot['row_specs'] ?? []) ?: 1;
    $headerLevelCount = count($pivot['header_levels'] ?? []);
    $rowspan = $headerLevelCount + 1;
@endphp

<table class="min-w-full text-sm">
    <thead class="bg-surface-muted/50">
        @foreach ($pivot['header_levels'] ?? [] as $levelIndex => $level)
            <tr>
                @if ($levelIndex === 0)
                    <th
                        colspan="{{ $rowLabelCount }}"
                        rowspan="{{ $rowspan }}"
                        class="sticky left-0 z-10 border-b border-r border-default bg-surface-muted/50 px-3 py-2 text-left text-2xs font-semibold uppercase tracking-wider text-body-subtle"
                    >
                        {{ implode(' / ', $pivot['row_axis_titles'] ?? []) }}
                    </th>
                @endif

                @foreach ($level as $header)
                    <th
                        colspan="{{ $header['colspan'] }}"
                        class="border-b border-default px-3 py-2 text-center text-2xs font-semibold uppercase tracking-wider text-body-subtle"
                    >
                        {{ $header['label'] }}
                    </th>
                @endforeach

                @if ($levelIndex === 0)
                    <th
                        colspan="{{ $pivot['grand_header']['colspan'] ?? 1 }}"
                        rowspan="{{ $rowspan }}"
                        class="border-b border-l border-default bg-brand-soft px-3 py-2 text-center text-2xs font-semibold uppercase tracking-wider text-fg-brand"
                    >
                        {{ $pivot['grand_header']['label'] ?? __('Total') }}
                    </th>
                @endif
            </tr>
        @endforeach

        @if (($pivot['header_levels'] ?? []) === [] && ($pivot['row_specs'] ?? []) !== [])
            <tr>
                <th
                    colspan="{{ $rowLabelCount }}"
                    rowspan="{{ $rowspan }}"
                    class="sticky left-0 z-10 border-b border-r border-default bg-surface-muted/50 px-3 py-2 text-left text-2xs font-semibold uppercase tracking-wider text-body-subtle"
                >
                    {{ implode(' / ', $pivot['row_axis_titles'] ?? []) }}
                </th>
                <th
                    colspan="{{ $pivot['measure_count'] ?? 1 }}"
                    class="border-b border-l border-default bg-brand-soft px-3 py-2 text-center text-2xs font-semibold uppercase tracking-wider text-fg-brand"
                >
                    {{ $pivot['grand_header']['label'] ?? __('Total') }}
                </th>
            </tr>
        @endif

        @if (($pivot['header_levels'] ?? []) !== [] || ($pivot['measure_count'] ?? 1) > 1)
            <tr>
                @foreach ($pivot['measure_label_row'] ?? [] as $measure)
                    <th class="border-b border-default px-3 py-1.5 text-right text-2xs font-medium text-body-subtle">
                        {{ $measure['label'] }}
                    </th>
                @endforeach
            </tr>
        @endif
    </thead>

    <tbody class="divide-y divide-default">
        @forelse ($pivot['body_rows'] ?? [] as $row)
            <tr class="transition-colors hover:bg-surface-muted/40">
                @foreach ($row['labels'] as $labelIndex => $label)
                    <td @class([
                        'px-3 py-2 text-sm font-medium text-heading',
                        'sticky left-0 z-[1] bg-surface' => $labelIndex === 0,
                    ])>
                        {{ $label }}
                    </td>
                @endforeach

                @foreach ($row['cells'] as $cell)
                    <td @class([
                        'px-3 py-2 text-right tabular-nums',
                        'bg-brand-soft/40 font-semibold text-fg-brand' => $cell['is_total'],
                    ])>
                        {{ $cell['display'] }}
                    </td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td
                    colspan="{{ $rowLabelCount + ($pivot['measure_count'] ?? 1) + (($pivot['col_combos_count'] ?? 0) * ($pivot['measure_count'] ?? 1)) }}"
                    class="px-3 py-8 text-center text-sm text-body-subtle"
                >
                    {{ __('No data to pivot. Adjust the controls above.') }}
                </td>
            </tr>
        @endforelse
    </tbody>

    @if (($pivot['body_rows'] ?? []) !== [])
        <tfoot>
            <tr class="bg-surface-muted/50 font-semibold text-fg-brand">
                <td
                    colspan="{{ $rowLabelCount }}"
                    class="sticky left-0 z-[1] bg-surface-muted/50 px-3 py-2 text-2xs uppercase tracking-wider"
                >
                    {{ __('Total') }}
                </td>
                @foreach ($pivot['col_totals'] ?? [] as $cell)
                    <td class="px-3 py-2 text-right tabular-nums">{{ $cell['display'] }}</td>
                @endforeach
            </tr>
        </tfoot>
    @endif
</table>
