<?php

declare(strict_types=1);

namespace App\Admin\Livewire\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Manage Hub web users' roles. Admin-only (route + mount are gated hub-manage).
 * `role = null` means "no panel access" — the way to revoke a user without
 * deleting them.
 */
final class UsersIndex extends Component
{
	use WithPagination;

	public string $q = '';
	public string $sortBy = 'id';
	public string $sortDirection = 'desc';

	public function mount(): void
	{
		Gate::authorize('hub-manage');
	}

	public function updatingQ(): void
	{
		$this->resetPage();
	}

	public function sort(string $field): void
	{
		if (!in_array($field, ['id', 'name', 'email', 'role'], true)) {
			return;
		}

		if ($this->sortBy === $field) {
			$this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
			return;
		}

		$this->sortBy = $field;
		$this->sortDirection = 'asc';
	}

	/**
	 * Assign a role, or revoke access with an empty value. Never leaves the
	 * system without an admin (which would lock everyone out).
	 */
	public function setRole(int $userId, string $role): void
	{
		Gate::authorize('hub-manage');

		$target = $role === '' ? null : UserRole::tryFrom($role);
		if ($role !== '' && $target === null) {
			return;
		}

		$user = User::query()->findOrFail($userId);

		if ($user->role === UserRole::ADMIN && $target !== UserRole::ADMIN) {
			$otherAdmins = User::query()
				->where('role', UserRole::ADMIN)
				->whereKeyNot($user->getKey())
				->count();

			if ($otherAdmins === 0) {
				session()->flash('error', 'Нельзя убрать последнего администратора.');
				return;
			}
		}

		$user->role = $target;
		$user->save();

		session()->flash('message', 'Роль обновлена: ' . $user->email . ' → ' . ($target?->label() ?? 'Без доступа'));
	}

	/**
	 * @return LengthAwarePaginator<User>
	 */
	public function getRowsProperty(): LengthAwarePaginator
	{
		return User::query()
			->when($this->q !== '', function ($query): void {
				$like = '%' . $this->q . '%';
				$query->where(function ($q) use ($like): void {
					$q->where('name', 'like', $like)->orWhere('email', 'like', $like);
				});
			})
			->orderBy($this->sortBy, $this->sortDirection)
			->paginate(20);
	}

	public function render()
	{
		return view('admin.users.index', [
			'rows' => $this->rows,
			'roleOptions' => UserRole::options(),
		])->layout('layouts.admin');
	}
}
