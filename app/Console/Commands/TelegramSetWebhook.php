<?php

namespace App\Console\Commands;

use App\Telegram\Services\TelegramClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:webhook {url : Публичный URL (например ngrok https://xxx.ngrok-free.app)}';

    protected $description = 'Прописывает APP_URL в .env, чистит кеш и ставит Telegram webhook';

    public function handle(): int
    {
        $url = rtrim($this->argument('url'), '/');

        // 1. Обновить APP_URL в .env
        $this->updateEnv('APP_URL', $url);
        $this->info("APP_URL установлен: {$url}");

        // 2. Читаем токен ДО очистки кеша
        $token = env('TELEGRAM_BOT_TOKEN');

        // 3. Очистить кеш
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');

        if (! $token) {
            $this->error('TELEGRAM_BOT_TOKEN не задан в .env');
            return self::FAILURE;
        }

        $webhookUrl = "{$url}/api/telegram/webhook";

        $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url' => $webhookUrl,
        ]);

        $result = $response->json();

        if ($result['ok'] ?? false) {
            $this->info("Webhook установлен: {$webhookUrl}");
        } else {
            $this->error('Ошибка Telegram API: ' . ($result['description'] ?? 'неизвестная ошибка'));
            return self::FAILURE;
        }

        // 4. Обновить кнопку меню бота
        $webappUrl = "{$url}/webapp";
        $client = app(TelegramClient::class);
        if ($client->setChatMenuButton('Открыть', $webappUrl)) {
            $this->info("Кнопка меню обновлена: {$webappUrl}");
        } else {
            $this->warn('Не удалось обновить кнопку меню (не критично).');
        }

        return self::SUCCESS;
    }

    private function updateEnv(string $key, string $value): void
    {
        $path = base_path('.env');
        $contents = file_get_contents($path);

        if (preg_match("/^{$key}=.*/m", $contents)) {
            $contents = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $contents);
        } else {
            $contents .= "\n{$key}={$value}";
        }

        file_put_contents($path, $contents);
    }
}
