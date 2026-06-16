<x-dynamic-component component="delivery.layout" title="Order {{ $payload['order_number'] }}">
	<div class="shell" id="orderApp" data-status-url="{{ route('delivery.order.status', ['token' => $order->token]) }}" data-code-url="{{ route('delivery.order.connection-code.store', ['token' => $order->token]) }}">
		<div class="header">
			<div>
				<h1>Order #{{ $payload['order_number'] }}</h1>
				<div class="muted">{{ $payload['platform'] }}</div>
			</div>
			<div class="status" id="statusLabel">{{ str_replace('_', ' ', $payload['status']) }}</div>
		</div>

		<div class="grid">
			<section class="card">
				<h2>Account</h2>
				<div id="accountBlock">
					@if($payload['account'])
						<p class="muted">Login</p>
						<p class="code">{{ $payload['account']['login'] }}</p>
						<p class="muted">Password</p>
						<p class="code">{{ $payload['account']['password'] }}</p>
					@else
						<p class="muted">Waiting for operator...</p>
					@endif
				</div>
			</section>

			<section class="card">
				<h2>Instructions</h2>
				<div id="instructionBlock">
					@if($payload['instruction'])
						<h3>{{ $payload['instruction']['title'] }}</h3>
						<p class="muted">{!! nl2br(e($payload['instruction']['body'] ?? '')) !!}</p>
					@else
						<p class="muted">Instructions will appear here when delivery starts.</p>
					@endif
				</div>
			</section>
		</div>

		<section class="card" style="margin-top: 16px;" id="connectionBlock">
			<h2>QR / Connection Code</h2>
			<p class="muted" id="connectionText">If your platform requires connection, enter the 6-8 character code shown on your console.</p>
			<form id="connectionForm">
				@csrf
				<div class="form-row">
					<label for="connection_code">Connection Code</label>
					<input id="connection_code" name="connection_code" maxlength="8" autocomplete="off">
				</div>
				<button type="submit">Send Code</button>
			</form>
			<p class="muted" id="attemptsText"></p>
			<p class="error hidden" id="connectionError"></p>
		</section>
	</div>

	<script>
		(() => {
			const root = document.getElementById('orderApp');
			const statusUrl = root.dataset.statusUrl;
			const codeUrl = root.dataset.codeUrl;
			const statusLabel = document.getElementById('statusLabel');
			const accountBlock = document.getElementById('accountBlock');
			const instructionBlock = document.getElementById('instructionBlock');
			const connectionBlock = document.getElementById('connectionBlock');
			const attemptsText = document.getElementById('attemptsText');
			const connectionForm = document.getElementById('connectionForm');
			const connectionError = document.getElementById('connectionError');
			const csrf = document.querySelector('input[name="_token"]').value;
			let pollingMs = 8000;

			function statusText(value) {
				return String(value || '').replaceAll('_', ' ');
			}

			function render(data) {
				statusLabel.textContent = statusText(data.status);
				pollingMs = Math.max(3000, (data.polling_interval_seconds || 8) * 1000);

				if (data.account) {
					accountBlock.innerHTML = `
						<p class="muted">Login</p>
						<p class="code">${escapeHtml(data.account.login || '')}</p>
						<p class="muted">Password</p>
						<p class="code">${escapeHtml(data.account.password || '')}</p>
					`;
				} else {
					accountBlock.innerHTML = '<p class="muted">Waiting for operator...</p>';
				}

				if (data.instruction) {
					instructionBlock.innerHTML = `
						<h3>${escapeHtml(data.instruction.title || '')}</h3>
						<p class="muted">${escapeHtml(data.instruction.body || '').replaceAll('\n', '<br>')}</p>
					`;
				}

				const connection = data.connection || {};
				connectionBlock.classList.toggle('hidden', !connection.required);
				attemptsText.textContent = connection.required
					? `Attempts: ${connection.attempts_used || 0} / ${connection.attempts_limit || 0}`
					: '';
			}

			function escapeHtml(value) {
				return String(value)
					.replaceAll('&', '&amp;')
					.replaceAll('<', '&lt;')
					.replaceAll('>', '&gt;')
					.replaceAll('"', '&quot;')
					.replaceAll("'", '&#039;');
			}

			async function refresh() {
				const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
				if (response.ok) render(await response.json());
				window.setTimeout(refresh, pollingMs);
			}

			connectionForm.addEventListener('submit', async (event) => {
				event.preventDefault();
				connectionError.classList.add('hidden');
				const code = document.getElementById('connection_code').value.trim();
				const response = await fetch(codeUrl, {
					method: 'POST',
					headers: {
						'Accept': 'application/json',
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': csrf
					},
					body: JSON.stringify({ connection_code: code })
				});
				const data = await response.json();
				if (!response.ok) {
					connectionError.textContent = data.message || 'Unable to send code.';
					connectionError.classList.remove('hidden');
				}
				if (data.order) render(data.order);
			});

			refresh();
		})();
	</script>
</x-dynamic-component>

