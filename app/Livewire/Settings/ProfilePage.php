<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class ProfilePage extends Component
{
	use ProfileValidationRules;
	use PasswordValidationRules;

	public string $name = '';
	public string $email = '';
	public string $current_password = '';
	public string $password = '';
	public string $password_confirmation = '';

	public function mount(): void
	{
		$user = Auth::user();
		$this->name = (string) $user?->name;
		$this->email = (string) $user?->email;
	}

	public function updateProfileInformation(): void
	{
		$user = Auth::user();

		$validated = $this->validate($this->profileRules($user->id));

		$user->fill($validated);

		if ($user->isDirty('email')) {
			$user->email_verified_at = null;
		}

		$user->save();

		$this->dispatch('profile-updated', name: $user->name);
	}

	public function updatePassword(): void
	{
		try {
			$validated = $this->validate([
				'current_password' => $this->currentPasswordRules(),
				'password' => $this->passwordRules(),
			]);
		} catch (ValidationException $e) {
			$this->reset('current_password', 'password', 'password_confirmation');
			throw $e;
		}

		Auth::user()->update([
			'password' => $validated['password'],
		]);

		$this->reset('current_password', 'password', 'password_confirmation');

		$this->dispatch('password-updated');
	}

	public function resendVerificationNotification(): void
	{
		$user = Auth::user();

		if ($user->hasVerifiedEmail()) {
			$this->redirectIntended(default: route('admin.accounts.index', absolute: false));
			return;
		}

		$user->sendEmailVerificationNotification();

		Session::flash('status', 'verification-link-sent');
	}

	public function getHasUnverifiedEmailProperty(): bool
	{
		return Auth::user() instanceof MustVerifyEmail
			&& ! Auth::user()->hasVerifiedEmail();
	}

	public function render()
	{
		return view('livewire.settings.profile-page')
			->layout('layouts.admin');
	}
}
