<?php

namespace App\Livewire\Home;

use Livewire\Component;

class HomePublic extends Component
{
    /**
     * This is a marketing landing page component.
     * It displays static marketing content about DevFlow Pro.
     * No project listing or filtering functionality.
     */
    public function render()
    {
        return view('livewire.home.home-public')->layout('layouts.marketing');
    }
}
