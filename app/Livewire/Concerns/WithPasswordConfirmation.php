<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Hash;

trait WithPasswordConfirmation
{
    public bool $showPasswordConfirm = false;

    public string $confirmPassword = '';

    public string $pendingAction = '';

    public string $pendingActionParam = '';

    public string $pendingActionLabel = '';

    public function confirmDestructiveAction(string $action, string $param, string $label): void
    {
        $this->confirmPassword = '';
        $this->pendingAction = $action;
        $this->pendingActionParam = $param;
        $this->pendingActionLabel = $label;
        $this->showPasswordConfirm = true;
    }

    public function executeConfirmedAction(): void
    {
        $user = auth()->user();

        if ($user === null || ! Hash::check($this->confirmPassword, $user->password)) {
            $this->addError('confirmPassword', 'Incorrect password.');

            return;
        }

        $action = $this->pendingAction;
        $param = $this->pendingActionParam;

        $this->resetConfirmation();

        if ($action !== '' && method_exists($this, $action)) {
            $this->$action($param);
        }
    }

    public function cancelConfirmation(): void
    {
        $this->resetConfirmation();
    }

    private function resetConfirmation(): void
    {
        $this->showPasswordConfirm = false;
        $this->confirmPassword = '';
        $this->pendingAction = '';
        $this->pendingActionParam = '';
        $this->pendingActionLabel = '';
    }
}
