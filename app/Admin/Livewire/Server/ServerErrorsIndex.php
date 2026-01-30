<?php

declare(strict_types=1);

namespace App\Admin\Livewire\Server;

use App\Models\ServerError;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

final class ServerErrorsIndex extends Component
{
	use WithPagination;

	public string $contextFilter = '';
	public string $q = '';
	public string $sortBy = 'created_at';
	public string $sortDirection = 'desc';

	public ?int $detailId = null;

	public function mount(): void
	{
		Gate::authorize('admin');
	}

	public function getRowsProperty(): LengthAwarePaginator
	{
		return ServerError::query()
			->when($this->contextFilter !== '', fn ($q) => $q->where('context', $this->contextFilter))
			->when($this->q !== '', function ($query): void {
				$q = '%' . $this->q . '%';
				$query->where(function ($q2) use ($q): void {
					$q2->where('exception_message', 'like', $q)
						->orWhere('exception_class', 'like', $q)
						->orWhereRaw('CAST(id AS CHAR) LIKE ?', [$q])
						->orWhereRaw('CAST(telegram_id AS CHAR) LIKE ?', [$q]);
				});
			})
			->orderBy($this->sortBy, $this->sortDirection)
			->paginate(25);
	}

	public function sort(string $field): void
	{
		if ($this->sortBy === $field) {
			$this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
		} else {
			$this->sortBy = $field;
			$this->sortDirection = 'desc';
		}
		$this->resetPage();
	}

	public function clearFilters(): void
	{
		$this->contextFilter = '';
		$this->q = '';
		$this->sortBy = 'created_at';
		$this->sortDirection = 'desc';
		$this->detailId = null;
		$this->resetPage();
	}

	public function showDetail(int $id): void
	{
		$this->detailId = $id;
	}

	public function closeDetail(): void
	{
		$this->detailId = null;
	}

	public function getErrorDetailProperty(): ?ServerError
	{
		return $this->detailId !== null ? ServerError::query()->find($this->detailId) : null;
	}

	public function render()
	{
		return view('admin.server.errors-index', [
			'rows' => $this->rows,
			'errorDetail' => $this->errorDetail,
		])->layout('layouts.admin');
	}
}
