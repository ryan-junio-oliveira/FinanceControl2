<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Visão Geral') · FinFamília</title>
    <meta name="description" content="FinFamília — gestão financeira familiar: dashboard, despesas, receitas, investimentos, cartões, contas e grupo familiar.">
    <meta name="theme-color" content="#059669">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-[#F8FAFC] text-gray-900">
@php
    $user = auth()->user();
    $family = $user?->family;
    $isGestor = $user && $user->isAdmin();
    $mesRef = \App\Support\Fin::month();
    $mesLabel = ucfirst(\Carbon\Carbon::createFromFormat('Y-m', $mesRef)->locale('pt_BR')->translatedFormat('F, Y'));
    $mesPrev = \Carbon\Carbon::createFromFormat('Y-m', $mesRef)->subMonth()->format('Y-m');
    $mesNext = \Carbon\Carbon::createFromFormat('Y-m', $mesRef)->addMonth()->format('Y-m');
@endphp
<div class="min-h-screen w-full overflow-x-clip">

    {{-- ============ SIDEBAR (fixa, scroll interno) ============ --}}
    <aside id="sidebar"
        class="fixed z-40 inset-y-0 left-0 w-[268px] -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col h-screen overflow-hidden"
        style="background: linear-gradient(180deg, #ffffff 0%, #fafcfb 100%); border-right: 1px solid #6b7280;">

        {{-- Topo da sidebar: logo + fechar --}}
        <div class="px-5 pt-5 pb-4 flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                <span class="sidebar-brand-icon group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">savings</span>
                </span>
                <span class="text-[19px] font-extrabold tracking-tight">Fin<span class="text-emerald-600">Família</span></span>
            </a>
            <button class="lg:hidden ml-auto w-8 h-8 grid place-items-center rounded-lg text-gray-400 hover:bg-slate-100 transition"
                onclick="document.getElementById('sidebar').classList.add('-translate-x-full');document.getElementById('backdrop').classList.add('hidden')">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        {{-- Family pill --}}
        <a href="{{ route('familia') }}" class="family-pill mx-3 mb-3 no-underline">
            <span class="w-9 h-9 rounded-full grid place-items-center text-white text-xs font-bold shrink-0 ring-2 ring-white/80"
                style="background:linear-gradient(135deg,#8B5CF6,#EC4899)">{{ mb_substr($family->name ?? 'FF', 0, 2) }}</span>
            <div class="min-w-0 flex-1">
                <p class="text-[13px] font-bold truncate text-gray-800">{{ $family->name ?? '—' }}</p>
                <p class="text-[11px] text-gray-500">{{ $family?->users()->count() ?? 0 }} pessoa(s) na conta</p>
            </div>
            <span class="material-symbols-outlined text-emerald-500 text-[18px]">chevron_right</span>
        </a>

        {{-- Nav principal --}}
        <nav class="flex-1 overflow-y-auto px-3 pb-3">
            <p class="nav-section-label">Menu principal</p>
            @php
                $nav = [
                    ['route'=>'dashboard','label'=>'Dashboard','icon'=>'dashboard'],
                    ['route'=>'despesas','label'=>'Despesas','icon'=>'trending_down'],
                    ['route'=>'receitas','label'=>'Receitas','icon'=>'trending_up'],
                    ['route'=>'investimentos','label'=>'Investimentos','icon'=>'savings','gestor'=>true],
                    ['route'=>'cartoes','label'=>'Cartões','icon'=>'credit_card'],
                    ['route'=>'contas','label'=>'Contas','icon'=>'account_balance'],
                    ['route'=>'categorias','label'=>'Categorias','icon'=>'category'],
                    ['route'=>'familia','label'=>'Pessoas','icon'=>'group'],
                    ['route'=>'perfil','label'=>'Meu Perfil','icon'=>'person'],
                ];
            @endphp
            @foreach($nav as $item)
                @continue(!empty($item['gestor']) && !$isGestor)
                @php $active = request()->routeIs($item['route'], $item['route'].'.*') || ($__env->hasSection('nav-active') && trim($__env->yieldContent('nav-active')) === $item['route']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="nav-item mb-0.5 {{ $active ? 'active' : '' }}">
                    <span class="material-symbols-outlined text-[20px]{{ $active ? ' material-symbols-filled' : '' }}">{{ $item['icon'] }}</span>
                    <span class="flex-1">{{ $item['label'] }}</span>
                    @if(!empty($item['dot']))
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-lg num {{ $active ? 'bg-white/20 text-white' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">{{ $item['dot'] }}</span>
                    @endif
                    @if($active)
                        <span class="w-1.5 h-1.5 rounded-full bg-white/70 shrink-0"></span>
                    @endif
                </a>
            @endforeach

            @if($isGestor)
            <p class="nav-section-label mt-2">Sistema</p>
            @php
                $sys = [
                    ['route'=>'configuracoes','label'=>'Configurações','icon'=>'settings'],
                ];
            @endphp
            @foreach($sys as $item)
                @php $active = request()->routeIs($item['route'], $item['route'].'.*') || ($__env->hasSection('nav-active') && trim($__env->yieldContent('nav-active')) === $item['route']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="nav-item mb-0.5 {{ $active ? 'active' : '' }}">
                    <span class="material-symbols-outlined text-[20px]">{{ $item['icon'] }}</span>
                    <span class="flex-1">{{ $item['label'] }}</span>
                </a>
            @endforeach
            @endif
        </nav>
    </aside>

    <div id="backdrop"
        class="fixed inset-0 z-30 hidden lg:hidden"
        style="background: rgba(15,23,42,.35); backdrop-filter: blur(4px);"
        onclick="document.getElementById('sidebar').classList.add('-translate-x-full');this.classList.add('hidden')">
    </div>

    {{-- ============ MAIN (respeita a largura da sidebar) ============ --}}
    <div class="flex flex-col h-screen min-w-0 w-full lg:ml-[268px] lg:w-[calc(100%-268px)]">

        {{-- Header fixo --}}
        <header data-app-header class="shrink-0 z-20 glass border-b border-gray-500/70 shadow-[0_1px_0_0_rgba(226,232,240,0.7)]">
            <div class="w-full min-w-0 max-w-[1600px] mx-auto px-4 lg:px-8 py-3 flex items-center gap-3">

                {{-- Hambúrguer mobile --}}
                <button class="lg:hidden w-10 h-10 grid place-items-center rounded-lg border border-gray-500 bg-white shadow-sm hover:shadow-md transition"
                    onclick="document.getElementById('sidebar').classList.remove('-translate-x-full');document.getElementById('backdrop').classList.remove('hidden')">
                    <span class="material-symbols-outlined text-gray-600">menu</span>
                </button>

                {{-- Breadcrumb --}}
                <nav class="hidden md:flex items-center gap-1.5 text-[13px] font-medium">
                    <span class="text-emerald-700 font-extrabold tracking-tight">FinFamília</span>
                    <span class="material-symbols-outlined text-[15px] text-gray-300">chevron_right</span>
                    <span class="text-gray-600 font-semibold">@yield('breadcrumb', 'Visão Geral / Dashboard')</span>
                </nav>

                <div class="flex-1"></div>

                {{-- Avatar usuário (dropdown) --}}
                <div class="relative" data-user-menu>
                    <button type="button" data-user-menu-btn
                        class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-lg hover:bg-slate-100 transition group" title="Minha conta">
                        <span class="w-9 h-9 rounded-full grid place-items-center text-white text-[12px] font-extrabold ring-2 ring-white shadow-sm"
                            style="background:{{ $user->avatarColor() }}">{{ $user->initials() }}</span>
                        <div class="hidden xl:block leading-tight text-left">
                            <p class="text-[13px] font-bold">{{ $user->name }}</p>
                            <p class="text-[11px] text-gray-400">{{ $user->roleLabel() }}</p>
                        </div>
                        <span class="material-symbols-outlined text-[16px] text-gray-300 group-hover:text-gray-500 transition" data-user-menu-caret>expand_more</span>
                    </button>

                    {{-- Dropdown --}}
                    <div data-user-menu-dropdown
                        class="hidden absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-500 py-2 overflow-hidden z-50 transition-all duration-200 origin-top-right scale-95 opacity-0">
                        <div class="px-4 py-3 border-b border-gray-500 mb-1 bg-slate-50/70">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Autenticado como</p>
                            <p class="text-sm font-bold text-gray-800 truncate">{{ $user->name }}</p>
                            <p class="text-[11px] text-gray-400 truncate">{{ $user->email }}</p>
                        </div>

                        <a href="{{ route('perfil') }}"
                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors group">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center transition-transform duration-200 group-hover:scale-110">
                                <span class="material-symbols-outlined text-[17px] text-emerald-600">person</span>
                            </div>
                            <span class="text-xs font-bold text-gray-700">Meu Perfil</span>
                        </a>

                        <a href="{{ route('configuracoes') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors group">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center transition-transform duration-200 group-hover:scale-110">
                                <span class="material-symbols-outlined text-[17px] text-blue-600">settings</span>
                            </div>
                            <span class="text-xs font-bold text-gray-700">Configurações</span>
                        </a>

                        <div class="border-t border-gray-500 my-1"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-red-50 group transition-colors">
                                <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center transition-transform duration-200 group-hover:scale-110">
                                    <span class="material-symbols-outlined text-[17px] text-red-500">logout</span>
                                </div>
                                <span class="text-xs font-bold text-red-500">Encerrar Sessão</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Conteúdo principal --}}
        <main class="flex-1 min-h-0 w-full min-w-0 max-w-[1600px] mx-auto px-4 lg:px-8 py-6 space-y-5 overflow-y-auto overflow-x-hidden">
            @yield('content')
        </main>

        {{-- Footer discreto --}}
        <footer class="shrink-0 px-4 lg:px-8 py-3 border-t border-gray-500 text-center">
            <p class="text-[11px] text-gray-300 font-medium">FinFamília · Gestão financeira familiar segura</p>
        </footer>
    </div>
</div>

{{-- ============ TOASTS ============ --}}
<div id="toasts" class="fixed top-4 right-4 z-[100] flex flex-col gap-2 w-[min(370px,92vw)]">
    @if(session('status'))
    <div data-toast data-toast-timeout="10000"
        class="toast-in rounded-lg border border-emerald-200 bg-white overflow-hidden"
        style="box-shadow: 0 8px 32px -4px rgba(5,150,105,0.2), 0 4px 12px -2px rgba(15,23,42,0.08);">
        <div class="flex items-start gap-3 p-4">
            <span class="w-9 h-9 rounded-lg grid place-items-center shrink-0"
                style="background: linear-gradient(135deg, #ECFDF5, #D1FAE5);">
                <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            </span>
            <div class="flex-1 min-w-0 pt-0.5">
                <p class="text-[13px] font-extrabold text-gray-900">Tudo certo!</p>
                <p class="text-[12px] text-gray-500 mt-0.5">{{ session('status') }}</p>
            </div>
            <button data-toast-close class="w-7 h-7 grid place-items-center rounded-lg text-gray-300 hover:text-gray-600 hover:bg-slate-100 shrink-0 transition">
                <span class="material-symbols-outlined text-[17px]">close</span>
            </button>
        </div>
        <div class="h-0.5 bg-slate-100"><div data-toast-bar class="h-full w-full bg-emerald-500 origin-left rounded-lg"></div></div>
    </div>
    @endif
    @if($errors->any())
    <div data-toast data-toast-timeout="10000"
        class="toast-in rounded-lg border border-red-200 bg-white overflow-hidden"
        style="box-shadow: 0 8px 32px -4px rgba(239,68,68,0.2), 0 4px 12px -2px rgba(15,23,42,0.08);">
        <div class="flex items-start gap-3 p-4">
            <span class="w-9 h-9 rounded-lg grid place-items-center shrink-0"
                style="background: linear-gradient(135deg, #FEF2F2, #FEE2E2);">
                <span class="material-symbols-outlined text-red-500">error</span>
            </span>
            <div class="flex-1 min-w-0 pt-0.5">
                <p class="text-[13px] font-extrabold text-gray-900">Verifique os campos</p>
                <ul class="mt-1 space-y-0.5 text-[12px] text-gray-500">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
            </div>
            <button data-toast-close class="w-7 h-7 grid place-items-center rounded-lg text-gray-300 hover:text-gray-600 hover:bg-slate-100 shrink-0 transition">
                <span class="material-symbols-outlined text-[17px]">close</span>
            </button>
        </div>
        <div class="h-0.5 bg-slate-100"><div data-toast-bar class="h-full w-full bg-red-500 origin-left rounded-lg"></div></div>
    </div>
    @endif
</div>

<script>
// Toasts
document.querySelectorAll('[data-toast]').forEach(box=>{
  const timeout = parseInt(box.dataset.toastTimeout || '10000', 10);
  const bar = box.querySelector('[data-toast-bar]');
  if(bar){ bar.style.transition = `transform ${timeout}ms linear`; requestAnimationFrame(()=>{ bar.style.transform = 'scaleX(0)'; }); }
  const timer = setTimeout(()=>dismiss(), timeout);
  function dismiss(){ clearTimeout(timer); box.style.transition = 'opacity .3s, transform .3s'; box.style.opacity = '0'; box.style.transform = 'translateX(20px)'; setTimeout(()=>box.remove(), 320); }
  box.querySelector('[data-toast-close]')?.addEventListener('click', dismiss);
});

// Filtros client-side por tokens
document.querySelectorAll('[data-filter-group]').forEach(group=>{
  const key = group.dataset.filterGroup;
  const btns = group.querySelectorAll('[data-filter]');
  btns.forEach(btn=>btn.addEventListener('click',()=>{
    btns.forEach(x=>x.className='filtro-btn h-8 px-4 rounded-lg text-[12px] font-bold border bg-white border-gray-500 text-gray-600 hover:border-gray-500 transition');
    btn.className='filtro-btn h-8 px-4 rounded-lg text-[12px] font-bold border bg-slate-900 text-white border-gray-900';
    const f = btn.dataset.filter;
    document.querySelectorAll('[data-ledger-row]').forEach(r=>{
      let show = true;
      if(f !== 'all') show = (r.dataset[key] ?? ' ').toLowerCase().split(/\s+/).includes(f.toLowerCase());
      r.dataset.hiddenByFilter = show ? '' : '1';
      r.style.display = show ? '' : 'none';
    });
  }));
});

// Animação de entrada para elementos da página
document.querySelectorAll('.kpi-card, .section-card, .hero-card').forEach((el, i) => {
  el.style.animationDelay = `${i * 60}ms`;
  el.classList.add('fade-up');
});

// Dropdown do usuário (abrir, fechar fora, Esc)
const userMenu = document.querySelector('[data-user-menu]');
if (userMenu) {
  const btn = userMenu.querySelector('[data-user-menu-btn]');
  const dd = userMenu.querySelector('[data-user-menu-dropdown]');
  const caret = userMenu.querySelector('[data-user-menu-caret]');

  function open() {
    dd.classList.remove('hidden', 'opacity-0', 'scale-95');
    dd.classList.add('opacity-100', 'scale-100');
    caret.textContent = 'expand_less';
  }
  function close() {
    dd.classList.add('hidden', 'opacity-0', 'scale-95');
    dd.classList.remove('opacity-100', 'scale-100');
    caret.textContent = 'expand_more';
  }

  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    dd.classList.contains('hidden') ? open() : close();
  });
  document.addEventListener('click', (e) => {
    if (!userMenu.contains(e.target)) close();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close();
  });
}
</script>
@stack('scripts')
</body>
</html>
