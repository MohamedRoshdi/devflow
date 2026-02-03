<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SecurityPrediction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityPredictionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SecurityPrediction $prediction
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $severityLabel = strtoupper($this->prediction->severity);
        $confidence = round($this->prediction->confidence_score * 100);

        $mail = (new MailMessage)
            ->subject("[PREDICTION] {$this->prediction->title}")
            ->line("Security Guardian has predicted a potential issue on server **{$this->prediction->server->name}**.")
            ->line("**Prediction:** {$this->prediction->title}")
            ->line("**Severity:** {$severityLabel}")
            ->line("**Confidence:** {$confidence}%")
            ->line("**Details:** {$this->prediction->description}");

        if ($this->prediction->predicted_impact_at) {
            $mail->line("**Estimated Impact:** {$this->prediction->predicted_impact_at->format('Y-m-d H:i')}");
        }

        if (! empty($this->prediction->recommended_actions)) {
            $mail->line('**Recommended Actions:**');
            foreach ($this->prediction->recommended_actions as $action) {
                $actionText = is_string($action) ? $action : ($action['description'] ?? 'Unknown action');
                $mail->line("- {$actionText}");
            }
        }

        return $mail
            ->action('View Predictions', route('security.incidents'))
            ->line('This is a predictive alert from DevFlow Pro Security Guardian.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'security_prediction',
            'prediction_id' => $this->prediction->id,
            'server_id' => $this->prediction->server_id,
            'server_name' => $this->prediction->server->name,
            'prediction_type' => $this->prediction->prediction_type,
            'severity' => $this->prediction->severity,
            'title' => $this->prediction->title,
            'confidence_score' => $this->prediction->confidence_score,
            'predicted_impact_at' => $this->prediction->predicted_impact_at,
        ];
    }
}
