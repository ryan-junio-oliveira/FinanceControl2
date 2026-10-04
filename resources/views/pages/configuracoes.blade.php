@extends('layouts.app')
@section('title', 'Configurações')
@section('breadcrumb', 'Sistema / Configurações')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Configurações da Conta</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">{{ auth()->user()->group->name }} · preferências reais salvas no banco.</p>
    </div>
</div>

{{-- Tabs --}}
<div class="flex gap-1.5 overflow-x-auto pb-1" id="cfg-tabs">
    @php $tabs = [
        ['id'=>'perfil','label'=>'Perfil da Conta','icon'=>'group_restroom'],
        ['id'=>'notif','label'=>'Notificações','icon'=>'notifications_active'],
    ]; @endphp
    @foreach($tabs as $i=>$t)
    <button data-tab="{{ $t['id'] }}"
        class="cfg-tab shrink-0 h-10 px-4 rounded-lg text-[13px] font-bold flex items-center gap-2 border transition-all duration-200
        {{ $i===0 ? 'bg-slate-900 text-white border-gray-900 shadow-sm' : 'bg-white border-slate-200 text-gray-500 hover:border-slate-300 hover:text-gray-700' }}">
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
                    <x-form.input name="name" value="{{ old('name', auth()->user()->group->name) }}" required placeholder="Ex.: Família Silva" />
                </x-form.field>
            </div>
            <x-form.field label="Moeda base">
                <x-form.select name="currency">
                    <option value="BRL" {{ old('currency', $settings->currency)==='BRL'?'selected':'' }}>Real (BRL)</option>
                    <option value="USD" {{ old('currency', $settings->currency)==='USD'?'selected':'' }}>Dólar (USD)</option>
                    <option value="EUR" {{ old('currency', $settings->currency)==='EUR'?'selected':'' }}>Euro (EUR)</option>
                </x-form.select>
            </x-form.field>
            <x-form.field label="Fuso horário">
                <x-form.select name="timezone">
                    <option value="America/Sao_Paulo" {{ old('timezone', $settings->timezone)==='America/Sao_Paulo'?'selected':'' }}>Brasília (GMT-3)</option>
                    <option value="America/Manaus" {{ old('timezone', $settings->timezone)==='America/Manaus'?'selected':'' }}>Manaus (GMT-4)</option>
                    <option value="America/Noronha" {{ old('timezone', $settings->timezone)==='America/Noronha'?'selected':'' }}>Noronha (GMT-2)</option>
                </x-form.select>
            </x-form.field>
            <div class="sm:col-span-2 flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer gap-3">
                    <div class="relative">
                        <input type="checkbox" name="consolidate_dependent_yield" value="1"
                            {{ old('consolidate_dependent_yield', $settings->consolidate_dependent_yield) ? 'checked' : '' }}
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

{{-- Painel: Segurança — DESATIVADO TEMPORARIAMENTE.
     Futuro: o administrador poderá exigir 2FA de todos os membros da família.
     O backend (configuracoes.sessoes) segue ativo; só a UI está oculta.
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
--}}

{{-- Painel: Notificações --}}
<div data-panel="notif" class="cfg-panel hidden space-y-4">
    <x-section-card title="Notificações" subtitle="Preferências da conta">
        <form method="POST" action="{{ route('configuracoes.notificacoes') }}" class="divide-y divide-slate-50">
            @csrf @method('PATCH')
            @php $opts = [
                'fatura_vencimento' => 'Fatura próxima do vencimento',
                'conta_vencimento' => 'Conta próxima do pagamento',
            ]; @endphp
            @foreach($opts as $k=>$l)
            <label class="flex items-center justify-between gap-3 py-3.5 cursor-pointer group">
                <span class="flex-1 text-[13px] font-semibold text-gray-700 group-hover:text-gray-900 transition">{{ $l }}</span>
                <div class="relative">
                    <input type="checkbox" name="notifications[{{ $k }}]" value="1"
                        {{ old("notifications.$k", $settings->notifications[$k] ?? false) ? 'checked' : '' }}
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
