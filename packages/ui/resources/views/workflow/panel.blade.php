@php
    $workflowModel = $workflowModel ?? '';
    $workflowRecordId = (int) ($workflowRecordId ?? 0);
@endphp

@if ($workflowModel !== '' && $workflowRecordId > 0)
    @include('velm-ui::workflow.panel-scripts')
@endif
