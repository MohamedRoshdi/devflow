# System Logs - Advanced Features Implementation

## ✅ Completed Features

### 1. Export Functionality
- **Service**: `LogExportService` - Exports logs to CSV, JSON, and TXT formats
- **UI**: Added export dropdown menu in SystemLogViewer with format selection
- **Features**:
  - Export filtered logs (respects all current filters)
  - CSV format with headers
  - JSON format with metadata
  - Plain text format with timestamps
  - Streamed downloads for large datasets

**Usage**:
```php
$exportService = app(LogExportService::class);
return $exportService->export($logs, 'csv'); // or 'json', 'txt'
```

### 2. Database Schema
✅ **Migrations Created**:
- `system_logs` - Main system logs table
- `log_alerts` - Pattern-based alert rules
- `log_forwarding_rules` - Log forwarding configuration
- `custom_log_parsers` - Custom parser definitions

## 🚧 Features Ready for Implementation

### 3. Log Alerts System
**Database**: ✅ Migration created
**Models**: Pending
**Services**: Pending
**UI**: Pending

**Features to Implement**:
- Pattern matching (regex or text)
- Threshold-based alerts (e.g., trigger after 5 occurrences in 1 minute)
- Multi-channel notifications (Email, Slack, Webhook)
- Server-specific or global alerts
- Alert history tracking

**Example Alert Rules**:
```php
// Alert when 5+ failed SSH attempts in 60 seconds
[
    'name' => 'SSH Brute Force Detection',
    'pattern' => 'Failed password',
    'log_type' => 'auth',
    'threshold' => 5,
    'time_window' => 60,
    'notification_channels' => ['email', 'slack']
]

// Alert on critical Docker errors
[
    'name' => 'Docker Critical Errors',
    'pattern' => '.*',
    'log_type' => 'docker',
    'log_level' => 'critical',
    'threshold' => 1,
    'notification_channels' => ['slack']
]
```

### 4. Log Analysis & Trending
**Features to Implement**:
- Time-series analytics (hourly, daily, weekly trends)
- Error rate calculations
- Top error sources
- Anomaly detection (unusual spikes in errors)
- Comparative analysis (week-over-week, month-over-month)
- Charts and visualizations (Chart.js/ApexCharts)

**Dashboard Components**:
- Error rate trend line graph
- Log volume by type (pie chart)
- Top 10 error messages
- Peak hours heatmap
- Server health comparison

### 5. Log Forwarding Service
**Database**: ✅ Migration created
**Features to Implement**:
- Forward logs to external services:
  - **Slack**: Send critical logs to Slack channels
  - **Email**: Send digest or real-time email alerts
  - **Webhooks**: POST logs to custom endpoints
  - **Syslog**: Forward to centralized syslog servers
  - **Splunk/ELK**: Integration with enterprise logging platforms

**Example Configuration**:
```php
// Forward critical logs to Slack
[
    'name' => 'Critical to Slack',
    'destination_type' => 'slack',
    'destination_config' => [
        'webhook_url' => 'https://hooks.slack.com/...',
        'channel' => '#alerts'
    ],
    'filters' => [
        'log_level' => ['critical', 'alert', 'emergency']
    ]
]

// Batch forward to Splunk
[
    'name' => 'All Logs to Splunk',
    'destination_type' => 'splunk',
    'destination_config' => [
        'host' => 'splunk.example.com',
        'port' => 8088,
        'token' => '...'
    ],
    'batch_size' => 100,
    'batch_timeout' => 300
]
```

### 6. Real-time Log Streaming
**Technology**: Laravel Reverb (WebSockets) or Pusher
**Features to Implement**:
- Live log updates without page refresh
- Real-time log filtering
- Tail-like functionality for specific servers
- Live search across streams
- Color-coded severity levels
- Auto-scroll with pause functionality

**Frontend**:
```javascript
// Listen for new logs via websocket
Echo.private(`server.${serverId}.logs`)
    .listen('SystemLogCreated', (event) => {
        appendLogToView(event.log);
    });
```

### 7. Custom Log Parsers
**Database**: ✅ Migration created
**Features to Implement**:
- Visual parser builder
- Regex pattern matching
- JSON log parsing
- CSV log parsing
- Field mapping to database columns
- Parser testing with sample logs
- Parser library/templates

**Example Custom Parser**:
```php
// Parse custom application logs
[
    'name' => 'My App JSON Logs',
    'log_type' => 'application',
    'format' => 'json',
    'pattern' => null, // JSON auto-parsed
    'field_mappings' => [
        'timestamp' => 'logged_at',
        'severity' => 'level',
        'msg' => 'message',
        'user_ip' => 'ip_address'
    ]
]

// Parse custom nginx format
[
    'name' => 'Custom Nginx Format',
    'log_type' => 'nginx',
    'format' => 'regex',
    'pattern' => '/^(?<ip>\S+) .* "(?<method>\S+) (?<path>\S+).*" (?<status>\d+)/',
    'field_mappings' => [
        'ip' => 'ip_address',
        'path' => 'metadata.path',
        'status' => 'metadata.http_status'
    ]
]
```

## 📋 Implementation Checklist

