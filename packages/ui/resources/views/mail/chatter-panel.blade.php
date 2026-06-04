@php
    $mailModel = $mailModel ?? '';
    $mailRecordId = (int) ($mailRecordId ?? 0);
@endphp

@if ($mailModel !== '' && $mailRecordId > 0)
    @include('velm-ui::mail.chatter-scripts')
@endif
