@props(['action' => null, 'placeholder' => 'Buscar…', 'clearUrl' => null, 'hasActiveFilters' => false, 'filtersLabel' => 'Filtrar'])

{{-- Toolbar padrão de tabelas: busca + filtros internos + aplicar + limpar. --}}
<form method="GET" action="{{ $action }}" class="table-toolbar" data-toolbar>
    <div class="table-search">
        <span class="material-symbols-outlined">search</span>
        <input name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" autocomplete="off" data-search-input>
        @if(request('q'))
        <button type="button" class="table-search-clear" data-search-clear aria-label="Limpar busca">
            <span class="material-symbols-outlined text-[15px]">close</span>
        </button>
        @endif
    </div>
    {{ $slot }}
    <button type="submit" class="table-apply">
        <span class="material-symbols-outlined">filter_alt</span>{{ $filtersLabel }}
    </button>
    @if($hasActiveFilters && $clearUrl)
        <a href="{{ $clearUrl }}" class="table-clear">
            <span class="material-symbols-outlined text-[15px]">close</span>
            Limpar
        </a>
    @endif
</form>