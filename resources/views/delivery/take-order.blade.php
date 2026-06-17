<x-dynamic-component component="delivery.layout" title="Take Order">
	@php
		$gamepadSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="5"/><line x1="6.5" y1="11" x2="9.5" y2="11"/><line x1="8" y1="9.5" x2="8" y2="12.5"/><circle cx="15.5" cy="13" r=".7" fill="currentColor"/><circle cx="18" cy="11" r=".7" fill="currentColor"/></svg>';
		$monitorSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="2"/><line x1="8" y1="20" x2="16" y2="20"/><line x1="12" y1="16" x2="12" y2="20"/></svg>';
		$platformIcons = [
			'PlayStation' => $gamepadSvg,
			'Xbox' => $gamepadSvg,
			'Nintendo' => $gamepadSvg,
			'Steam' => $monitorSvg,
			'Epic Games' => $monitorSvg,
		];
	@endphp

	<div class="card">
		<div class="card-grad-header">
			<h1>Get your game</h1>
			<p>Enter your order details. An operator will check the order and start delivery.</p>
		</div>

		<div class="card-body">
			@if(session('error'))
				<div class="alert alert--danger" style="margin-bottom:16px;">
					<span class="ic"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>
					<span>{{ session('error') }}</span>
				</div>
			@endif

			<div id="offlineNotice" class="alert alert--warn hidden" style="margin-bottom:16px;">
				<span class="ic"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg></span>
				<span id="offlineNoticeText"></span>
			</div>

			<form method="post" action="{{ route('delivery.take-order.store') }}" id="takeOrderForm" novalidate>
				@csrf

				<div class="form-row">
					<label for="order_number">Order Number</label>
					<input id="order_number" name="order_number" value="{{ old('order_number') }}"
						inputmode="text" autocomplete="off" required placeholder="e.g. 3233159">
					@error('order_number') <div class="error">{{ $message }}</div> @enderror
				</div>

				<div class="form-row">
					<label for="email">Email</label>
					<input id="email" name="email" type="email" value="{{ old('email') }}"
						autocomplete="email" required placeholder="you@example.com">
					@error('email') <div class="error">{{ $message }}</div> @enderror
				</div>

				<div class="form-row">
					<label>Platform</label>
					<div class="platforms" role="radiogroup" aria-label="Platform">
						@foreach($platforms as $platform)
							<div class="platform-opt">
								<input type="radio" id="pf_{{ $loop->index }}" name="platform"
									value="{{ $platform }}" @checked(old('platform') === $platform) required>
								<label for="pf_{{ $loop->index }}">
									<span class="pf-ic">{!! $platformIcons[$platform] ?? $gamepadSvg !!}</span>
									<span>{{ $platform }}</span>
								</label>
							</div>
						@endforeach
					</div>
					@error('platform') <div class="error">{{ $message }}</div> @enderror
				</div>

				<button type="submit" class="btn" id="submitBtn">
					<span class="btn-label">Get Game</span>
				</button>
			</form>
		</div>
	</div>

	<script>
		(() => {
			const form = document.getElementById('takeOrderForm');
			const btn = document.getElementById('submitBtn');
			const label = btn.querySelector('.btn-label');
			const notice = document.getElementById('offlineNotice');
			const noticeText = document.getElementById('offlineNoticeText');

			const cfg = window.__deliveryHours || {};
			const pad = (n) => String(n).padStart(2, '0');
			const rangeText = `${pad(cfg.start ?? 9)}:00 to ${(cfg.end ?? 23) >= 24 ? '00' : pad(cfg.end ?? 23)}:00 ${cfg.label || 'EET'}`;

			let closed = false; // night block currently active

			function applyHours() {
				const enforce = typeof window.deliveryHoursEnforced === 'function' ? window.deliveryHoursEnforced() : !!cfg.enforce;
				const open = typeof window.deliveryHoursIsOpen === 'function' ? window.deliveryHoursIsOpen() : true;
				closed = enforce && !open;
				if (notice) notice.classList.toggle('hidden', !closed);
				if (closed && noticeText) noticeText.textContent = `Orders are accepted during support hours (${rangeText}). Please come back later.`;
				if (!btn.dataset.loading) btn.disabled = closed;
			}
			document.addEventListener('delivery-hours-tick', applyHours);
			applyHours();

			form.addEventListener('submit', (e) => {
				if (closed) { e.preventDefault(); applyHours(); return; }
				// Let native validation block empty submits before showing the loading state.
				if (!form.checkValidity()) return;
				btn.dataset.loading = '1';
				btn.disabled = true;
				label.textContent = 'Checking order…';
				btn.insertAdjacentHTML('afterbegin', '<span class="spinner"></span>');
			});
		})();
	</script>
</x-dynamic-component>
