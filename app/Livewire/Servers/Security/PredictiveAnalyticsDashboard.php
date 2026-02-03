<?php

declare(strict_types=1);

namespace App\Livewire\Servers\Security;

use App\Models\SecurityPrediction;
use App\Models\Server;
use App\Services\Security\PredictiveSecurityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PredictiveAnalyticsDashboard extends Component
{
    use AuthorizesRequests, WithPagination;

    public Server $server;

    public string $statusFilter = 'active';

    public bool $isAnalyzing = false;

    public ?string $flashMessage = null;

    public ?string $flashType = null;

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);
        $this->server = $server;
    }

    public function runAnalysis(): void
    {
        $this->isAnalyzing = true;

        try {
            $service = app(PredictiveSecurityService::class);
            $predictions = $service->analyzeServer($this->server);

            $count = count($predictions);
            $this->flashMessage = "Analysis complete: {$count} prediction(s) generated";
            $this->flashType = $count > 0 ? 'warning' : 'success';
        } catch (\Exception $e) {
            $this->flashMessage = 'Analysis failed: '.$e->getMessage();
            $this->flashType = 'error';
        }

        $this->isAnalyzing = false;
    }

    public function captureBaseline(): void
    {
        try {
            $service = app(PredictiveSecurityService::class);
            $baseline = $service->captureBaseline($this->server);

            $this->flashMessage = 'Baseline captured: '.count($baseline->running_services).' services, '.count($baseline->listening_ports).' ports';
            $this->flashType = 'success';
            $this->server->refresh();
        } catch (\Exception $e) {
            $this->flashMessage = 'Baseline capture failed: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function acknowledgePrediction(int $predictionId): void
    {
        $prediction = SecurityPrediction::find($predictionId);
        if ($prediction && $prediction->server_id === $this->server->id) {
            $prediction->acknowledge(auth()->id());
            $this->flashMessage = 'Prediction acknowledged';
            $this->flashType = 'info';
        }
    }

    public function resolvePrediction(int $predictionId): void
    {
        $prediction = SecurityPrediction::find($predictionId);
        if ($prediction && $prediction->server_id === $this->server->id) {
            $prediction->resolve();
            $this->flashMessage = 'Prediction resolved';
            $this->flashType = 'success';
        }
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, \App\Models\SecurityPrediction>
     */
    public function getPredictionsProperty(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->server->securityPredictions();

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function getBaselineProperty(): ?\App\Models\SecurityBaseline
    {
        return $this->server->latestBaseline;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getBaselineDriftProperty(): array
    {
        try {
            $service = app(PredictiveSecurityService::class);

            return $service->detectBaselineDrift($this->server);
        } catch (\Exception) {
            return [];
        }
    }

    public function render(): View
    {
        return view('livewire.servers.security.predictive-analytics-dashboard');
    }
}
