<?php

namespace App\Livewire\Deployments;

use Livewire\Component;
use App\Models\Deployment;

class DeploymentShow extends Component
{
    public Deployment $deployment;
    public $currentStep = '';
    public $progress = 0;

    public function mount(Deployment $deployment)
    {
        // All deployments are shared across all users
        $this->deployment = $deployment;
        $this->analyzeProgress();
    }

    public function refresh()
    {
        $this->deployment = $this->deployment->fresh();
        $this->analyzeProgress();
    }

    protected function analyzeProgress()
    {
        $logs = $this->deployment->output_log ?? '';
        
        // Determine current step and progress from logs
        if (str_contains($logs, '=== Cloning Repository ===')) {
            $this->currentStep = 'Cloning repository';
            $this->progress = 10;
        }
        
        if (str_contains($logs, '✓ Repository cloned successfully')) {
            $this->currentStep = 'Recording commit information';
            $this->progress = 20;
        }
        
        if (str_contains($logs, '✓ Commit information recorded')) {
            $this->currentStep = 'Building Docker container';
            $this->progress = 25;
        }
        
        if (str_contains($logs, 'Building Docker Container')) {
            $this->currentStep = 'Installing system packages';
            $this->progress = 30;
        }
        
        if (str_contains($logs, 'Installing shared extensions')) {
            $this->currentStep = 'Installing PHP extensions';
            $this->progress = 40;
        }
        
        if (str_contains($logs, 'Installing dependencies from lock file')) {
            $this->currentStep = 'Installing Composer dependencies';
            $this->progress = 50;
        }
        
        if (str_contains($logs, 'npm install') || str_contains($logs, 'Installing node modules')) {
            $this->currentStep = 'Installing Node dependencies';
            $this->progress = 60;
        }
        
        if (str_contains($logs, 'npm run build') || str_contains($logs, 'vite build')) {
            $this->currentStep = 'Building frontend assets';
            $this->progress = 75;
        }
        
        if (str_contains($logs, 'Laravel optimization') || str_contains($logs, 'config:cache')) {
            $this->currentStep = 'Optimizing Laravel';
            $this->progress = 85;
        }
        
        if (str_contains($logs, '✓ Build successful') || str_contains($logs, 'Build complete')) {
            $this->currentStep = 'Starting container';
            $this->progress = 90;
        }
        
        if (str_contains($logs, 'Container started')) {
            $this->currentStep = 'Deployment complete';
            $this->progress = 100;
        }
        
        if ($this->deployment->status === 'success') {
            $this->currentStep = 'Deployment successful';
            $this->progress = 100;
        }
        
        if ($this->deployment->status === 'failed') {
            $this->currentStep = 'Deployment failed';
            $this->progress = 0;
        }
    }

    public function render()
    {
        return view('livewire.deployments.deployment-show');
    }
}

