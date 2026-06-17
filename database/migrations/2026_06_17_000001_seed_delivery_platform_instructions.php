<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration
{
	public function up(): void
	{
		$now = now();

		foreach ($this->instructions() as $instruction) {
			DB::table('delivery_platform_instructions')->updateOrInsert(
				['platform' => $instruction['platform']],
				[
					'title' => $instruction['title'],
					'body' => $instruction['body'],
					'is_active' => true,
					'created_at' => $now,
					'updated_at' => $now,
				],
			);
		}
	}

	public function down(): void
	{
		DB::table('delivery_platform_instructions')
			->whereIn('platform', array_column($this->instructions(), 'platform'))
			->delete();
	}

	private function instructions(): array
	{
		return [
			[
				'platform' => 'PlayStation',
				'title' => 'PlayStation console connection',
				'body' => 'Use the login shown on this page. When the console asks for confirmation, open the QR or device-code screen, enter the 6-8 character code here, and wait while the operator completes the connection.',
			],
			[
				'platform' => 'Xbox',
				'title' => 'Xbox console connection',
				'body' => 'Use the login shown on this page. When Xbox shows the QR or device code, enter the 6-8 character code here. The operator will process it and finish the authorization.',
			],
			[
				'platform' => 'Nintendo',
				'title' => 'Nintendo console connection',
				'body' => 'Use the login shown on this page. When Nintendo asks for confirmation, enter the QR or device code here and wait for the operator to complete the connection.',
			],
			[
				'platform' => 'Steam',
				'title' => 'Steam account delivery',
				'body' => 'Use the login and password shown on this page. QR or device-code connection is not required for this platform.',
			],
			[
				'platform' => 'Epic Games',
				'title' => 'Epic Games account delivery',
				'body' => 'Use the login and password shown on this page. QR or device-code connection is not required for this platform.',
			],
		];
	}
};
