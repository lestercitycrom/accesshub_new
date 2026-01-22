@props([
	'status',
])

@php
	$st = strtoupper((string) $status);

	$variant = match ($st) {
		'ACTIVE' => 'green',
		'RECOVERY' => 'amber',
		'STOLEN' => 'red',
		'DEAD' => 'red',
		'TEMP_HOLD' => 'blue',
		'COOLDOWN' => 'violet',
		default => 'gray',
	};
@endphp

<x-admin.badge :variant="$variant">{{ $st }}</x-admin.badge>