@extends('layouts.app')
@section('title', ($cartao ? 'Editar' : 'Novo').' Cartão')
@section('breadcrumb', 'Cartões / '.($cartao ? 'Editar' : 'Novo'))
@section('nav-active', 'cartoes')

@section('content')
@php
    $action = $cartao ? route('cartoes.update', $cartao) : route('cartoes.store');
    $val = fn($k, $d = null) => old($k, $cartao?->$k ?? $d);
    $contaSel = old('account_id', $cartao?->account_id);
@endphp
<div class="max-w-2xl mx-auto w-full">
    <a href="{{ route('cartoes') }}" class="inline-flex items-center gap-1 text-[12px] font-bold text-gray-500 hover:text-gray-800"><span class="material-symbols-outlined text-[16px]">arrow_back</span> Voltar para cartões</a>
    <h1 class="text-[24px] font-extrabold tracking-tight mt-1">{{ $cartao ? 'Editar cartão' : 'Novo cartão' }}</h1>
    <p class="text-[13px] text-gray-500">Vincule o cartão a uma conta para herdar a cor dela no visual.</p>

    <div class="grid lg:grid-cols-5 gap-4 mt-5">
        <div class="lg:col-span-2">
            <div id="prev-cartao" class="cc-sheen rounded-lg text-white p-4 min-h-[190px] flex flex-col justify-between shadow-md" style="background:{{ $cartao?->display_color ?? 'linear-gradient(135deg,#0f172a,#334155)' }}">
                <p class="text-[11px] font-extrabold tracking-widest" id="prev-nome">{{ $val('name', 'SEU CARTÃO') }}</p>
                <p class="text-[11px] opacity-70" id="prev-titular">Titular</p>
                <div>
                    <p class="font-extrabold num text-[17px]" id="prev-limite">Limite</p>
                    <p class="text-[11px] opacity-80" id="prev-datas">Fecha · Vence</p>
                </div>
            </div>
            <p class="text-[11px] text-gray-400 mt-2">Pré-visualização ao vivo da cor e dos dados.</p>
        </div>

        <form method="POST" action="{{ $action }}" class="lg:col-span-3 bg-white rounded-lg border border-gray-500 p-5 space-y-4">
            @csrf
            @if($cartao) @method('PATCH') @endif

            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-nome">Nome do cartão *</label>
                <input id="f-nome" name="name" required value="{{ $val('name') }}" placeholder="Digite o nome (ex.: Nubank Ultravioleta)"
                    class="mt-1.5 w-full h-12 rounded-lg border px-4 text-[14px] outline-none focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('name') ? 'border-red-400 focus:border-red-500' : 'border-gray-500 focus:border-emerald-600' }}">
                @error('name')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-[12px] font-bold text-gray-600" for="f-tit">Titular</label>
                    <select id="f-tit" name="holder_user_id" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-2 text-[14px] bg-white">
                        <option value="">Selecione o titular</option>
                        @foreach($membros as $m)<option value="{{ $m->id }}" {{ (string)$val('holder_user_id') === (string)$m->id ? 'selected' : '' }}>{{ $m->name }}</option>@endforeach
                    </select>
                    @error('holder_user_id')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-[12px] font-bold text-gray-600" for="f-bandeira">Bandeira</label>
                    <input id="f-bandeira" name="brand" value="{{ $val('brand') }}" placeholder="Digite a bandeira (ex.: Mastercard)"
                        class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-4 text-[14px] outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10">
                </div>
            </div>

            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-conta">Conta vinculada</label>
                <select id="f-conta" name="account_id" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-2 text-[14px] bg-white">
                    <option value="">Sem vínculo (cor padrão)</option>
                    @foreach($contas as $c)<option value="{{ $c->id }}" data-cor="{{ $c->color }}" {{ (string)$contaSel === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                </select>
                <p class="text-[11px] text-gray-500 mt-1">O cartão herda a cor da conta (ex.: Inter → laranja).</p>
                @error('account_id')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="text-[12px] font-bold text-gray-600" for="f-lim">Limite (R$) *</label>
                    <input id="f-lim" name="credit_limit" required inputmode="decimal" value="{{ $val('credit_limit', '0') }}" placeholder="Digite o limite"
                        class="mt-1.5 w-full h-12 rounded-lg border px-3 text-[14px] num outline-none focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('credit_limit') ? 'border-red-400 focus:border-red-500' : 'border-gray-500 focus:border-emerald-600' }}">
                    @error('credit_limit')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-[12px] font-bold text-gray-600" for="f-fecha">Fecha dia *</label>
                    <input id="f-fecha" name="closing_day" type="number" min="1" max="28" required value="{{ $val('closing_day', '1') }}" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-3 text-[14px]">
                    @error('closing_day')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-[12px] font-bold text-gray-600" for="f-vence">Vence dia *</label>
                    <input id="f-vence" name="due_day" type="number" min="1" max="28" required value="{{ $val('due_day', '10') }}" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-3 text-[14px]">
                    @error('due_day')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            @if($cartao)
            <label class="flex items-center gap-2 text-[13px] text-gray-600"><input type="checkbox" name="active" value="1" {{ old('active', $cartao->active) ? 'checked' : '' }} class="w-4 h-4 accent-emerald-600"> Cartão ativo</label>
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('cartoes') }}" class="h-11 px-5 rounded-lg border border-gray-500 bg-white text-[13px] font-bold flex items-center">Cancelar</a>
                <button class="h-11 px-6 rounded-lg bg-slate-900 text-white text-[13px] font-bold">{{ $cartao ? 'Salvar alterações' : 'Salvar cartão' }}</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const prev = document.getElementById('prev-cartao');
const corConta = () => {
  const sel = document.getElementById('f-conta');
  const opt = sel.options[sel.selectedIndex];
  return (opt && opt.dataset.cor) ? opt.dataset.cor : 'linear-gradient(135deg,#0f172a,#334155)';
};
function atualizaPrev(){
  const nome = document.getElementById('f-nome').value.trim() || 'SEU CARTÃO';
  const tit = document.getElementById('f-tit');
  const lim = document.getElementById('f-lim').value.trim() || 'Limite';
  const fe = document.getElementById('f-fecha').value || '·';
  const ve = document.getElementById('f-vence').value || '·';
  document.getElementById('prev-nome').textContent = nome.toUpperCase();
  document.getElementById('prev-titular').textContent = tit.options[tit.selectedIndex]?.text || 'Titular';
  document.getElementById('prev-limite').textContent = 'R$ ' + lim;
  document.getElementById('prev-datas').textContent = `Fecha ${fe} · Vence ${ve}`;
  prev.style.background = corConta();
}
['f-nome','f-tit','f-lim','f-fecha','f-vence','f-conta'].forEach(id=>document.getElementById(id)?.addEventListener('input', atualizaPrev));
document.getElementById('f-conta')?.addEventListener('change', atualizaPrev);
atualizaPrev();
</script>
@endpush
@endsection
