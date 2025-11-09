<?php

namespace App\Livewire\Deployments;

use Livewire\Component;
use App\Models\Deployment;
use Livewire\WithPagination;

class DeploymentList extends Component
{
    use WithPagination;

    public $statusFilter = '';

    public function render()
    {
        $deployments = Deployment::with(['project', 'server'])
            ->where('user_id', auth()->id())
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->latest()
            ->paginate(20);

        return view('livewire.deployments.deployment-list', [
            'deployments' => $deployments,
        ]);
    }
}

