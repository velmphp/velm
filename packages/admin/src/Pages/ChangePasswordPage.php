<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Velm\Admin\Support\UserAccountUpdater;
use Velm\Admin\Support\VelmNotify;
use Velm\Admin\Support\VelmPanel;

final class ChangePasswordPage extends VelmShellPage
{
    protected static ?string $slug = 'account/password';

    #[Locked]
    public int $userId = 0;

    /** @var array{current_password: string, password: string, password_confirmation: string} */
    public array $data = [
        'current_password' => '',
        'password' => '',
        'password_confirmation' => '',
    ];

    public function mount(): void
    {
        $authId = VelmPanel::auth()->id();
        $this->userId = is_numeric($authId) ? (int) $authId : 0;

        if ($this->userId <= 0) {
            abort(403);
        }
    }

    public function getTitle(): string|Htmlable
    {
        return __('Change password');
    }

    public function savePassword(UserAccountUpdater $updater): void
    {
        $validated = $this->validate([
            'data.current_password' => ['required', 'string'],
            'data.password' => ['required', 'string', 'confirmed', Password::min(8)],
            'data.password_confirmation' => ['required', 'string'],
        ]);

        try {
            $updater->changePassword(
                $this->userId,
                $validated['data']['current_password'],
                $validated['data']['password'],
            );
        } catch (InvalidArgumentException $exception) {
            $this->addError('data.current_password', $exception->getMessage());

            return;
        }

        $this->data = [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ];

        VelmNotify::flash('success', __('Password updated'));
    }

    public function render()
    {
        return view('velm-ui::pages.account-password');
    }
}
