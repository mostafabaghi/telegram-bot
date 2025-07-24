<?php

use PHPUnit\Framework\TestCase;
use TelegramBot\TelegramBotPlus;

class TelegramBotTest extends TestCase
{
    public function testClassInitialization() {
        $bot = new TelegramBotPlus("DUMMY_TOKEN");
        $this->assertInstanceOf(TelegramBotPlus::class, $bot);
    }

    public function testWebhookSetter() {
        $bot = new TelegramBotPlus("DUMMY_TOKEN");
        $bot->enableDebug(); // Enable logs
        $this->expectNotToPerformAssertions();
        $bot->setWebhookHandler(function($update) {
            // ...
        });
    }
}

// vendor/bin/phpunit tests
