<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Velm\Admin\Support\UserAccountUpdater;
use Velm\Admin\Support\VelmNotify;
use Velm\Admin\Support\VelmPanel;
use Velm\Environment;

final class ProfilePage extends VelmShellPage
{
    protected static ?string $slug = 'account/profile';

    #[Locked]
    public int $userId = 0;

    /** @var array{name: string, email: string, company_id: int|string|null} */
    public array $data = [
        'name' => '',
        'email' => '',
        'company_id' => null,
    ];

    public function mount(): void
    {
        $authId = VelmPanel::auth()->id();
        $this->userId = is_numeric($authId) ? (int) $authId : 0;

        if ($this->userId <= 0) {
            abort(403);
        }

        $env = app(Environment::class);
        $row = $env->withAclBypass(function () use ($env): array {
            $rows = $env->browse('res.users', [$this->userId])->read(['name', 'email', 'company_id']);

            return $rows[0] ?? [];
        });

        $this->data = [
            'name' => (string) ($row['name'] ?? VelmPanel::auth()->user()?->name ?? ''),
            'email' => (string) ($row['email'] ?? VelmPanel::auth()->user()?->email ?? ''),
            'company_id' => isset($row['company_id']) && is_numeric($row['company_id'])
                ? (int) $row['company_id']
                : null,
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('My profile');
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public function companyOptions(): array
    {
        $env = app(Environment::class);
        $ids = $env->allowedCompanyIds();

        if ($ids === []) {
            return [];
        }

        return $env->withAclBypass(function () use ($env, $ids): array {
            $options = [];

            foreach ($env->browse('res.company', $ids)->read(['id', 'name']) as $row) {
                $id = (int) ($row['id'] ?? 0);

                if ($id <= 0) {
                    continue;
                }

                $options[] = [
                    'value' => $id,
                    'label' => (string) ($row['name'] ?? $id),
                ];
            }

            usort($options, fn (array $a, array $b): int => strcasecmp($a['label'], $b['label']));

            return $options;
        });
    }

    public function saveProfile(UserAccountUpdater $updater): void
    {
        $companyRules = ['nullable', 'integer'];

        $options = $this->companyOptions();

        if ($options !== []) {
            $companyRules[] = Rule::in(array_column($options, 'value'));
        }

        $validated = $this->validate([
            'data.name' => ['required', 'string', 'max:255'],
            'data.email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->userId),
            ],
            'data.company_id' => $companyRules,
        ]);

        $payload = [
            'name' => $validated['data']['name'],
            'email' => $validated['data']['email'],
        ];

        if ($options !== []) {
            $rawCompany = $validated['data']['company_id'] ?? null;
            $payload['company_id'] = $rawCompany === null || $rawCompany === ''
                ? null
                : (int) $rawCompany;
        }

        $updater->updateProfile($this->userId, $payload);

        VelmNotify::flash('success', __('Profile saved'));
    }

    public function render()
    {
        return view('velm-ui::pages.account-profile');
    }
}
