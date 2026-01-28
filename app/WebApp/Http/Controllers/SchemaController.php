<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use App\Domain\Accounts\Models\Account;
use Illuminate\Http\JsonResponse;

final class SchemaController
{
	public function __invoke(): JsonResponse
	{
		// Get unique games from accounts
		$games = Account::query()
			->distinct()
			->pluck('game')
			->filter()
			->sort()
			->values()
			->map(fn($game) => ['value' => $game, 'label' => ucfirst($game)])
			->toArray();

		// Get unique platforms from accounts (extract from JSON arrays)
		$platforms = Account::query()
			->pluck('platform')
			->filter()
			->flatMap(function ($platform) {
				if (is_array($platform)) {
					return $platform;
				}
				// Try to decode JSON if it's a string
				$decoded = json_decode($platform, true);
				if (is_array($decoded)) {
					return $decoded;
				}
				return [$platform];
			})
			->unique()
			->sort()
			->values()
			->map(fn($platform) => ['value' => $platform, 'label' => ucfirst($platform)])
			->toArray();

		return response()->json([
			'version' => 1,
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
							'options' => $games,
							'required' => true,
						],
						[
							'name' => 'platform',
							'label' => 'Платформа',
							'type' => 'select',
							'options' => $platforms,
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