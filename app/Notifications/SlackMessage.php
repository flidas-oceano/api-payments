<?php
// app/Notifications/SlackMessage.php

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage as LaravelSlackMessage;

class SlackMessage extends Notification
{
    protected $level;
    protected $message;

    public function __construct($level, $message)
    {
        $this->level = $level;
        $this->message = $message;
    }

    public function toSlack($notifiable)
    {
        return (new LaravelSlackMessage)
            ->content($this->level . ': ' . $this->message);
    }
}
