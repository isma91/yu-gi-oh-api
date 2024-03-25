<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class to send message from an already created bot from a Custom Chat room
 */
class Telegram
{
    private ?int $chatId = NULL;
    private string $urlBase = "https://api.telegram.org/bot{BOT_TOKEN}";
    public function __construct(ParameterBagInterface $param)
    {
        $this->urlBase = str_replace('{BOT_TOKEN}',  $param->get('TELEGRAM_BOT_TOKEN'), $this->urlBase);
        $chatId = $param->get('TELEGRAM_CHAT_ID');
        if (empty($chatId) === FALSE) {
            $chatId = (int)$chatId;
        } else {
            $chatId = "";
        }
        $this->chatId = $chatId;
    }

    public function sendMessage(string $message): void
    {
        $url = $this->urlBase . "/sendMessage";
        $data = [
            "chat_id" => $this->chatId,
            "text" => $message
        ];
        $this->_send($url, $data);
    }

    private function _send(string $url, array $data): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        curl_close($ch);
    }
}