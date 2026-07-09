<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Web (Hub) user roles. `null` role = no panel access at all — this preserves
 * the previous behaviour where only is_admin users could enter the panel.
 *
 * Capability tiers: view ⊂ operate ⊂ manage.
 *  - viewer   → read-only (view)
 *  - operator → read + operational mutations (operate)
 *  - admin    → everything incl. system config & user management (manage)
 */
enum UserRole: string
{
	case ADMIN = 'admin';
	case OPERATOR = 'operator';
	case VIEWER = 'viewer';

	public function label(): string
	{
		return match ($this) {
			self::ADMIN => 'Администратор',
			self::OPERATOR => 'Оператор',
			self::VIEWER => 'Наблюдатель',
		};
	}

	/** Can perform operational mutations (assign, generate, edit accounts, …). */
	public function canOperate(): bool
	{
		return $this === self::ADMIN || $this === self::OPERATOR;
	}

	/** Can manage system config and users (admin-only areas). */
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
