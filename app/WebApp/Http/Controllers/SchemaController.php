<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class SchemaController
{
	public function __invoke(): JsonResponse
	{
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