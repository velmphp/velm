@php
    $transition = $transition ?? [];
    $formFields = $transition['form_fields'] ?? [];
    $values = $values ?? [];
    $errors = $errors ?? [];
    $formError = $formError ?? null;
@endphp

<form
    class="space-y-4"
    data-pv-workflow-transition-form
    data-instance-id="{{ (int) ($instanceId ?? 0) }}"
    data-transition-key="{{ $transitionKey ?? '' }}"
>
    @if ($formError)
        <div class="rounded-md border border-fg-danger/30 bg-danger-soft px-3 py-2 text-sm text-fg-danger">
            {{ $formError }}
        </div>
    @endif

    <h3 class="text-base font-semibold text-heading">{{ $transition['form_title'] ?? $transition['label'] ?? __('Transition') }}</h3>

    @foreach ($formFields as $ff)
        @php
            $name = (string) ($ff['name'] ?? '');
            $label = (string) ($ff['label'] ?? $name);
            $required = ! empty($ff['required']);
            $val = $values[$name] ?? '';
            $isLong = ($ff['type'] ?? '') === 'text' || ($ff['source'] ?? '') === 'stage' && ($ff['type'] ?? 'char') === 'text';
        @endphp
        <label class="block text-sm">
            <span class="font-medium text-heading">{{ $label }}</span>
            @if ($required)<span class="text-fg-danger">*</span>@endif
            @if ($isLong)
                <textarea
                    name="{{ $name }}"
                    rows="4"
                    class="mt-1 w-full rounded-md border border-default px-3 py-2 text-sm @if(isset($errors[$name])) border-fg-danger @endif"
                    @if($required) required @endif
                >{{ $val }}</textarea>
            @else
                <input
                    type="text"
                    name="{{ $name }}"
                    value="{{ $val }}"
                    class="mt-1 w-full rounded-md border border-default px-3 py-2 text-sm @if(isset($errors[$name])) border-fg-danger @endif"
                    @if($required) required @endif
                >
            @endif
            @if (isset($errors[$name]))
                <p class="mt-1 text-xs text-fg-danger">{{ $errors[$name] }}</p>
            @endif
        </label>
    @endforeach

    <div class="flex justify-end gap-2 border-t border-default pt-3">
        <button type="button" class="pv-btn pv-btn-secondary" @click="window.PvWorkflowDialog?.close()">
            {{ __('Cancel') }}
        </button>
        <button type="submit" class="pv-btn pv-btn-primary">
            {{ __('Continue') }}
        </button>
    </div>
</form>

<script>
    document.querySelectorAll('[data-pv-workflow-transition-form]').forEach((form) => {
        if (form.dataset.bound) return;
        form.dataset.bound = '1';
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const instanceId = form.dataset.instanceId;
            const transitionKey = form.dataset.transitionKey;
            const values = {};
            new FormData(form).forEach((v, k) => { values[k] = v; });
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const r = await fetch(`/web/workflow/instances/${instanceId}/transition/${transitionKey}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ values }),
            });
            const data = await r.json().catch(() => ({}));
            if (!r.ok) {
                window.PvWorkflowDialog?.setError(data.message || 'Request failed');
                return;
            }
            window.PvWorkflowDialog?.close(data);
        });
    });
</script>
