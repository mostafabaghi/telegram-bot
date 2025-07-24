# Telegram Bot Plus

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A modern, fully featured PHP wrapper for the Telegram Bot API — built for developers who want full control and modern structure with PSR-4 compatibility.

GitHub: [github.com/mostafabaghi/telegram-bot](https://github.com/mostafabaghi/telegram-bot)

---

## 🚀 Features

- PSR-4 autoloading
- Full API support (sendMessage, sendMedia, Polls, Payments...)
- Debug logging
- Webhook handler system
- Event Dispatcher system (like Laravel listeners)
- IP validation for Telegram
- File uploads via URL, local, or stream (memory)

---

## 📦 Installation

```bash
composer require mostafabaghi/telegram-bot
```

> Or manually clone:
```bash
git clone https://github.com/mostafabaghi/telegram-bot.git
cd telegram-bot
composer install
```

---

## 🔧 Usage Example

### Basic usage:

```php
use TelegramBot\TelegramBotPlus;

$bot = new TelegramBotPlus('YOUR_BOT_TOKEN');
$bot->sendMessage(123456789, 'Hello world!');
```

### Webhook handling:

```php
$bot->setWebhookHandler(function($update) use ($bot) {
    if (isset($update['message']['text'])) {
        $chatId = $update['message']['chat']['id'];
        $bot->sendMessage($chatId, "Received: " . $update['message']['text']);
    }
});

$bot->handleWebhook();
```

---

## 📚 Documentation

### ✅ Core Methods

| Method | Description |
|--------|-------------|
| `sendMessage($chatId, $text, $options = [])` | Send text message |
| `sendPhoto($chatId, $photoPath, $caption = '')` | Send image |
| `sendMedia($type, $chatId, $media, $caption = '')` | Send media (photo, video, document, ...) |
| `sendMediaGroup($chatId, $mediaArray)` | Send album (array of media) |
| `sendPoll($chatId, $question, $options)` | Create poll |
| `sendInvoice(...)` | Send payment invoice |
| `answerCallbackQuery(...)` | Answer inline button click |

### ⚙️ Webhook Management

```php
$bot->setWebhook('https://yourdomain.com/webhook.php');
$bot->deleteWebhook();
$bot->getWebhookInfo();
```

### 💡 Event System

```php
$bot->on('message.text', function($message) use ($bot) {
    $bot->sendMessage($message['chat']['id'], 'Handled via event!');
});
```

### 🔐 IP Validation

```php
if (!$bot->isFromTelegram()) {
    http_response_code(403);
    exit('Access Denied');
}
```

---

## 🧪 Testing

```bash
composer install
vendor/bin/phpunit
```

---

## 📜 License

MIT License © [mostafabaghi](https://github.com/mostafabaghi)
