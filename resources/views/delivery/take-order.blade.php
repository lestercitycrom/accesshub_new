<x-dynamic-component component="delivery.layout" title="Take Order">
	<div class="shell">
		<h1>Get your game</h1>
		<p class="muted">Enter your order details. An operator will check the order and start delivery.</p>

		<form method="post" action="{{ route('delivery.take-order.store') }}">
			@csrf

			<div class="form-row">
				<label for="order_number">Order Number</label>
				<input id="order_number" name="order_number" value="{{ old('order_number') }}" required>
				@error('order_number') <div class="error">{{ $message }}</div> @enderror
			</div>

			<div class="form-row">
				<label for="email">Email</label>
				<input id="email" name="email" type="email" value="{{ old('email') }}" required>
				@error('email') <div class="error">{{ $message }}</div> @enderror
			</div>

			<div class="form-row">
				<label for="platform">Platform</label>
				<select id="platform" name="platform" required>
					<option value="">Choose platform</option>
					@foreach($platforms as $platform)
						<option value="{{ $platform }}" @selected(old('platform') === $platform)>{{ $platform }}</option>
					@endforeach
				</select>
				@error('platform') <div class="error">{{ $message }}</div> @enderror
			</div>

			<button type="submit">Get Game</button>
		</form>
	</div>
</x-dynamic-component>

