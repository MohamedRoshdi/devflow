<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Hero Section with Gradient -->
        <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 dark:from-emerald-600 dark:via-teal-600 dark:to-cyan-600 p-8 shadow-xl overflow-hidden">
            <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white">Analytics Dashboard</h1>
                    </div>
                    <p class="text-white/90 text-lg">Track performance metrics and insights across all your projects</p>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="mb-8">
            <nav class="flex space-x-1 bg-white dark:bg-gray-800 rounded-xl p-1 shadow-lg border border-gray-100 dark:border-gray-700">
                <button wire:click="$set('activeTab', 'overview')"
                        class="flex-1 px-4 py-3 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'overview' ? 'bg-emerald-500 text-white shadow-md' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    Overview
                </button>
                <button wire:click="$set('activeTab', 'charts')"
                        class="flex-1 px-4 py-3 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'charts' ? 'bg-emerald-500 text-white shadow-md' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    Charts
                </button>
                <button wire:click="$set('activeTab', 'costs')"
                        class="flex-1 px-4 py-3 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'costs' ? 'bg-emerald-500 text-white shadow-md' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    Cost Estimation
                </button>
            </nav>
        </div>

        <!-- Filters Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3 mb-6">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Filters</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Time Period</label>
                    <select wire:model.live="selectedPeriod" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-gray-900 dark:text-white">
                        <option value="24hours">Last 24 Hours</option>
                        <option value="7days">Last 7 Days</option>
                        <option value="30days">Last 30 Days</option>
                        <option value="90days">Last 90 Days</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Project Filter</label>
                    <select wire:model.live="selectedProject" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-gray-900 dark:text-white">
                        <option value="">All Projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Server Filter</label>
                    <select wire:model.live="selectedServer" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-gray-900 dark:text-white">
                        <option value="">All Servers</option>
                        @foreach($servers as $server)
                            <option value="{{ $server->id }}">{{ $server->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

    {{-- ============ OVERVIEW TAB ============ --}}
    @if($activeTab === 'overview')
        <!-- Deployment Statistics -->
        <div class="mb-10">
            <div class="flex items-center gap-3 mb-6">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Deployment Statistics</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                    <div class="relative p-6 text-white">
                        <p class="text-sm font-medium text-white/80 mb-2">Total Deployments</p>
                        <p class="text-4xl font-extrabold">{{ $deploymentStats['total'] }}</p>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                    <div class="relative p-6 text-white">
                        <p class="text-sm font-medium text-white/80 mb-2">Successful</p>
                        <p class="text-4xl font-extrabold">{{ $deploymentStats['successful'] }}</p>
                        <p class="text-xs text-white/70 mt-2">{{ $deploymentStats['total'] > 0 ? round(($deploymentStats['successful'] / $deploymentStats['total']) * 100, 1) : 0 }}% success rate</p>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-gradient-to-br from-red-500 to-pink-600 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                    <div class="relative p-6 text-white">
                        <p class="text-sm font-medium text-white/80 mb-2">Failed</p>
                        <p class="text-4xl font-extrabold">{{ $deploymentStats['failed'] }}</p>
                        <p class="text-xs text-white/70 mt-2">{{ $deploymentStats['total'] > 0 ? round(($deploymentStats['failed'] / $deploymentStats['total']) * 100, 1) : 0 }}% failure rate</p>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                    <div class="relative p-6 text-white">
                        <p class="text-sm font-medium text-white/80 mb-2">Avg Duration</p>
                        <p class="text-4xl font-extrabold">{{ $deploymentStats['avg_duration'] ?? 0 }}<span class="text-xl">s</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Server Performance -->
        <div class="mb-10">
            <div class="flex items-center gap-3 mb-6">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                </svg>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Server Performance</h2>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                @foreach([
                    ['label' => 'CPU Usage', 'key' => 'avg_cpu', 'gradient' => 'from-blue-500 to-blue-600', 'bg' => 'bg-blue-100 dark:bg-blue-900/30', 'icon_color' => 'text-blue-600 dark:text-blue-400', 'warn' => 50, 'crit' => 80],
                    ['label' => 'Memory Usage', 'key' => 'avg_memory', 'gradient' => 'from-green-500 to-emerald-600', 'bg' => 'bg-green-100 dark:bg-green-900/30', 'icon_color' => 'text-green-600 dark:text-green-400', 'warn' => 60, 'crit' => 85],
                    ['label' => 'Disk Usage', 'key' => 'avg_disk', 'gradient' => 'from-yellow-500 to-orange-600', 'bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'icon_color' => 'text-yellow-600 dark:text-yellow-400', 'warn' => 70, 'crit' => 90],
                ] as $metric)
                    @php $val = round($serverMetrics->{$metric['key']} ?? 0, 1); @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-6">
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $metric['label'] }}</p>
                            <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $val }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                            <div class="bg-gradient-to-r {{ $metric['gradient'] }} h-4 rounded-full transition-all duration-1000" style="width: {{ min($val, 100) }}%"></div>
                        </div>
                        <p class="mt-3 text-sm font-medium {{ $val < $metric['warn'] ? 'text-green-600 dark:text-green-400' : ($val < $metric['crit'] ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                            Status: {{ $val < $metric['warn'] ? 'Normal' : ($val < $metric['crit'] ? 'Warning' : 'Critical') }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Project Analytics -->
        <div>
            <div class="flex items-center gap-3 mb-6">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Project Analytics</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Total Projects</p>
                    <p class="text-4xl font-extrabold text-gray-900 dark:text-white">{{ $projectAnalytics['total_projects'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Running</p>
                    <p class="text-4xl font-extrabold text-green-600 dark:text-green-400">{{ $projectAnalytics['running'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Stopped</p>
                    <p class="text-4xl font-extrabold text-gray-600 dark:text-gray-400">{{ $projectAnalytics['stopped'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Total Storage</p>
                    <p class="text-4xl font-extrabold text-blue-600 dark:text-blue-400">{{ round($projectAnalytics['total_storage'] / 1024, 2) }}<span class="text-xl"> GB</span></p>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ CHARTS TAB ============ --}}
    @if($activeTab === 'charts')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8" wire:ignore>
            <!-- Deployment Success Rate Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Deployment Success Rate</h3>
                <div class="h-64">
                    <canvas id="successRateChart"></canvas>
                </div>
            </div>

            <!-- Deployment Status Distribution -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Status Distribution</h3>
                <div class="h-64">
                    <canvas id="statusDistributionChart"></canvas>
                </div>
            </div>

            <!-- Deployment Duration Trend -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Deployment Duration Trend</h3>
                <div class="h-64">
                    <canvas id="durationTrendChart"></canvas>
                </div>
            </div>

            <!-- Resource Usage Trends -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Resource Usage Trends</h3>
                <div class="h-64">
                    <canvas id="resourceUsageChart"></canvas>
                </div>
            </div>
        </div>

        @script
        <script>
            Alpine.data('analyticsCharts', () => ({
                charts: {},
                init() {
                    this.initCharts();
                    Livewire.on('charts-updated', () => {
                        this.$nextTick(() => this.initCharts());
                    });
                },
                isDarkMode() {
                    return document.documentElement.classList.contains('dark');
                },
                gridColor() {
                    return this.isDarkMode() ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
                },
                textColor() {
                    return this.isDarkMode() ? '#9CA3AF' : '#6B7280';
                },
                initCharts() {
                    const chartData = @json($chartData);
                    this.initSuccessRateChart(chartData.successRate);
                    this.initStatusDistributionChart(chartData.statusDistribution);
                    this.initDurationTrendChart(chartData.timeTrend);
                    this.initResourceUsageChart(chartData.resourceUsage);
                },
                destroyChart(name) {
                    if (this.charts[name]) {
                        this.charts[name].destroy();
                    }
                },
                initSuccessRateChart(data) {
                    this.destroyChart('successRate');
                    const ctx = document.getElementById('successRateChart');
                    if (!ctx) return;
                    this.charts.successRate = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Success Rate (%)',
                                data: data.success_rates,
                                borderColor: '#10B981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                pointBackgroundColor: '#10B981',
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { min: 0, max: 100, grid: { color: this.gridColor() }, ticks: { color: this.textColor() } },
                                x: { grid: { color: this.gridColor() }, ticks: { color: this.textColor() } }
                            },
                            plugins: { legend: { labels: { color: this.textColor() } } }
                        }
                    });
                },
                initStatusDistributionChart(data) {
                    this.destroyChart('statusDistribution');
                    const ctx = document.getElementById('statusDistributionChart');
                    if (!ctx) return;
                    this.charts.statusDistribution = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.counts,
                                backgroundColor: data.colors || ['#10B981', '#EF4444', '#F59E0B', '#6366F1', '#8B5CF6'],
                                borderWidth: 0,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom', labels: { color: this.textColor(), padding: 16 } }
                            }
                        }
                    });
                },
                initDurationTrendChart(data) {
                    this.destroyChart('durationTrend');
                    const ctx = document.getElementById('durationTrendChart');
                    if (!ctx) return;
                    this.charts.durationTrend = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Avg Duration (s)',
                                data: data.avg_durations,
                                borderColor: '#8B5CF6',
                                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                pointBackgroundColor: '#8B5CF6',
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, grid: { color: this.gridColor() }, ticks: { color: this.textColor() } },
                                x: { grid: { color: this.gridColor() }, ticks: { color: this.textColor() } }
                            },
                            plugins: { legend: { labels: { color: this.textColor() } } }
                        }
                    });
                },
                initResourceUsageChart(data) {
                    this.destroyChart('resourceUsage');
                    const ctx = document.getElementById('resourceUsageChart');
                    if (!ctx) return;
                    this.charts.resourceUsage = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [
                                { label: 'CPU %', data: data.cpu, borderColor: '#3B82F6', backgroundColor: 'rgba(59,130,246,0.1)', fill: false, tension: 0.4 },
                                { label: 'Memory %', data: data.memory, borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,0.1)', fill: false, tension: 0.4 },
                                { label: 'Disk %', data: data.disk, borderColor: '#F59E0B', backgroundColor: 'rgba(245,158,11,0.1)', fill: false, tension: 0.4 },
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { min: 0, max: 100, grid: { color: this.gridColor() }, ticks: { color: this.textColor() } },
                                x: { grid: { color: this.gridColor() }, ticks: { color: this.textColor() } }
                            },
                            plugins: { legend: { position: 'bottom', labels: { color: this.textColor() } } }
                        }
                    });
                }
            }));
        </script>
        @endscript
    @endif

    {{-- ============ COST ESTIMATION TAB ============ --}}
    @if($activeTab === 'costs')
        <div class="space-y-8">
            <!-- Total Cost Card -->
            <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-8 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium mb-1">Estimated Total Cost</p>
                        <p class="text-5xl font-extrabold">${{ number_format($costData['total_cost'], 2) }}</p>
                        <p class="text-white/70 text-sm mt-2">{{ $costData['currency'] }} &middot; Last {{ $costData['period_days'] }} days</p>
                    </div>
                    <div class="p-4 bg-white/20 rounded-2xl">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Cost Breakdown Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Cost Breakdown</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Resource</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Usage</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Unit</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Cost</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($costData['breakdown'] as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $item['resource'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ number_format($item['usage'], 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $item['unit'] }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white text-right">${{ number_format($item['cost'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No cost data available for the selected period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    </div>
</div>
