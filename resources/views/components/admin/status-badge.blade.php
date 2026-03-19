@props([
	'status',
])

@php
	$st = strtoupper((string) $status);

	$variant = match ($st) {
		'ACTIVE'    => 'green',
		'RECOVERY'  => 'amber',
		'STOLEN'    => 'red',
		'DEAD'      => 'red',
		'TEMP_HOLD' => 'blue',
		'COOLDOWN'  => 'violet',
		default     => 'gray',
	};

	$label = match ($st) {
		'ACTIVE'    => 'Активен',
		'RECOVERY'  => 'Восстановление',
		'STOLEN'    => 'Украден',
		'DEAD'      => 'Мёртвый',
		'TEMP_HOLD' => 'На паузе',
		'COOLDOWN'  => 'Кулдаун',
		default     => $st,
	};
@endphp

<x-admin.badge :variant="$variant">{{ $label }}</x-admin.badge>
