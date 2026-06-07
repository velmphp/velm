@php
    $rows = $initial ?? [];
@endphp

@if ($rows !== [])
    <div class="pv-file-display-grid">
        @foreach ($rows as $row)
            @include('velm-ui::widgets.display.file', ['initial' => [$row]])
        @endforeach
    </div>
@endif
