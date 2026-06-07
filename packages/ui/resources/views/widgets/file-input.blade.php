@include('velm-ui::widgets.file-picker-field', [
    'wireKey' => $wireKey,
    'multi' => false,
    'readonly' => $readonly ?? false,
    'accept' => $accept ?? '',
    'initial' => $initial ?? [],
    'pickerTitle' => $pickerTitle ?? __('Pick a file'),
])
