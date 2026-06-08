@props([
    'paginator' => null,
])

@if ($paginator !== null && $paginator->total() > 0)
    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        @include('velm-ui::pagination.page-size')

        @if ($paginator->hasPages())
            <div class="flex justify-end">
                {{ $paginator->links($this->listPaginationView(), ['scrollTo' => '[data-pv-form-shell]']) }}
            </div>
        @endif
    </div>
@endif
