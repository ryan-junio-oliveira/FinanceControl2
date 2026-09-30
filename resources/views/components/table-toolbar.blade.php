@props(['action' => null, 'placeholder' => 'Buscar…', 'clearUrl' => null, 'hasActiveFilters' => false])

{{-- Toolbar padrão de tabelas: busca + filtros internos + limpar. --}}
<form method="GET" action="{{ $action }}" class="table-toolbar">
    <div class="table-search">
        <span class="material-symbols-outlined">search</span>
        <input name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" autocomplete="off">
    </div>
    {{ $slot }}
    @if($hasActiveFilters && $clearUrl)
        <a href="{{ $clearUrl }}" class="table-clear">
            <span class="material-symbols-outlined text-[15px]">close</span>
            Limpar
        </a>
    @endif
</form>
