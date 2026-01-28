<?php

declare(strict_types=1);

namespace App\Admin\Livewire\TelegramUsers;

use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

final class TelegramUsersIndex extends Component
{
	use WithPagination;

	public string $q = '';
	public array $selected = [];
	public string $roleFilter = '';
	public string $sortBy = 'id';
	public string $sortDirection = 'desc';

	public function mount(): void
	{
		Gate::authorize('admin');
	}

	public function updatingQ(): void
	{
		$this->resetPage();
	}

	public function toggleSelectAll(): void
	{
		$rows = $this->rows;
		$ids = $rows->pluck('id')->map(fn ($id): int => (int) $id)->all();

		if ($ids === []) {
			$this->selected = [];
			return;
		}

		$this->selected = count($this->selected) === count($ids) ? [] : $ids;
	}

	public function updatingRoleFilter(): void
	{
		$this->resetPage();
	}

	public function setRoleFilter(string $role): void
	{
		if (!in_array($role, [TelegramRole::OPERATOR->value, TelegramRole::ADMIN->value], true)) {
			return;
		}

		$this->roleFilter = $this->roleFilter === $role ? '' : $role;
	}

	public function toggleActive(bool $active): void
	{
		Gate::authorize('admin');

		TelegramUser::query()
			->whereIn('id', $this->selected)
			->update(['is_active' => $active]);

		$this->selected = [];
	}

	public function setRole(string $role): void
	{
		Gate::authorize('admin');

		if (!in_array($role, [TelegramRole::OPERATOR->value, TelegramRole::ADMIN->value], true)) {
			return;
		}

		TelegramUser::query()
			->whereIn('id', $this->selected)
			->update(['role' => $role]);

		$this->selected = [];
	}

	/**
	 * @return LengthAwarePaginator<TelegramUser>
	 */
	public function getRowsProperty(): LengthAwarePaginator
	{
		return TelegramUser::query()
			->when($this->q !== '', function ($query): void {
				$q = '%' . $this->q . '%';

				$query->where('telegram_id', 'like', $q)
					->orWhere('username', 'like', $q)
					->orWhere('first_name', 'like', $q)
					->orWhere('last_name', 'like', $q);
			})
			->when($this->roleFilter !== '', function ($query): void {
				$query->where('role', $this->roleFilter);
			})
			->orderBy($this->sortBy, $this->sortDirection)
			->paginate(20);
	}

	public function render()
	{
		return view('admin.telegram-users.index', [
			'rows' => $this->rows,
		])->layout('layouts.admin');
	}
}
