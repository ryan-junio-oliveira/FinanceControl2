@extends('layouts.app')
@section('title', 'Configurações')
@section('breadcrumb', 'Sistema / Configurações')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Configurações da Conta</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">{{ auth()->user()->family->name }} · preferências reais salvas no banco.</p>
    </div>
</div>

{{-- Tabs --}}
<div class="flex gap-1.5 overflow-x-auto pb-1" id="cfg-tabs">
    @php $tabs = [
        ['id'=>'perfil','label'=>'Perfil da Conta','icon'=>'family_restroom'],
        ['id'=>'seg','label'=>'Segurança','icon'=>'shield'],
        ['id'=>'notif','label'=>'Notificações','icon'=>'notifications_active'],
        ['id'=>'bancos','label'=>'Bancos','icon'=>'account_balance'],
    ]; @endphp
    @foreach($tabs as $i=>$t)
    <button data-tab="{{ $t['id'] }}"
        class="cfg-tab shrink-0 h-10 px-4 rounded-lg text-[13px] font-bold flex items-center gap-2 border transition-all duration-200
        {{ $i===0 ? 'bg-slate-900 text-white border-gray-900 shadow-sm' : 'bg-white border-slate-200 text-gray-500 hover:border-slate-300 hover:text-gray-700' }}">">
        <span class="material-symbols-outlined text-[17px]">{{ $t['icon'] }}</span>
        {{ $t['label'] }}
    </button>
    @endforeach
</div>

{{-- Painel: Perfil --}}
<div data-panel="perfil" class="cfg-panel space-y-4">
    <x-section-card title="Identidade da Conta" subtitle="Dados da sua conta">
        <form method="POST" action="{{ route('configuracoes.update') }}" class="form-grid form-grid-2">
            @csrf @method('PATCH')
            <div class="sm:col-span-2">
                <x-form.field label="Nome da conta" :required="true">
                    <x-form.input name="name" value="{{ auth()->user()->family->name }}" required placeholder="Ex.: Família Silva" />
                </x-form.field>
            </div>
            <x-form.field label="Moeda base">
                <x-form.select name="currency">
                    <option value="BRL" {{ $settings->currency==='BRL'?'selected':'' }}>Real (BRL)</option>
                    <option value="USD" {{ $settings->currency==='USD'?'selected':'' }}>Dólar (USD)</option>
                    <option value="EUR" {{ $settings->currency==='EUR'?'selected':'' }}>Euro (EUR)</option>
                </x-form.select>
            </x-form.field>
            <x-form.field label="Fuso horário">
                <x-form.select name="timezone">
                    <option value="America/Sao_Paulo" {{ $settings->timezone==='America/Sao_Paulo'?'selected':'' }}>Brasília (GMT-3)</option>
                    <option value="America/Manaus" {{ $settings->timezone==='America/Manaus'?'selected':'' }}>Manaus (GMT-4)</option>
                    <option value="America/Noronha" {{ $settings->timezone==='America/Noronha'?'selected':'' }}>Noronha (GMT-2)</option>
                </x-form.select>
            </x-form.field>
            <x-form.field label="Fechamento do ciclo (dia)">
                <x-form.input name="closing_day" type="number" min="1" max="28" value="{{ $settings->closing_day }}" />
            </x-form.field>
            <x-form.field label="Aprovação acima de">
                <x-form.money name="approval_threshold" value="{{ number_format($settings->approval_threshold, 2, ',', '.') }}" placeholder="500,00" />
            </x-form.field>
            <x-form.field label="Ocultar despesas abaixo de">
                <x-form.money name="privacy_hide_under" value="{{ number_format($settings->privacy_hide_under, 2, ',', '.') }}" placeholder="50,00" />
            </x-form.field>
            <div class="sm:col-span-2 flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer gap-3">
                    <div class="relative">
                        <input type="checkbox" name="consolidate_dependent_yield" value="1"
                            {{ $settings->consolidate_dependent_yield ? 'checked' : '' }}
                            class="sr-only peer" id="toggle-consolidate">
                        <div class="w-10 h-6 bg-slate-200 rounded-full peer peer-checked:bg-emerald-500 peer-focus:ring-2 peer-focus:ring-emerald-500/30 transition-all"></div>
                        <div class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow transition-all peer-checked:translate-x-4"></div>
                    </div>
                    <span class="text-[13px] font-semibold text-gray-700">Consolidar proventos de dependentes</span>
                </label>
            </div>
            <div class="sm:col-span-2">
                <x-btn-submit icon="save">Salvar configurações</x-btn-submit>
            </div>
        </form>
    </x-section-card>
</div>

