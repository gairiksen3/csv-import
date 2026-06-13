<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ImportNotification extends Notification
{
    use Queueable;

    /**
     * @param string      $level   info | warning | error
     * @param string      $title   Short headline shown in the bell dropdown
     * @param string      $message Detail line
     * @param string|null $url     Where clicking the notification should go
     */
    public function __construct(
        public string $level,
        public string $title,
        public string $message,
        public ?string $url = null,
    ) {
    }

    /**
     * Store notifications in the database (shown via the topbar bell).
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'level' => $this->level,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
