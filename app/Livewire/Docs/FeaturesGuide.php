<?php

declare(strict_types=1);

namespace App\Livewire\Docs;

use Livewire\Component;

class FeaturesGuide extends Component
{
    public string $activeTab = 'overview';
    public string $activeFeature = '';

    /**
     * @var array<string, array{title: string, description: string, icon: string, color: string, features: array<int, array{name: string, description: string}>}>
     */
    protected array $featureCategories = [
        'core' => [
            'title' => 'Core Features',
            'description' => 'Essential features for managing your projects and servers',
            'icon' => 'cube',
            'color' => 'blue',
            'features' => [
                ['name' => 'Project Management', 'description' => 'Create, edit, and manage web applications'],
                ['name' => 'Server Management', 'description' => 'Connect and monitor your servers via SSH'],
                ['name' => 'One-Click Deployments', 'description' => 'Deploy with a single click, watch progress in real-time'],
                ['name' => 'Docker Integration', 'description' => 'Full Docker container management'],
                ['name' => 'Environment Variables', 'description' => 'Secure configuration management'],
            ],
        ],
        'devops' => [
            'title' => 'DevOps Features',
            'description' => 'Advanced automation and infrastructure management',
            'icon' => 'cog',
            'color' => 'purple',
            'features' => [
                ['name' => 'CI/CD Pipelines', 'description' => 'Build automated deployment pipelines'],
                ['name' => 'Kubernetes Integration', 'description' => 'Deploy to K8s clusters'],
                ['name' => 'Custom Scripts', 'description' => 'Run custom deployment scripts'],
                ['name' => 'Webhooks', 'description' => 'Auto-deploy on git push'],
            ],
        ],
        'monitoring' => [
            'title' => 'Monitoring & Alerts',
            'description' => 'Keep track of your infrastructure health',
            'icon' => 'chart-bar',
            'color' => 'green',
            'features' => [
                ['name' => 'Real-time Metrics', 'description' => 'CPU, RAM, Disk monitoring'],
                ['name' => 'Health Checks', 'description' => 'Automated uptime monitoring'],
                ['name' => 'Resource Alerts', 'description' => 'Get notified when thresholds are exceeded'],
                ['name' => 'Log Aggregation', 'description' => 'Centralized log viewing and search'],
            ],
        ],
        'security' => [
            'title' => 'Security Features',
            'description' => 'Protect your servers and applications',
            'icon' => 'shield-check',
            'color' => 'red',
            'features' => [
                ['name' => 'Firewall Management', 'description' => 'UFW firewall control from dashboard'],
                ['name' => 'Fail2ban', 'description' => 'Intrusion prevention system'],
                ['name' => 'SSH Hardening', 'description' => 'Secure SSH configuration'],
                ['name' => 'SSL Certificates', 'description' => "Let's Encrypt auto-renewal"],
                ['name' => 'Security Scans', 'description' => 'Automated security audits'],
            ],
        ],
        'team' => [
            'title' => 'Team Collaboration',
            'description' => 'Work together efficiently',
            'icon' => 'users',
            'color' => 'indigo',
            'features' => [
                ['name' => 'Teams', 'description' => 'Organize users into teams'],
                ['name' => 'Role-Based Access', 'description' => 'Owner, Admin, Member, Viewer roles'],
                ['name' => 'Audit Logs', 'description' => 'Track all actions'],
                ['name' => 'Notifications', 'description' => 'Slack, Discord, Teams alerts'],
            ],
        ],
        'advanced' => [
            'title' => 'Advanced Features',
            'description' => 'Enterprise-grade capabilities',
            'icon' => 'sparkles',
            'color' => 'amber',
            'features' => [
                ['name' => 'Multi-Tenant', 'description' => 'Manage SaaS applications with multiple clients'],
                ['name' => 'Database Backups', 'description' => 'Scheduled backups with cloud storage'],
                ['name' => 'Server Backups', 'description' => 'Full server backup and restore'],
                ['name' => 'API Access', 'description' => 'RESTful API for automation'],
            ],
        ],
    ];

    /**
     * @return array<string, array{title: string, description: string, icon: string, color: string, features: array<int, array{name: string, description: string}>}>
     */
    public function getFeatureCategoriesProperty(): array
    {
        return $this->featureCategories;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->activeFeature = '';
    }

    public function showFeatureDetail(string $feature): void
    {
        $this->activeFeature = $feature;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.docs.features-guide');
    }
}