{{-- Painel: Segurança --}}
<div data-panel="seg" class="cfg-panel hidden space-y-4">
    <x-section-card title="Encerrar Outras Sessões" subtitle="Exige sua senha atual para segurança">
        <form method="POST" action="{{ route('configuracoes.sessoes') }}" class="flex flex-wrap gap-3 items-end">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <x-form.field label="Sua senha atual" :required="true">
                    <x-form.input name="password" type="password" required placeholder="••••••••" />
                </x-form.field>
            </div>
            <div>
                <x-btn-submit color="danger" icon="logout">Encerrar outras sessões</x-btn-submit>
            </div>
        </form>
    </x-section-card>
</div>

{{-- Painel: Notificações --}}
<div data-panel="notif" class="cfg-panel hidden space-y-4">
    <x-section-card title="Notificações" subtitle="Preferências da conta">
        <form method="POST" action="{{ route('configuracoes.notificacoes') }}" class="divide-y divide-slate-50">
            @csrf @method('PATCH')
            @php $opts = [
                'compra_dependente' => 'Compra de dependente',
                'fatura_vencimento' => 'Fatura próxima do vencimento',
                'resumo_semanal' => 'Resumo semanal',
                'dividendo' => 'Dividendo creditado',
            ]; @endphp
            @foreach($opts as $k=>$l)
            <label class="flex items-center justify-between gap-3 py-3.5 cursor-pointer group">
                <span class="flex-1 text-[13px] font-semibold text-gray-700 group-hover:text-gray-900 transition">{{ $l }}</span>
                <div class="relative">
                    <input type="checkbox" name="notifications[{{ $k }}]" value="1"
                        {{ ($settings->notifications[$k] ?? false) ? 'checked' : '' }}
                        class="sr-only peer">
                    <div class="w-10 h-6 bg-slate-200 rounded-lg peer peer-checked:bg-emerald-500 transition-all"></div>
                    <div class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow transition-all peer-checked:translate-x-4"></div>
                </div>
            </label>
            @endforeach
            <div class="pt-4">
                <x-btn-submit icon="notifications_active">Salvar notificações</x-btn-submit>
            </div>
        </form>
    </x-section-card>
</div>

{{-- Painel: Bancos --}}
<div data-panel="bancos" class="cfg-panel hidden space-y-4">
    <x-section-card title="Bancos" subtitle="Referência manual — sem sincronização automática com bancos">
        @forelse($bancos as $b)
        <div class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 hover:border-slate-300 mb-3 transition-all">
            <span class="w-11 h-11 rounded-lg grid place-items-center text-[13px] font-extrabold text-white shadow-sm shrink-0"
                style="background: linear-gradient(135deg, #1E293B, #0F172A);">
                {{ mb_strtoupper(mb_substr($b->bank, 0, 2)) }}
            </span>
            <div class="flex-1 min-w-0">
                <p class="text-[13px] font-extrabold text-gray-800">{{ $b->bank }}</p>
                <p class="text-[12px] text-gray-400">{{ $b->details ?? '—' }} · anotado em {{ $b->created_at->format('d/m/Y') }}</p>
            </div>
            <x-badge type="neutral">Registro manual</x-badge>
            <form method="POST" action="{{ route('configuracoes.bancos.destroy', $b) }}" class="inline ml-2">
                @csrf @method('DELETE')
                <button class="text-[12px] font-bold text-gray-300 hover:text-red-500 transition px-2 py-1 rounded-lg hover:bg-red-50">Remover</button>
            </form>
        </div>
        @empty
        <div class="text-center py-10 text-gray-400">
            <span class="material-symbols-outlined text-[44px] text-gray-300">account_balance</span>
            <p class="text-[13px] font-bold mt-3 text-gray-500">Nenhum banco anotado</p>
            <p class="text-[12px] mt-1">Adicione os bancos da sua conta como referência para os lançamentos.</p>
        </div>
        @endforelse
        <a href="{{ route('configuracoes.bancos.create') }}"
            class="mt-3 h-12 rounded-lg border-2 border-dashed border-slate-200 text-[13px] font-bold text-gray-400
            hover:border-emerald-400 hover:text-emerald-600 hover:bg-emerald-50/50 flex items-center justify-center gap-2 transition-all duration-200">
            <span class="material-symbols-outlined text-[18px]">add_circle</span>
            Anotar banco
        </a>
    </x-section-card>
</div>

@push('scripts')
<script>
document.querySelectorAll('.cfg-tab').forEach(btn => btn.addEventListener('click', () => {
    document.querySelectorAll('.cfg-tab').forEach(x => {
        x.className = 'cfg-tab shrink-0 h-10 px-4 rounded-lg text-[13px] font-bold flex items-center gap-2 border transition-all duration-200 bg-white border-slate-200 text-gray-500 hover:border-slate-300 hover:text-gray-700';
    });
    btn.className = 'cfg-tab shrink-0 h-10 px-4 rounded-lg text-[13px] font-bold flex items-center gap-2 border transition-all duration-200 bg-slate-900 text-white border-gray-900 shadow-sm';
    document.querySelectorAll('.cfg-panel').forEach(p => p.classList.toggle('hidden', p.dataset.panel !== btn.dataset.tab));
}));
</script>
@endpush
@endsection
