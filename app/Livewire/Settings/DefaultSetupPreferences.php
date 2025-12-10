<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\UserSettings;
use Livewire\Component;

class DefaultSetupPreferences extends Component
{
    public bool $defaultEnableSsl = true;

    public bool $defaultEnableWebhooks = true;

    public bool $defaultEnableHealthChecks = true;

    public bool $defaultEnableBackups = true;

    public bool $defaultEnableNotifications = true;

    public bool $defaultEnableAutoDeploy = false;

    public string $theme = 'dark';

    public bool $showWizardTips = true;

    public bool $showInlineHelp = true;

    public bool $isSaving = false;

    public bool $saveSuccess = false;

    public function mount(): void
    {
        $settings = UserSettings::getForUser(auth()->user());

        $this->defaultEnableSsl = $settings->default_enable_ssl;
        $this->defaultEnableWebhooks = $settings->default_enable_webhooks;
        $this->defaultEnableHealthChecks = $settings->default_enable_health_checks;
        $this->defaultEnableBackups = $settings->default_enable_backups;
        $this->defaultEnableNotifications = $settings->default_enable_notifications;
        $this->defaultEnableAutoDeploy = $settings->default_enable_auto_deploy;
        $this->theme = $settings->theme;
        $this->showWizardTips = $settings->show_wizard_tips;
        $this->showInlineHelp = auth()->user()->show_inline_help ?? true;
    }

    public function save(): void
    {
        $this->isSaving = true;

        try {
            $settings = UserSettings::getForUser(auth()->user());

            $settings->update([
                'default_enable_ssl' => $this->defaultEnableSsl,
                'default_enable_webhooks' => $this->defaultEnableWebhooks,
                'default_enable_health_checks' => $this->defaultEnableHealthChecks,
                'default_enable_backups' => $this->defaultEnableBackups,
                'default_enable_notifications' => $this->defaultEnableNotifications,
                'default_enable_auto_deploy' => $this->defaultEnableAutoDeploy,
                'theme' => $this->theme,
                'show_wizard_tips' => $this->showWizardTips,
            ]);

            // Update user's inline help preference
            auth()->user()->update([
                'show_inline_help' => $this->showInlineHelp,
            ]);

            $this->isSaving = false;
            $this->saveSuccess = true;

            // Reset success message after 3 seconds
            $this->dispatch('notification', type: 'success', message: 'Default setup preferences saved successfully');

            // Clear the success message after delay
            $this->js('setTimeout(() => { window.location.reload(); }, 1000);');
        } catch (\Exception $e) {
            $this->isSaving = false;
            $this->dispatch('notification', type: 'error', message: 'Failed to save preferences: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.settings.default-setup-preferences');
    }
}
