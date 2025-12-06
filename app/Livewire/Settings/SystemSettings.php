<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\SystemSetting;
use App\Services\SystemSettingsService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SystemSettings extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $settings = [];

    public bool $isSaving = false;

    public bool $saveSuccess = false;

    public string $activeGroup = 'general';

    public function mount(SystemSettingsService $service): void
    {
        $this->loadSettings();
    }

    /**
     * @return \Illuminate\Support\Collection<string, \Illuminate\Database\Eloquent\Collection<int, SystemSetting>>
     */
    #[Computed]
    public function groupedSettings(): \Illuminate\Support\Collection
    {
        return SystemSetting::orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group');
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function groups(): array
    {
        return [
            'general' => 'General Settings',
            'auth' => 'Authentication',
            'features' => 'Features',
            'mail' => 'Mail Configuration',
            'security' => 'Security',
        ];
    }

    public function setActiveGroup(string $group): void
    {
        $this->activeGroup = $group;
    }

    public function loadSettings(): void
    {
        $allSettings = SystemSetting::all();

        foreach ($allSettings as $setting) {
            $this->settings[$setting->key] = $setting->getTypedValue();
        }
    }

    public function toggleSetting(string $key): void
    {
        if (isset($this->settings[$key])) {
            $this->settings[$key] = ! $this->settings[$key];
        }
    }

    public function save(SystemSettingsService $service): void
    {
        $this->isSaving = true;

        try {
            $service->bulkUpdate($this->settings);

            $this->isSaving = false;
            $this->saveSuccess = true;

            $this->dispatch('notification', type: 'success', message: 'System settings saved successfully');
        } catch (\Exception $e) {
            $this->isSaving = false;
            $this->dispatch('notification', type: 'error', message: 'Failed to save settings: '.$e->getMessage());
        }
    }

    public function resetToDefaults(SystemSettingsService $service): void
    {
        try {
            $defaults = $service->getDefaultSettings();

            foreach ($defaults as $default) {
                SystemSetting::set($default['key'], $default['value'], $default['type']);
            }

            $this->loadSettings();
            $service->clearCache();

            $this->dispatch('notification', type: 'success', message: 'Settings reset to defaults');
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to reset settings: '.$e->getMessage());
        }
    }

    public function clearCache(SystemSettingsService $service): void
    {
        $service->clearCache();
        $this->dispatch('notification', type: 'success', message: 'Settings cache cleared');
    }

    public function render()
    {
        return view('livewire.settings.system-settings');
    }
}
