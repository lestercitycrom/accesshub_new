@props([
	'name',
	'class' => 'h-4 w-4',
])

@php
	$n = (string) $name;
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center '.$class]) }} aria-hidden="true">
	@if($n === 'users')
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
			<circle cx="9" cy="7" r="4"/>
			<path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
			<path d="M16 3.13a4 4 0 0 1 0 7.75"/>
		</svg>
	@elseif($n === 'database')
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<ellipse cx="12" cy="5" rx="9" ry="3"/>
			<path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/>
			<path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/>
		</svg>
	@elseif($n === 'search')
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<circle cx="11" cy="11" r="8"/>
			<path d="M21 21l-4.3-4.3"/>
		</svg>
	@elseif($n === 'upload')
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
			<path d="M17 8l-5-5-5 5"/>
			<path d="M12 3v12"/>
		</svg>
	@elseif($n === 'list')
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M8 6h13"/>
			<path d="M8 12h13"/>
			<path d="M8 18h13"/>
			<path d="M3 6h.01"/>
			<path d="M3 12h.01"/>
			<path d="M3 18h.01"/>
		</svg>
	@elseif($n === 'settings')
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7z"/>
			<path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 0 1-1.4 3.4h-.2a1.7 1.7 0 0 0-1.6 1.1 2 2 0 0 1-3.7 0 1.7 1.7 0 0 0-1.6-1.1H10a2 2 0 0 1-1.4-3.4l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H7a2 2 0 0 1 0-4h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1A2 2 0 0 1 10 3.6h.2a1.7 1.7 0 0 0 1.6-1.1 2 2 0 0 1 3.7 0 1.7 1.7 0 0 0 1.6 1.1h.2A2 2 0 0 1 21.4 6l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1H23a2 2 0 0 1 0 4h-.2a1.7 1.7 0 0 0-1.5 1z"/>
		</svg>
	@else
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M12 20h9"/>
			<path d="M12 4h9"/>
			<path d="M4 9h16"/>
			<path d="M4 15h16"/>
		</svg>
	@endif
</span>