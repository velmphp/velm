<div class="rounded-lg border border-default bg-surface p-6 text-sm text-body">
    <p class="font-medium text-heading">{{ ucfirst($viewType) }} renderer pending</p>
    <p class="mt-2 text-body-subtle">
        Route and arch schema are wired for <code>{{ $module }}.{{ $viewName }}</code>.
        Livewire presentation will land in a follow-up slice.
    </p>

    <dl class="mt-4 grid gap-2 sm:grid-cols-2">
        <div>
            <dt class="text-xs uppercase tracking-wide text-body-subtle">Model</dt>
            <dd><code>{{ $arch['model'] ?? '—' }}</code></dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-body-subtle">View type</dt>
            <dd><code>{{ $viewType }}</code></dd>
        </div>
        @if ($viewType === 'kanban')
            <div>
                <dt class="text-xs uppercase tracking-wide text-body-subtle">Group by</dt>
                <dd><code>{{ $arch['group_by'] ?? '—' }}</code></dd>
            </div>
        @endif
        @if ($viewType === 'graph')
            <div>
                <dt class="text-xs uppercase tracking-wide text-body-subtle">Group by</dt>
                <dd><code>{{ $arch['group_by'] ?? '—' }}</code></dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-body-subtle">Chart</dt>
                <dd><code>{{ $arch['chart'] ?? 'bar' }}</code></dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs uppercase tracking-wide text-body-subtle">Measures</dt>
                <dd><code>{{ json_encode($arch['measures'] ?? [], JSON_THROW_ON_ERROR) }}</code></dd>
            </div>
        @endif
        @if ($viewType === 'pivot')
            <div>
                <dt class="text-xs uppercase tracking-wide text-body-subtle">Rows</dt>
                <dd><code>{{ json_encode($arch['rows'] ?? [], JSON_THROW_ON_ERROR) }}</code></dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-body-subtle">Columns</dt>
                <dd><code>{{ json_encode($arch['cols'] ?? [], JSON_THROW_ON_ERROR) }}</code></dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs uppercase tracking-wide text-body-subtle">Measures</dt>
                <dd><code>{{ json_encode($arch['measures'] ?? [], JSON_THROW_ON_ERROR) }}</code></dd>
            </div>
        @endif
    </dl>
</div>