### High Priority
- [ ] Create LogAlert model and service
- [ ] Create LogAlertService for pattern matching
- [ ] Create LogAlertManager Livewire component
- [ ] Implement alert notifications (Email, Slack)
- [ ] Add ProcessLogAlerts command (scheduled task)

### Medium Priority
- [ ] Create LogForwardingRule model
- [ ] Create LogForwardingService
- [ ] Implement Slack integration
- [ ] Implement Email forwarding
- [ ] Implement Webhook forwarding
- [ ] Add ProcessLogForwarding command

### Medium Priority (Analytics)
- [ ] Create LogAnalyticsDashboard component
- [ ] Implement trend calculations
- [ ] Add Chart.js integration
- [ ] Create analytics API endpoints
- [ ] Implement caching for heavy queries

### Low Priority (Advanced)
- [ ] Create CustomLogParser model
- [ ] Create LogParserManager component
- [ ] Implement parser testing UI
- [ ] Create parser template library
- [ ] Add parser performance metrics

### Low Priority (Real-time)
- [ ] Set up Laravel Reverb or Pusher
- [ ] Create SystemLogCreated event
- [ ] Add websocket listeners in frontend
- [ ] Create live log viewer component
- [ ] Implement connection management

## 🎯 Quick Implementation Commands

### Create Models
```bash
php artisan make:model LogAlert
php artisan make:model LogForwardingRule
php artisan make:model CustomLogParser
```

### Create Services
```bash
# Create service files
touch app/Services/LogAlertService.php
touch app/Services/LogForwardingService.php
touch app/Services/LogAnalyticsService.php
```

### Create Components
```bash
php artisan make:livewire Logs/LogAlertManager
php artisan make:livewire Logs/LogForwardingManager
php artisan make:livewire Logs/LogAnalyticsDashboard
php artisan make:livewire Logs/LogParserManager
php artisan make:livewire Logs/LiveLogViewer
```

### Create Commands
```bash
php artisan make:command ProcessLogAlertsCommand
php artisan make:command ProcessLogForwardingCommand
php artisan make:command AnalyzeLogTrendsCommand
```

### Add to Kernel Schedule
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Collect system logs every hour
    $schedule->command('system-logs:sync --lines=100')->hourly();

    // Process log alerts every minute
    $schedule->command('logs:process-alerts')->everyMinute();

    // Process log forwarding every 5 minutes
    $schedule->command('logs:process-forwarding')->everyFiveMinutes();

    // Analyze trends daily
    $schedule->command('logs:analyze-trends')->daily();

    // Clean old logs weekly
    $schedule->command('system-logs:sync --clean-old')->weekly();
}
```

## 🔗 Routes to Add

```php
// routes/web.php
Route::middleware(['auth', 'throttle:web'])->group(function () {
    // Log Management Routes
    Route::get('/logs/system', SystemLogViewer::class)->name('logs.system');
    Route::get('/logs/alerts', LogAlertManager::class)->name('logs.alerts');
    Route::get('/logs/forwarding', LogForwardingManager::class)->name('logs.forwarding');
    Route::get('/logs/analytics', LogAnalyticsDashboard::class)->name('logs.analytics');
    Route::get('/logs/parsers', LogParserManager::class)->name('logs.parsers');
    Route::get('/logs/live', LiveLogViewer::class)->name('logs.live');

    // Export routes (handled by component methods)
    // No additional routes needed
});
```

## 📊 Expected Performance Impact

### Database
- **Current**: ~500MB for 1M logs (with indexes)
- **With Alerts**: +50MB for alert tracking
- **With Forwarding**: +20MB for forwarding stats
- **With Parsers**: +10MB for parser definitions

### Processing
- **Log Collection**: ~100 logs/second per server
- **Alert Processing**: ~1000 logs/second pattern matching
- **Forwarding**: ~500 logs/second to external services
- **Analytics**: Cached queries, regenerated hourly

### Network
- **Real-time Streaming**: ~1KB/log × active connections
- **Webhooks**: Depends on external service response time
- **Slack**: Rate limited to 1 message/second per channel

## 🚀 Deployment Notes

1. **Run Migrations**:
   ```bash
   php artisan migrate
   ```

2. **Set up Queue Workers** (for async processing):
   ```bash
   php artisan queue:work --queue=logs,default
   ```

3. **Configure Notification Channels**:
   - Add Slack webhook URL to `.env`
   - Configure mail driver for email alerts
   - Set up webhook endpoints

4. **Enable Real-time** (optional):
   ```bash
   php artisan reverb:start
   ```

5. **Schedule Tasks**:
   - Ensure cron is running: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`

## 📈 Future Enhancements

- **Machine Learning**: Anomaly detection using ML models
- **Log Correlation**: Link related logs across servers
- **Incident Management**: Create incidents from log patterns
- **Cost Analysis**: Track storage and processing costs
- **Compliance Reports**: GDPR, HIPAA, SOC2 compliance tracking
- **Multi-tenant**: Isolate logs by team/organization
- **Log Replay**: Replay logs for debugging
- **Smart Sampling**: Intelligently sample high-volume logs

---

**Status**: Export functionality ✅ Complete | Alerts, Forwarding, Analytics, Real-time, Parsers 🚧 Schemas Ready
**Last Updated**: 2026-01-18
**Author**: Claude Code
