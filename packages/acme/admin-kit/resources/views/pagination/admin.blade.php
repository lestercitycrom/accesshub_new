@if ($paginator->hasPages())
	<nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-3">
		<div class="text-xs text-slate-500">
			@if($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator)
				Showing
				<span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}</span>
				to
				<span class="font-semibold text-slate-700">{{ $paginator->lastItem() }}</span>
				of
				<span class="font-semibold text-slate-700">{{ $paginator->total() }}</span>
			@endif
		</div>

		<div class="flex items-center gap-1">
			{{-- Previous --}}
			@if ($paginator->onFirstPage())
				<span class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-sm font-semibold border border-slate-200 bg-white text-slate-400 cursor-not-allowed">
					Prev
				</span>
			@else
				<a href="{{ $paginator->previousPageUrl() }}" rel="prev"
					class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-sm font-semibold border border-slate-200 bg-white text-slate-900 hover:bg-slate-50">
					Prev
				</a>
			@endif

			{{-- Elements --}}
			@foreach ($elements as $element)
				@if (is_string($element))
					<span class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-sm font-semibold border border-slate-200 bg-white text-slate-400">
						{{ $element }}
					</span>
				@endif

				@if (is_array($element))
					@foreach ($element as $page => $url)
						@if ($page == $paginator->currentPage())
							<span aria-current="page"
								class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-sm font-semibold bg-slate-900 text-white">
								{{ $page }}
							</span>
						@else
							<a href="{{ $url }}"
								class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-sm font-semibold border border-slate-200 bg-white text-slate-900 hover:bg-slate-50"
								aria-label="Go to page {{ $page }}">
								{{ $page }}
							</a>
						@endif
					@endforeach
				@endif
			@endforeach

			{{-- Next --}}
			@if ($paginator->hasMorePages())
				<a href="{{ $paginator->nextPageUrl() }}" rel="next"
					class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-sm font-semibold border border-slate-200 bg-white text-slate-900 hover:bg-slate-50">
					Next
				</a>
			@else
				<span class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-sm font-semibold border border-slate-200 bg-white text-slate-400 cursor-not-allowed">
					Next
				</span>
			@endif
		</div>
	</nav>
@endif