@extends('layouts.app')
@section('title', 'Logs de Ação')
@section('breadcrumb', 'Sistema / Logs de Ação')
@section('nav-active', 'admin.logs')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Logs de Ação</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Trilha de auditoria de todas as ações na conta.</p>
    </div>
</div>

<x-section-card title="Registros de Auditoria" :subtitle="$logs->total().' registro(s)'">
    <x-slot:action>
        <x-table-toolbar
            :action="route('admin.logs')"
            placeholder="Buscar ação…"
            :clearUrl="route('admin.logs')"
            :hasActiveFilters="request()->filled('q') || request()->filled('acao') || request()->filled('membro') || request()->filled('de') || request()->filled('ate')">
            <select name="acao" onchange="this.form.submit()" class="table-filter" aria-label="Filtrar por ação">
                <option value="">Todas as ações</option>
                @foreach($acoes as $v => $l)
                    <option value="{{ $v }}" {{ request('acao') === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
            <select name="membro" onchange="this.form.submit()" class="table-filter" aria-label="Filtrar por membro">
                <option value="">Todos os membros</option>
                @foreach($membros as $m)
                    <option value="{{ $m->id }}" {{ (string)request('membro') === (string)$m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                @endforeach
            </select>
            <input type="date" name="de" value="{{ request('de') }}" onchange="this.form.submit()" class="table-filter" aria-label="Data inicial">
            <input type="date" name="ate" value="{{ request('ate') }}" onchange="this.form.submit()" class="table-filter" aria-label="Data final">
        </x-table-toolbar>
    </x-slot:action>

    <div class="overflow-x-auto -mx-5 lg:-mx-6 px-5 lg:px-6">
        <table class="w-full text-left min-w-[860px] table-modern">
            <thead>
                <tr>
                    <th>Data / Hora</th>
                    <th>Ação</th>
                    <th>Quem</th>
                    <th>IP · Método</th>
                    <th>Recurso</th>
                    <th class="text-right">Detalhes</th>
                </tr>
            </thead>
            <tbody class="text-[13px]">
                @forelse($logs as $log)
                <tr data-log-row>
                    <td class="text-gray-500 whitespace-nowrap text-[12px] num">
                        {{ $log->created_at?->format('d/m/Y H:i:s') ?? '—' }}
                    </td>
                    <td>
                        <div class="flex items-start gap-2">
                            <x-badge :type="$log->actionType()">{{ $log->actionLabel() }}</x-badge>
                            <div class="min-w-0">
                                <p class="font-bold text-gray-800 leading-snug">{{ $log->description }}</p>
                                @if($log->url)
                                <p class="text-[10px] text-gray-300 font-mono truncate max-w-[220px]" title="{{ $log->url }}">{{ $log->url }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($log->member)
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full grid place-items-center text-white text-[10px] font-extrabold shrink-0"
                                style="background:{{ $log->member->avatarColor() }}">{{ $log->member->initials() }}</span>
                            <span class="min-w-0">
                                <span class="block font-bold text-gray-800 truncate max-w-[150px]">{{ $log->member->name }}</span>
                                <span class="block text-[10px] text-gray-400">{{ $log->member->roleLabel() }}</span>
                            </span>
                        </div>
                        @else
                        <span class="text-gray-300">Sistema</span>
                        @endif
                    </td>
                    <td class="text-gray-500 text-[12px] num">
                        @if($log->ip_address)
                        <p class="font-mono">{{ $log->ip_address }}</p>
                        <p class="text-[10px] text-gray-400">{{ $log->method ?? '—' }}</p>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="text-gray-500 text-[12px]">{{ $log->resourceLabel() ?? '—' }}</td>
                    <td class="text-right whitespace-nowrap">
                        @if($log->changes)
                        <button type="button" data-log-toggle title="Ver detalhes"
                            class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition inline-grid place-items-center">
                            <span class="material-symbols-outlined text-[18px]">code</span>
                        </button>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                </tr>
                @if($log->changes)
                <tr data-log-details class="hidden bg-slate-50/60">
                    <td colspan="6" class="px-6 py-4">
                        <pre class="text-[11px] leading-relaxed text-gray-500 whitespace-pre-wrap font-mono max-h-56 overflow-auto">{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="6" class="text-center text-gray-400">
                        <x-empty-state icon="history" title="Nenhum registro encontrado" hint="Ajuste os filtros ou realize ações no sistema." />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-section-card>

@push('scripts')
<script>
document.querySelectorAll('[data-log-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
        const row = btn.closest('tr');
        const details = row.nextElementSibling;
        if (details?.matches('[data-log-details]')) {
            details.classList.toggle('hidden');
        }
    });
});
</script>
@endpush
@endsection