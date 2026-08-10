<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Web (Hub) user roles. `null` role = no panel access at all — this preserves
 * the previous behaviour where only is_admin users could enter the panel.
 *
 * Capability tiers (each role adds to the one below):
 *  - operator → fulfillment plus adding new inventory without access to the
 *               account base. NOT: inspecting/editing accounts, links, exports,
 *               or system config.
 *  - manager  → operator + supply: inspect/add/edit accounts, create delivery
 *               links, edit instructions. NOT exports, system config, or users.
 *  - admin    → everything, incl. settings, server, telegram users, web users.
 */
enum UserRole: string
{
	case ADMIN = 'admin';
	case MANAGER = 'manager';
	case OPERATOR = 'operator';

	public function label(): string
	{
		return match ($this) {
			self::ADMIN => 'Администратор',
			self::MANAGER => 'Менеджер',
			self::OPERATOR => 'Оператор',
		};
	}

	/** May add/edit accounts, create links, edit instructions, export (admin+manager). */
	public function canSupply(): bool
	{
		return $this === self::ADMIN || $this === self::MANAGER;
	}

	/** May manage system config and users (admin only). */
	public function canManage(): bool
	{
		return $this === self::ADMIN;
	}

	/**
	 * @return list<array{value: string, label: string}>
	 */
	public static function options(): array
	{
		return array_map(
			static fn (self $role): array => ['value' => $role->value, 'label' => $role->label()],
			self::cases(),
		);
	}
}
