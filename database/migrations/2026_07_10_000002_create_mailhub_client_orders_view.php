<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Read-only contract for MailHub's "Мои клиенты" sync (Take Order source).
 * Versioned view: MailHub depends on `*_v1`; any breaking change ships as a new
 * `*_v2`. MailHub reads this through a read-only DB user (granted separately by
 * an ops admin — see docs). MailHub never writes to AccessHub tables.
 *
 * DROP + CREATE (instead of CREATE OR REPLACE) so it runs on both MySQL (prod)
 * and SQLite (tests/local).
 */
return new class extends Migration
{
	public function up(): void
	{
		DB::statement('DROP VIEW IF EXISTS mailhub_client_orders_v1');
		DB::statement(<<<'SQL'
			CREATE VIEW mailhub_client_orders_v1 AS
			SELECT
				o.id                  AS order_id,
				o.order_number        AS order_number,
				o.customer_email      AS customer_email,
				o.platform            AS platform,
				o.issue_platform      AS issue_platform,
				o.game                AS game,
				o.status              AS status,
				o.operator_telegram_id AS operator_telegram_id,
				o.issuance_id         AS issuance_id,
				o.created_at          AS created_at,
				o.updated_at          AS updated_at,
				o.connected_at        AS connected_at,
				o.cancelled_at        AS cancelled_at
			FROM delivery_orders o
		SQL);
	}

	public function down(): void
	{
		DB::statement('DROP VIEW IF EXISTS mailhub_client_orders_v1');
	}
};
