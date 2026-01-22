<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use App\Domain\Telegram\Models\TelegramUser;
use App\WebApp\Services\InitDataValidator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final class BootstrapController
{
	public function __construct(
		private readonly InitDataValidator $validator,
	) {}

	public function bootstrap(Request $request): JsonResponse
	{
		$initData = $request->input('initData');

		if (empty($initData)) {
			return response()->json(['error' => 'initData required'], 400);
		}

		$validatedData = $this->validator->validate($initData);

		if (!$validatedData) {
			return response()->json(['error' => 'Invalid initData'], 400);
		}

		// Extract user data - user comes as JSON string in initData
		$userJson = $validatedData['user'] ?? null;
		if (!$userJson || !is_string($userJson)) {
			return response()->json(['error' => 'Invalid user data'], 400);
		}

		$userData = json_decode($userJson, true);
		if (!$userData || !isset($userData['id'])) {
			return response()->json(['error' => 'Invalid user JSON'], 400);
		}

		$userId = $userData['id'];
		$username = $userData['username'] ?? null;
		$firstName = $userData['first_name'] ?? null;
		$lastName = $userData['last_name'] ?? null;

		// Create/update Telegram user
		TelegramUser::query()->updateOrCreate(
			['telegram_id' => (int) $userId],
			[
				'username' => $username,
				'first_name' => $firstName,
				'last_name' => $lastName,
				'role' => \App\Domain\Telegram\Enums\TelegramRole::OPERATOR,
				'is_active' => true,
			]
		);

		// Store in session
		session(['webapp.telegram_id' => (int) $userId]);

		return response()->json([
			'success' => true,
			'telegram_id' => $userId,
		]);
	}

	public function schema(): JsonResponse
	{
		return response()->json([
			'tabs' => [
				[
					'id' => 'issue',
					'title' => 'Выдача аккаунта',
					'fields' => [
						[
							'name' => 'order_id',
							'label' => 'Номер заказа',
							'type' => 'text',
							'required' => true,
						],
						[
							'name' => 'game',
							'label' => 'Игра',
							'type' => 'select',
							'options' => [
								['value' => 'cs2', 'label' => 'CS2'],
								['value' => 'dota2', 'label' => 'Dota 2'],
								['value' => 'pubg', 'label' => 'PUBG'],
							],
							'required' => true,
						],
						[
							'name' => 'platform',
							'label' => 'Платформа',
							'type' => 'select',
							'options' => [
								['value' => 'steam', 'label' => 'Steam'],
								['value' => 'epic', 'label' => 'Epic Games'],
							],
							'required' => true,
						],
						[
							'name' => 'qty',
							'label' => 'Количество',
							'type' => 'number',
							'min' => 1,
							'max' => 2,
							'default' => 1,
							'required' => true,
						],
					],
				],
				[
					'id' => 'history',
					'title' => 'История выдач',
					'component' => 'history-table',
				],
			],
		]);
	}
}