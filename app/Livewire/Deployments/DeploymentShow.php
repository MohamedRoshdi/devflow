<?php

namespace App\Livewire\Deployments;

use Livewire\Component;
use App\Models\Deployment;

class DeploymentShow extends Component
{
    public Deployment $deployment;

    public function mount(Deployment $deployment)
    {
        $this->authorize('view', $deployment);
        $this->deployment = $deployment;
    }

    public function render()
    {
        return view('livewire.deployments.deployment-show');
    }
}

