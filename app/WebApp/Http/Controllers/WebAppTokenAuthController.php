<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use App\WebApp\Services\WebAppTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class WebAppTokenAuthController
{
    public function __construct(
        private readonly WebAppTokenService $tokenService,
    ) {}

    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $telegramId = $this->tokenService->consume($token);

        if ($telegramId === null) {
            abort(403, 'Ссылка недействительна или истекла. Запросите новую командой /link в боте.');
        }

        $request->session()->regenerate();
        $request->session()->put('webapp.telegram_id', $telegramId);

        return redirect()->route('webapp');
    }
}
