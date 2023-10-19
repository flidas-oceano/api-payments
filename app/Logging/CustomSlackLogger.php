<?php
namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\SlackHandler;

class CustomSlackLogger extends SlackHandler
{
    public function __construct($token, $channel, $username, $useAttachment = true, $iconEmoji = null, $level = Logger::ERROR, $bubble = true)
    {
        parent::__construct($token, $channel, $username, $useAttachment, $iconEmoji, $level, $bubble);
    }

    protected function createAttachment($record)
    {
        // Formatea el mensaje antes de enviarlo a Slack
        $formattedMessage = $this->formatMessage($record);

        // Crea un objeto de adjunto
        $attachment = parent::createAttachment($record);
        $attachment['text'] = $formattedMessage;

        return $attachment;
    }

    protected function formatMessage($record)
    {
        // Formatea el mensaje aquí como desees
        $message = $record['message'];

        return ":rotating_light: **[Error]** :rotating_light:\n\n" .
            "- *Tipo de Error*: DatabaseException\n" .
            "- *Fecha*: " . now() . "\n" .
            "- *Detalles*: $message\n" .
            ":warning: ¡Actúa rápidamente! :warning:";
    }
}
