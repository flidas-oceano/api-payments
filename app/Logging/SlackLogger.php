<?php
// app/Logging/SlackLogger.php

namespace App\Logging;

use Monolog\Logger;
use App\Notifications\SlackMessage;
use Illuminate\Support\Facades\Notification;

class SlackLogger
{
    protected $log;

    public function __construct()
    {
        $this->log = new Logger('slack');
    }

    public function info($message)
    {
        $this->log->info($message);
        $this->sendToSlack('info', $message);
    }

    public function error($message)
    {
        $this->log->error($message);
        $this->sendToSlack('error', $message);
    }

    // Otros métodos de registro, si es necesario

    protected function sendToSlack($level, $message)
    {
        // Enviar el mensaje a Slack
        Notification::route('slack', config('services.slack.channel'))
            ->notify(new SlackMessage($level, $message));
    }
}
