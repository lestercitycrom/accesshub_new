<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class Dashboard extends Component
{
	public function mount(): void
	{
		Gate::authorize('admin');
	}

	public function render()
	{
		return view('admin.dashboard')
			->layout('layouts.admin');
	}
}