@extends('layouts.app')
@section('title', 'Convidar Membro')
@section('breadcrumb', 'Membros / Convidar')
@section('nav-active', 'grupo')

@section('content')
<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        title="Convidar membro"
        subtitle="Geramos um link de primeiro acesso para o membro definir a senha."
        :backUrl="route('grupo')"
        backLabel="Voltar para o grupo"
        icon="person_add"
        iconBg="linear-gradient(135deg,#FFF1F2,#FFE4E6)"
        iconColor="#E11D48" />

    <form method="POST" action="{{ route('grupo.convites.store') }}" class="form-card tint-rose">
        @csrf
        <div class="form-grid">
            <x-form.field label="Nome" for="f-nome" :required="true" :error="$errors->first('name')">
                <x-form.input id="f-nome" name="name" required value="{{ old('name') }}" placeholder="Ex.: Mariana Silva" />
            </x-form.field>
            <x-form.field label="E-mail" for="f-email" :required="true" :error="$errors->first('email')">
                <x-form.input id="f-email" name="email" required type="email" value="{{ old('email') }}" placeholder="Ex.: membro@email.com" />
            </x-form.field>
            <x-form.field label="Papel" for="f-papel" :required="true" :error="$errors->first('role')">
                <x-form.select id="f-papel" name="role">
                    <option value="">Selecione o papel</option>
                    <option value="co_admin" {{ old('role') === 'co_admin' ? 'selected' : '' }}>Co-administrador</option>
                    <option value="dependente" {{ old('role', 'dependente') === 'dependente' ? 'selected' : '' }}>Dependente</option>
                    <option value="junior" {{ old('role') === 'junior' ? 'selected' : '' }}>Júnior</option>
                </x-form.select>
            </x-form.field>
        </div>
        <x-form.actions :cancelUrl="route('grupo')" submitLabel="Criar convite" submitIcon="person_add" color="rose" />
    </form>
</div>
@endsection
