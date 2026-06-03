@php
    $shell = $velmShell ?? [
        'companies' => [],
        'current_company_id' => null,
        'current_company_name' => '',
        'allow_all_companies' => false,
    ];
    $companies = $shell['companies'] ?? [];
    $currentCompanyId = $shell['current_company_id'] ?? null;
    $currentCompanyName = (string) ($shell['current_company_name'] ?? '');
    $allowAllCompanies = (bool) ($shell['allow_all_companies'] ?? false);
    $activeLabel = $currentCompanyName !== ''
        ? $currentCompanyName
        : ($allowAllCompanies ? __('All companies') : __('Select company'));
@endphp

@if ($companies !== [] || $currentCompanyId !== null)
    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
        <button
            type="button"
            @click="open = ! open"
            class="flex max-w-[12rem] items-center gap-2 rounded-md px-2.5 py-1.5 text-sm text-body transition-colors hover:bg-neutral-secondary hover:text-heading"
            aria-haspopup="true"
            :aria-expanded="open.toString()"
            title="{{ $activeLabel }}"
        >
            <svg class="h-4 w-4 shrink-0 text-body-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"
                />
            </svg>
            <span class="min-w-0 truncate font-medium text-heading">
                {{ $activeLabel }}
            </span>
            <svg class="h-3 w-3 shrink-0 text-body-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition
            class="absolute end-0 top-full z-50 mt-2 w-56 overflow-hidden rounded-lg border border-default bg-neutral-primary shadow-lg"
        >
            <ul class="py-1 text-sm">
                @if ($allowAllCompanies)
                    <li>
                        <form method="post" action="{{ route('velm.switch-company') }}">
                            @csrf
                            <input type="hidden" name="company_id" value="" />
                            <button
                                type="submit"
                                @class([
                                    'w-full px-3 py-1.5 text-start text-body transition-colors hover:bg-neutral-secondary',
                                    'font-medium text-fg-brand' => $currentCompanyId === null,
                                ])
                                @if ($currentCompanyId === null) aria-current="true" @endif
                            >
                                {{ __('All companies') }}
                            </button>
                        </form>
                    </li>
                @endif
                @foreach ($companies as $company)
                    <li>
                        <form method="post" action="{{ route('velm.switch-company') }}">
                            @csrf
                            <input type="hidden" name="company_id" value="{{ $company['id'] }}" />
                            <button
                                type="submit"
                                @class([
                                    'w-full px-3 py-1.5 text-start text-body transition-colors hover:bg-neutral-secondary',
                                    'font-medium text-fg-brand' => ($currentCompanyId ?? null) === $company['id'],
                                ])
                                @if (($currentCompanyId ?? null) === $company['id']) aria-current="true" @endif
                            >
                                {{ $company['name'] }}
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
