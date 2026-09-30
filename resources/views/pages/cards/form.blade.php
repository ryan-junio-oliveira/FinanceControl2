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
<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        :title="$cartao ? 'Editar cartão' : 'Novo cartão'"
        subtitle="Vincule o cartão a uma conta para herdar a cor dela no visual."
        :backUrl="route('cartoes')"
        backLabel="Voltar para cartões"
        icon="credit_card"
        iconBg="linear-gradient(135deg,#FFF7ED,#FFEDD5)"
        iconColor="#EA580C" />

    <div class="grid lg:grid-cols-5 gap-4">
        <div class="lg:col-span-2">
            <div id="prev-cartao" class="cc-sheen rounded-2xl text-white p-5 min-h-[190px] flex flex-col justify-between shadow-md" style="background:{{ $cartao?->display_color ?? 'linear-gradient(135deg,#0f172a,#334155)' }}">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[11px] font-extrabold tracking-widest" id="prev-nome">{{ $val('name', 'SEU CARTÃO') }}</p>
                    <span class="text-[10px] font-bold rounded-lg px-2 py-0.5 uppercase tracking-wider" style="background: rgba(0,0,0,0.2);" id="prev-bandeira">{{ $cartao?->brand_label ?? '' }}</span>
                </div>
                <p class="text-[11px] opacity-70" id="prev-titular">Titular</p>
                <div>
                    <p class="font-extrabold num text-[17px]" id="prev-limite">Limite</p>
                    <p class="text-[11px] opacity-80" id="prev-datas">Fecha · Vence</p>
                </div>
            </div>
            <p class="fld-hint">Pré-visualização ao vivo da cor e dos dados.</p>
            @if($cartao && $cartao->open_invoice > 0)
                <form method="POST" action="{{ route('cartoes.fatura.pagar', $cartao) }}" onsubmit="return confirm('Pagar a fatura de {{ $cartao->name }}? Isso liquida todos os itens pendentes.')" class="mt-4">
                    @csrf
                    <x-btn-submit color="orange" icon="payments" class="w-full">Pagar fatura</x-btn-submit>
                </form>
            @endif
        </div>

        <form method="POST" action="{{ $action }}" class="lg:col-span-3 form-card tint-orange">
            @csrf
            @if($cartao) @method('PATCH') @endif

            <div class="form-grid">
                <x-form.field label="Nome do cartão" for="f-nome" :required="true" :error="$errors->first('name')">
                    <x-form.input id="f-nome" name="name" required value="{{ $val('name') }}" placeholder="Ex.: Nubank Ultravioleta" />
                </x-form.field>

                <div class="form-grid form-grid-2">
                    <x-form.field label="Titular" for="f-tit" :error="$errors->first('holder_user_id')">
                        <x-form.select id="f-tit" name="holder_user_id">
                            <option value="">Selecione o titular</option>
                            @foreach($membros as $m)<option value="{{ $m->id }}" {{ (string)$val('holder_user_id') === (string)$m->id ? 'selected' : '' }}>{{ $m->name }}</option>@endforeach
                        </x-form.select>
                    </x-form.field>
                    <x-form.field label="Bandeira" for="f-bandeira" :error="$errors->first('brand')">
                        <x-form.select id="f-bandeira" name="brand">
                            <option value="">Selecione a bandeira</option>
                            @foreach(\App\Enums\CardBrand::cases() as $b)<option value="{{ $b->value }}" {{ (string)$val('brand') === $b->value ? 'selected' : '' }}>{{ $b->label() }}</option>@endforeach
                        </x-form.select>
                    </x-form.field>
                </div>

                <x-form.field label="Conta vinculada" for="f-conta" hint="O cartão herda a cor da conta (ex.: Inter → laranja)." :error="$errors->first('account_id')">
                    <x-form.select id="f-conta" name="account_id">
                        <option value="">Sem vínculo (cor padrão)</option>
                        @foreach($contas as $c)<option value="{{ $c->id }}" data-cor="{{ $c->color }}" {{ (string)$contaSel === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                    </x-form.select>
                </x-form.field>

                <div class="form-grid form-grid-3">
                    <x-form.field label="Limite" for="f-lim" :required="true" :error="$errors->first('credit_limit')">
                        <x-form.money id="f-lim" name="credit_limit" required value="{{ $val('credit_limit', '0') }}" placeholder="0,00" />
                    </x-form.field>
                    <x-form.field label="Fecha dia" for="f-fecha" :required="true" :error="$errors->first('closing_day')">
                        <x-form.input id="f-fecha" name="closing_day" type="number" min="1" max="28" required value="{{ $val('closing_day', '1') }}" />
                    </x-form.field>
                    <x-form.field label="Vence dia" for="f-vence" :required="true" :error="$errors->first('due_day')">
                        <x-form.input id="f-vence" name="due_day" type="number" min="1" max="28" required value="{{ $val('due_day', '10') }}" />
                    </x-form.field>
                </div>

                @if($cartao)
                    <x-form.check name="active" value="1" :checked="(bool) old('active', $cartao->active)" label="Cartão ativo" />
                @endif
            </div>

            <x-form.actions :cancelUrl="route('cartoes')" :submitLabel="$cartao ? 'Salvar alterações' : 'Salvar cartão'" :submitIcon="$cartao ? 'save' : 'add_circle'" color="orange" />
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
  const band = document.getElementById('f-bandeira');
  const lim = document.getElementById('f-lim').value.trim() || 'Limite';
  const fe = document.getElementById('f-fecha').value || '·';
  const ve = document.getElementById('f-vence').value || '·';
  document.getElementById('prev-nome').textContent = nome.toUpperCase();
  document.getElementById('prev-titular').textContent = tit.options[tit.selectedIndex]?.text || 'Titular';
  document.getElementById('prev-bandeira').textContent = band.options[band.selectedIndex]?.text || '';
  document.getElementById('prev-limite').textContent = 'R$ ' + lim;
  document.getElementById('prev-datas').textContent = `Fecha ${fe} · Vence ${ve}`;
  prev.style.background = corConta();
}
['f-nome','f-tit','f-bandeira','f-lim','f-fecha','f-vence','f-conta'].forEach(id=>document.getElementById(id)?.addEventListener('input', atualizaPrev));
document.getElementById('f-conta')?.addEventListener('change', atualizaPrev);
document.getElementById('f-bandeira')?.addEventListener('change', atualizaPrev);
atualizaPrev();
</script>
@endpush
@endsection
