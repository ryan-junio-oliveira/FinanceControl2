@extends('layouts.app')
@section('title', ($transaction ? 'Editar' : 'Novo').' '.($type === 'despesa' ? 'Despesa' : 'Receita'))
@section('breadcrumb', ($type === 'despesa' ? 'Despesas' : 'Receitas').' / '.($transaction ? 'Editar' : 'Novo'))
@section('nav-active', $type === 'despesa' ? 'despesas' : 'receitas')

@section('content')
@php
    $isDespesa = $type === 'despesa';
    $titulo = ($transaction ? 'Editar' : 'Nova').' '.($isDespesa ? 'despesa' : 'receita');
    $action = $transaction ? route('lancamentos.update', $transaction) : route($isDespesa ? 'despesas.store' : 'receitas.store');
    $voltar = route($isDespesa ? 'despesas' : 'receitas', ['mes' => $mes]);
    $val = fn($k, $d = null) => old($k, $transaction?->$k ?? $d);
    $dataOcorrido = old('occurred_on', $transaction?->occurred_on?->format('Y-m-d') ?? now()->toDateString());
    $dataVenc = old('due_on', $transaction?->due_on?->format('Y-m-d') ?? '');
@endphp

<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        :title="$titulo"
        subtitle="Preencha os dados do lançamento."
        :backUrl="$voltar"
        :backLabel="'Voltar para ' . ($isDespesa ? 'despesas' : 'receitas')"
        :icon="$isDespesa ? 'trending_down' : 'trending_up'"
        :iconBg="$isDespesa ? 'linear-gradient(135deg,#FEE2E2,#FECACA)' : 'linear-gradient(135deg,#D1FAE5,#A7F3D0)'"
        :iconColor="$isDespesa ? '#DC2626' : '#059669'" />

    <form method="POST" action="{{ $action }}" class="form-card {{ $isDespesa ? 'tint-danger' : 'tint-success' }}" enctype="multipart/form-data">
        @csrf
        @if($transaction) @method('PATCH') @endif

        <div class="form-grid">
            <x-form.field label="Descrição" for="f-desc" :required="true" :error="$errors->first('description')">
                <x-form.input id="f-desc" name="description" required value="{{ $val('description') }}" placeholder="Ex.: Mercado Central" autocomplete="off" :error="$errors->has('description')" />
            </x-form.field>

            <div class="form-grid form-grid-2">
                <x-form.field label="Valor" for="f-valor" :required="true" :error="$errors->first('amount')">
                    <x-form.money id="f-valor" name="amount" required value="{{ $val('amount') }}" placeholder="0,00" :error="$errors->has('amount')" />
                </x-form.field>

                <x-form.field label="{{ $isDespesa ? 'Forma de pagamento' : 'Forma de recebimento' }}" for="f-pagamento" :error="$errors->first('payment_method')">
                    <x-form.select id="f-pagamento" name="payment_method">
                        @foreach(['pix' => 'Pix', 'ted' => 'TED', 'dinheiro_fisico' => 'Dinheiro físico', 'dinheiro_digital' => 'Dinheiro digital'] + ($isDespesa ? ['cartao' => 'Cartão'] : []) as $v => $l)
                            <option value="{{ $v }}" {{ $val('payment_method', 'pix') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>
            </div>

            <div class="form-grid form-grid-2">
                <x-form.field label="Categoria" for="f-cat" :error="$errors->first('category_id')">
                    <x-form.select id="f-cat" name="category_id">
                        <option value="">Selecione a categoria</option>
                        @foreach($categorias as $c)
                            <option value="{{ $c->id }}" {{ (string)$val('category_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>

                <x-form.field id="f-conta-wrap" label="Conta" for="f-conta" :error="$errors->first('account_id')">
                    <x-form.select id="f-conta" name="account_id">
                        <option value="">Selecione a conta</option>
                        @foreach($contas as $c)
                            <option value="{{ $c->id }}" data-kind="{{ $c->kind }}" {{ (string)$val('account_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->label }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>
            </div>

            <div class="form-grid form-grid-3">
                <x-form.field label="Data" for="f-data" :required="true" :error="$errors->first('occurred_on')">
                    <x-form.input id="f-data" name="occurred_on" type="date" required value="{{ $dataOcorrido }}" />
                </x-form.field>

                <x-form.field label="Vencimento" for="f-venc" :error="$errors->first('due_on')">
                    <x-form.input id="f-venc" name="due_on" type="date" value="{{ $dataVenc }}" />
                </x-form.field>

                <x-form.field label="Situação" for="f-status" :required="true" :error="$errors->first('status')">
                    <x-form.select id="f-status" name="status">
                        @foreach(['pago' => $isDespesa ? 'Paga' : 'Recebida', 'pendente' => 'Pendente', 'agendado' => 'Agendada'] as $v => $l)
                            <option value="{{ $v }}" {{ $val('status', 'pago') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>
            </div>

            @if($isDespesa)
                <div id="f-pagseg" class="hidden form-grid form-grid-2">
                    <x-form.field label="Cartão" for="f-cartao" :error="$errors->first('credit_card_id')">
                        <x-form.select id="f-cartao" name="credit_card_id">
                            <option value="">Selecione o cartão</option>
                            @foreach($cartoes as $c)
                                <option value="{{ $c->id }}" {{ (string)$val('credit_card_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </x-form.select>
                    </x-form.field>
                    <x-form.field label="Parcelas" for="f-parc" hint="À vista = 1. Parcelado divide o valor em vencimentos mensais.">
                        <x-form.select id="f-parc" name="installments_total">
                            <option value="1" {{ old('installments_total', '1') === '1' ? 'selected' : '' }}>À vista</option>
                            @for($i = 2; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ (string)old('installments_total') === (string)$i ? 'selected' : '' }}>{{ $i }}x</option>
                            @endfor
                            @foreach([18, 24, 36, 48] as $i)
                                <option value="{{ $i }}" {{ (string)old('installments_total') === (string)$i ? 'selected' : '' }}>{{ $i }}x</option>
                            @endforeach
                        </x-form.select>
                    </x-form.field>
                </div>
            @endif

            <x-form.field label="Observações" for="f-obs">
                <x-form.input id="f-obs" name="notes" value="{{ $val('notes') }}" placeholder="Opcional" />
            </x-form.field>

            <x-form.field label="Comprovante" for="f-anexo" hint="PDF ou imagem de até 5 MB (nota fiscal, recibo)." :error="$errors->first('anexo')">
                <x-form.input id="f-anexo" name="anexo" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" />
            </x-form.field>

            @if($transaction && $transaction->attachments->isNotEmpty())
            <div class="rounded-xl border border-slate-200 divide-y divide-slate-100">
                @foreach($transaction->attachments as $ax)
                <div class="flex items-center gap-2.5 px-3.5 py-2.5">
                    <span class="material-symbols-outlined text-[18px] text-slate-400">description</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-bold text-gray-800 truncate">{{ $ax->original_name }}</p>
                        <p class="text-[11px] text-gray-400 num">{{ $ax->sizeForHumans() }} · {{ $ax->created_at->format('d/m/Y') }}</p>
                    </div>
                    <a title="Baixar" href="{{ route('anexos.download', $ax) }}"
                        class="w-8 h-8 rounded-lg hover:bg-slate-100 text-blue-500 hover:text-blue-700 transition inline-grid place-items-center">
                        <span class="material-symbols-outlined text-[19px]">download</span>
                    </a>
                    <form method="POST" action="{{ route('anexos.destroy', $ax) }}" class="inline"
                        onsubmit="return confirm('Remover este anexo?')">
                        @csrf @method('DELETE')
                        <x-btn-submit color="danger" iconOnly icon="delete" title="Remover anexo" />
                    </form>
                </div>
                @endforeach
            </div>
            @endif

            <x-form.field label="Lançamento fixo" hint="Fixas se repetem todo mês (ex.: aluguel, salário) e podem ser filtradas nas listagens.">
                <label class="toggle-switch mt-1">
                    <input type="checkbox" name="is_fixed" value="1" {{ $val('is_fixed') ? 'checked' : '' }}>
                    <div class="toggle-track">
                        <div class="toggle-thumb"></div>
                    </div>
                    <span class="toggle-label-text">{{ $isDespesa ? 'Despesa' : 'Receita' }} fixa mensal</span>
                </label>
            </x-form.field>
        </div>

        <x-form.actions :cancelUrl="$voltar" :submitLabel="$transaction ? 'Salvar alterações' : 'Salvar'" :submitIcon="$transaction ? 'save' : 'add_circle'" :color="$isDespesa ? 'danger' : 'success'" />
    </form>

    @if($transaction && $transaction->auditLogs->isNotEmpty())
    <x-section-card title="Histórico" subtitle="Quem criou e alterou este lançamento" class="mt-4">
        <ol class="space-y-3">
            @foreach($transaction->auditLogs->take(10) as $log)
            <li class="flex items-start gap-2.5 text-[12px]">
                <span class="w-7 h-7 rounded-lg bg-slate-100 grid place-items-center shrink-0 text-[11px] font-extrabold text-slate-500">
                    {{ mb_substr($log->member->name ?? '?', 0, 1) }}
                </span>
                <div class="min-w-0">
                    <p class="text-gray-700"><strong class="text-gray-900">{{ $log->member->name ?? 'Sistema' }}</strong> {{ $log->actionLabel() }}
                        <span class="text-gray-400 num">· {{ $log->created_at->format('d/m/Y H:i') }}</span>
                    </p>
                    @if($log->action === 'updated' && is_array($log->changes))
                    <ul class="mt-1 space-y-0.5 text-gray-500">
                        @foreach($log->changes as $campo => $v)
                        <li class="num"><span class="font-bold">{{ $campo }}</span>: {{ is_scalar($v['de'] ?? null) ? $v['de'] : '—' }} → {{ is_scalar($v['para'] ?? null) ? $v['para'] : '—' }}</li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            </li>
            @endforeach
        </ol>
    </x-section-card>
    @endif
</div>

<script>
(function () {
    const pag = document.getElementById('f-pagamento');
    if (!pag) return;
    const seg = document.getElementById('f-pagseg');
    const contaWrap = document.getElementById('f-conta-wrap');
    const contaSel = document.getElementById('f-conta');
    const allOpts = contaSel ? Array.from(contaSel.options) : [];

    const toggle = () => {
        const v = pag.value;
        if (seg) seg.classList.toggle('hidden', v !== 'cartao');
        if (contaWrap) contaWrap.classList.toggle('hidden', v === 'dinheiro_fisico' || v === 'cartao');
        if (contaSel) {
            allOpts.forEach((o) => {
                o.hidden = v === 'dinheiro_digital' && o.dataset.kind === 'carteira';
            });
        }
    };
    pag.addEventListener('change', toggle);
    toggle();
})();
</script>
@endsection
