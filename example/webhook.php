<?php

require __DIR__ . '/../vendor/autoload.php';

use TelegramBot\TelegramBotPlus;

$bot = new TelegramBotPlus('YOUR_BOT_TOKEN');
$bot->enableDebug();

$bot->setWebhookHandler(function($update) use ($bot) {
    if (isset($update['message']['text'])) {
        $bot->sendMessage($update['message']['chat']['id'], 'I got your message!');
    }
});

$bot->handleWebhook();
