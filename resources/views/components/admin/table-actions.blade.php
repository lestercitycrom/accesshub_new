@props([
	'viewHref' => null,
	'editHref' => null,
	'deleteHref' => null, // optional
])

<div class="inline-flex items-center justify-end gap-2">
	@if($viewHref)
		<x-admin.icon-button :href="$viewHref" icon="eye" title="Open" variant="secondary" />
	@endif

	@if($editHref)
		<x-admin.icon-button :href="$editHref" icon="pencil" title="Редактировать" variant="secondary" />
	@endif

	@if($deleteHref)
		<x-admin.icon-button :href="$deleteHref" icon="trash" title="Удалить" variant="danger" />
	@endif

	@if(trim($slot) !== '')
		{{ $slot }}
	@endif
</div>
