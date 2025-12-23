<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Services\SystemSettingsService;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = new SystemSettingsService;
        $settings = $service->getDefaultSettings();

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                    'label' => $setting['label'] ?? null,
                    'description' => $setting['description'] ?? null,
                    'is_public' => $setting['is_public'] ?? false,
                    'is_encrypted' => $setting['is_encrypted'] ?? false,
                ]
            );
        }

        $this->command->info('System settings seeded successfully!');
        $this->command->info('Total settings: '.count($settings));
    }
}
