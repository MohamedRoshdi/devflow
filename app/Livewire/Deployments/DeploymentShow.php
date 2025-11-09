<?php

namespace App\Livewire\Deployments;

use Livewire\Component;
use App\Models\Deployment;

class DeploymentShow extends Component
{
    public Deployment $deployment;

    public function mount(Deployment $deployment)
    {
        // Check if deployment belongs to current user
        if ($deployment->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this deployment.');
        }
        
        $this->deployment = $deployment;
    }

    public function render()
    {
        return view('livewire.deployments.deployment-show');
    }
}

