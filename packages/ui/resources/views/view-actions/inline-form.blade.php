<form class="space-y-4" data-velm-inline-action-form data-submit-url="{{ $submitUrl }}">
    <h3 class="text-base font-semibold text-heading">{{ __($title) }}</h3>

    @foreach ($fields as $field)
        @php
            $name = (string) ($field['name'] ?? '');
            $label = (string) ($field['label'] ?? $name);
            $required = ! empty($field['required']);
            $type = (string) ($field['type'] ?? 'char');
            $value = $field['value'] ?? '';
        @endphp

        <label class="block text-sm">
            <span class="font-medium text-heading">{{ $label }}</span>
            @if ($required)
                <span class="text-fg-danger">*</span>
            @endif

            @if ($type === 'boolean')
                <span class="mt-1 flex items-center gap-2">
                    <input
                        type="checkbox"
                        name="{{ $name }}"
                        value="1"
                        class="rounded border-default text-fg-brand focus:ring-fg-brand"
                        @checked(filter_var($value, FILTER_VALIDATE_BOOLEAN))
                    />
                    <span class="text-body-subtle">{{ filter_var($value, FILTER_VALIDATE_BOOLEAN) ? __('Yes') : __('No') }}</span>
                </span>
            @elseif ($type === 'many2one')
                <select
                    name="{{ $name }}"
                    class="mt-1 w-full rounded-md border border-default px-3 py-2 text-sm"
                    @if($required) required @endif
                >
                    <option value="">{{ __('—') }}</option>
                    @foreach ($field['options'] ?? [] as $option)
                        <option value="{{ $option['id'] }}" @selected((string) $value === (string) $option['id'])>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>
            @elseif ($type === 'text' || ! empty($field['multiline']))
                <textarea
                    name="{{ $name }}"
                    rows="4"
                    class="mt-1 w-full rounded-md border border-default px-3 py-2 text-sm"
                    @if($required) required @endif
                >{{ is_scalar($value) ? $value : '' }}</textarea>
            @elseif ($type === 'integer')
                <input
                    type="number"
                    name="{{ $name }}"
                    value="{{ is_scalar($value) ? $value : '' }}"
                    class="mt-1 w-full rounded-md border border-default px-3 py-2 text-sm"
                    @if($required) required @endif
                />
            @else
                <input
                    type="text"
                    name="{{ $name }}"
                    value="{{ is_scalar($value) ? $value : '' }}"
                    class="mt-1 w-full rounded-md border border-default px-3 py-2 text-sm"
                    @if($required) required @endif
                />
            @endif
        </label>
    @endforeach

    <p class="hidden text-sm text-fg-danger" data-velm-inline-action-error></p>

    <div class="flex justify-end gap-2 border-t border-default pt-3">
        <button type="button" class="pv-btn pv-btn-secondary" onclick="window.PvWorkflowDialog?.close()">
            {{ __('Cancel') }}
        </button>
        <button type="submit" class="pv-btn pv-btn-primary">
            {{ $recordId > 0 ? __('Save') : __('Create') }}
        </button>
    </div>
</form>

<script>
    document.querySelectorAll('[data-velm-inline-action-form]').forEach((form) => {
        if (form.dataset.bound) return;
        form.dataset.bound = '1';

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const submitButton = form.querySelector('button[type="submit"]');
            const busyMessage = submitButton?.textContent?.trim() || @js(__('Saving…'));

            await window.pvWithActionBusy(async () => {
                const errorEl = form.querySelector('[data-velm-inline-action-error]');
                const values = {};
                new FormData(form).forEach((raw, key) => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input && input.type === 'checkbox') {
                        values[key] = input.checked;
                        return;
                    }
                    values[key] = raw;
                });
                form.querySelectorAll('input[type="checkbox"][name]').forEach((input) => {
                    if (!(input.name in values)) values[input.name] = false;
                });

                const response = await fetch(form.dataset.submitUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-Token': window.pvCsrf ? window.pvCsrf() : '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(values),
                });

                let payload = null;
                try {
                    payload = await response.json();
                } catch (error) {
                    payload = null;
                }

                if (!response.ok) {
                    const message = payload?.message || @js(__('Could not save.'));
                    if (errorEl) {
                        errorEl.textContent = message;
                        errorEl.classList.remove('hidden');
                    } else {
                        window.PvWorkflowDialog?.setError(message);
                    }
                    return;
                }

                window.PvWorkflowDialog?.close(payload);
            }, { message: busyMessage });
        });
    });
</script>
