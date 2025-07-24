<?php

namespace TelegramBot;

use Exception;
use CURLFile;

class TelegramBotPlus
{
    private string $apiUrl;
    private string $botToken;
    private bool $debug = false;
    private ?string $logFile = null;
    /**
     * @var callable|null
     */
    private $webhookHandler = null;
    private array $listeners = [];

    public function __construct(string $botToken)
    {
        $this->botToken = $botToken;
        $this->apiUrl = "https://api.telegram.org/bot{$botToken}/";
    }

    private function request(string $method, array $params = [], bool $isMultipart = false): array
    {
        $url = $this->apiUrl . $method;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($isMultipart) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        }

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("Curl error: " . curl_error($ch));
        }
        curl_close($ch);

        return json_decode($response, true);
    }

    public function enableDebug(string $logFile = 'telegram_bot.log'): void
    {
        $this->debug = true;
        $this->logFile = $logFile;
    }

    private function log($data): void
    {
        if (!$this->debug) return;

        $entry = "[" . date('Y-m-d H:i:s') . "] " . (is_string($data) ? $data : print_r($data, true)) . "\n";

        if ($this->logFile) {
            file_put_contents($this->logFile, $entry, FILE_APPEND);
        } else {
            echo nl2br($entry);
        }
    }

    public function on(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function dispatch(string $event, $payload = null): void
    {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                call_user_func($listener, $payload);
            }
        }
    }

    public function setWebhook(string $url): array
    {
        return $this->request("setWebhook", ["url" => $url]);
    }

    public function deleteWebhook(): array
    {
        return $this->request("deleteWebhook");
    }

    public function getWebhookInfo(): array
    {
        return $this->request("getWebhookInfo");
    }

    public function setWebhookHandler(callable $callback): void
    {
        $this->webhookHandler = $callback;
    }

    public function handleWebhook(): void
    {
        $input = file_get_contents("php://input");
        $update = json_decode($input, true);
        $this->log("Webhook Update: " . $input);

        if (is_callable($this->webhookHandler)) {
            call_user_func($this->webhookHandler, $update);
        } else {
            $this->log("No handler set.");
        }

        http_response_code(200);
        echo 'OK';
    }

    public function isFromTelegram(): bool
    {
        $telegram_ip_ranges = [
            '149.154.160.0/20',
            '91.108.4.0/22',
        ];

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        foreach ($telegram_ip_ranges as $range) {
            if ($this->ipInRange($ip, $range)) return true;
        }
        return false;
    }

    private function ipInRange(string $ip, string $range): bool
    {
        list($subnet, $mask) = explode('/', $range);
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
    }

    // 🔄 Updates
    public function getUpdates($offset = null, $limit = 100, $timeout = 0)
    {
        return $this->request("getUpdates", compact("offset", "limit", "timeout"));
    }

    // 📩 ارسال پیام
    public function sendMessage($chat_id, $text, $options = [])
    {
        return $this->request("sendMessage", array_merge([
            "chat_id" => $chat_id,
            "text" => $text,
        ], $options));
    }

    public function sendChatAction($chat_id, $action = 'typing')
    {
        return $this->request("sendChatAction", [
            "chat_id" => $chat_id,
            "action" => $action,
        ]);
    }

    public function pinMessage($chat_id, $message_id)
    {
        return $this->request("pinChatMessage", [
            "chat_id" => $chat_id,
            "message_id" => $message_id
        ]);
    }

    public function unpinMessage($chat_id, $message_id = null)
    {
        $params = ["chat_id" => $chat_id];
        if ($message_id) $params["message_id"] = $message_id;
        return $this->request("unpinChatMessage", $params);
    }

    // 📍 لوکیشن
    public function sendLocation($chat_id, $lat, $lng, $options = [])
    {
        return $this->request("sendLocation", array_merge([
            "chat_id" => $chat_id,
            "latitude" => $lat,
            "longitude" => $lng,
        ], $options));
    }

    public function sendContact($chat_id, $phone, $first_name, $options = [])
    {
        return $this->request("sendContact", array_merge([
            "chat_id" => $chat_id,
            "phone_number" => $phone,
            "first_name" => $first_name,
        ], $options));
    }

    // 📷 ارسال فایل‌ها
    public function sendMedia($type, $chat_id, $media, $caption = '', $options = [])
    {
        $method = "send" . ucfirst($type); // مثل sendPhoto یا sendDocument

        $isLocal = file_exists($media);
        $params = array_merge([
            "chat_id" => $chat_id,
            $type => $isLocal ? new CURLFile($media) : $media,
            "caption" => $caption
        ], $options);

        return $this->request($method, $params, $isLocal);
    }

    // مثال استفاده: sendMedia('photo', $chat_id, 'local.jpg');
    // یا sendMedia('video', $chat_id, 'https://example.com/vid.mp4');

    // 📦 MediaGroup (آلبوم عکس/ویدیو)
    public function sendMediaGroup($chat_id, $media_array)
    {
        return $this->request("sendMediaGroup", [
            "chat_id" => $chat_id,
            "media" => json_encode($media_array)
        ]);
    }

    // 📊 Poll
    public function sendPoll($chat_id, $question, $options, $type = 'regular')
    {
        return $this->request("sendPoll", [
            "chat_id" => $chat_id,
            "question" => $question,
            "options" => json_encode($options),
            "type" => $type
        ]);
    }

    // 🔘 Keyboard ها
    public function sendInlineKeyboard($chat_id, $text, $inline_keyboard)
    {
        return $this->sendMessage($chat_id, $text, [
            "reply_markup" => json_encode([
                "inline_keyboard" => $inline_keyboard
            ])
        ]);
    }

    public function sendReplyKeyboard($chat_id, $text, $keyboard)
    {
        return $this->sendMessage($chat_id, $text, [
            "reply_markup" => json_encode([
                "keyboard" => $keyboard,
                "resize_keyboard" => true,
                "one_time_keyboard" => false
            ])
        ]);
    }

    public function removeKeyboard($chat_id, $text = "کیبورد حذف شد")
    {
        return $this->sendMessage($chat_id, $text, [
            "reply_markup" => json_encode([
                "remove_keyboard" => true
            ])
        ]);
    }

    public function answerCallbackQuery($callback_query_id, $text = "", $show_alert = false)
    {
        return $this->request("answerCallbackQuery", [
            "callback_query_id" => $callback_query_id,
            "text" => $text,
            "show_alert" => $show_alert
        ]);
    }

    // Payments
    public function sendInvoice($chat_id, $title, $description, $payload, $provider_token, $currency, $prices, $options = [])
    {
        $params = array_merge([
            "chat_id" => $chat_id,
            "title" => $title,
            "description" => $description,
            "payload" => $payload,
            "provider_token" => $provider_token,
            "currency" => $currency,
            "prices" => json_encode($prices),
        ], $options);

        return $this->request("sendInvoice", $params);
    }
    public function answerShippingQuery($shipping_query_id, $ok, $shipping_options = [], $error_message = null)
    {
        $params = [
            "shipping_query_id" => $shipping_query_id,
            "ok" => $ok,
        ];

        if ($ok) {
            $params["shipping_options"] = json_encode($shipping_options);
        } else {
            $params["error_message"] = $error_message;
        }

        return $this->request("answerShippingQuery", $params);
    }

    public function answerPreCheckoutQuery($pre_checkout_query_id, $ok = true, $error_message = null)
    {
        $params = [
            "pre_checkout_query_id" => $pre_checkout_query_id,
            "ok" => $ok,
        ];

        if (!$ok) {
            $params["error_message"] = $error_message;
        }

        return $this->request("answerPreCheckoutQuery", $params);
    }

    // Stream
    private function createStreamFile($contents, $filename, $mime = 'application/octet-stream')
    {
        $tmp = tmpfile();
        fwrite($tmp, $contents);
        fseek($tmp, 0);
        return new CURLFile(stream_get_meta_data($tmp)['uri'], $mime, $filename);
    }

    public function sendFileFromStream($chat_id, $type, $fileContents, $filename, $caption = "", $options = [])
    {
        $curlFile = $this->createStreamFile($fileContents, $filename);
        $params = array_merge([
            "chat_id" => $chat_id,
            $type => $curlFile,
            "caption" => $caption
        ], $options);

        return $this->request("send" . ucfirst($type), $params, true);
    }
}
