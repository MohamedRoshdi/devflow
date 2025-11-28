<?php

declare(strict_types=1);

namespace App\Livewire\Docs;

use Livewire\Component;

class ApiDocumentation extends Component
{
    public string $activeSection = 'authentication';
    public string $activeEndpoint = 'auth-overview';

    public function setSection(string $section, string $endpoint = null)
    {
        $this->activeSection = $section;
        $this->activeEndpoint = $endpoint ?? $this->getDefaultEndpoint($section);
    }

    private function getDefaultEndpoint(string $section): string
    {
        return match($section) {
            'authentication' => 'auth-overview',
            'projects' => 'projects-list',
            'servers' => 'servers-list',
            'deployments' => 'deployments-list',
            default => 'auth-overview',
        };
    }

    public function render()
    {
        return view('livewire.docs.api-documentation');
    }
}
