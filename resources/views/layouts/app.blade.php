<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Visão Geral') · Prumo</title>
    <meta name="description" content="Prumo — gestão financeira familiar: dashboard, despesas, receitas, investimentos, cartões, contas e grupo familiar.">
    <meta name="theme-color" content="#059669">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Prumo">
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
        style="background: linear-gradient(180deg, #ffffff 0%, #fafcfb 100%); border-right: 1px solid #e2e8f0;">

        {{-- Topo da sidebar: logo + fechar --}}
        <div class="px-5 pt-5 pb-4 flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                <span class="sidebar-brand-icon group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">savings</span>
                </span>
                <span class="text-[19px] font-extrabold tracking-tight">Prumo</span>
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
                <p class="text-[11px] text-gray-500">{{ $family?->users()->count() ?? 0 }} membro(s) na conta</p>
            </div>
            <span class="material-symbols-outlined text-emerald-500 text-[18px]">chevron_right</span>
        </a>

        {{-- Nav principal --}}
        <nav class="flex-1 overflow-y-auto px-3 pb-3">
            <p class="nav-section-label">Menu principal</p>
            @php
                $nav = [
                    ['route'=>'dashboard','label'=>'Dashboard','icon'=>'dashboard'],
                    ['route'=>'despesas','label'=>'Despesas','icon'=>'trending_down','color'=>'danger'],
                    ['route'=>'receitas','label'=>'Receitas','icon'=>'trending_up','color'=>'success'],
                    ['route'=>'investimentos','label'=>'Investimentos','icon'=>'savings','gestor'=>true,'color'=>'cyan'],
                    ['route'=>'cartoes','label'=>'Cartões','icon'=>'credit_card','color'=>'orange'],
                    ['route'=>'contas','label'=>'Contas','icon'=>'account_balance','color'=>'blue'],
                    ['route'=>'categorias','label'=>'Categorias','icon'=>'category','color'=>'violet'],
                    ['route'=>'familia','label'=>'Membros','icon'=>'group','color'=>'rose'],
                    ['route'=>'perfil','label'=>'Meu Perfil','icon'=>'person'],
                    ['route'=>'plans','label'=>'Plano','icon'=>'workspace_premium','color'=>'amber'],
                ];
            @endphp
            @foreach($nav as $item)
                @continue(!empty($item['gestor']) && !$isGestor)
                @php $active = request()->routeIs($item['route'], $item['route'].'.*') || ($__env->hasSection('nav-active') && trim($__env->yieldContent('nav-active')) === $item['route']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="nav-item mb-0.5 {{ $active ? 'active' : '' }}{{ $active && !empty($item['color']) ? ' nav-active-'.$item['color'] : '' }}">
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
                if ($user->role === 'admin') {
                    $sys[] = ['route'=>'admin.logs','label'=>'Logs de Ação','icon'=>'receipt_long'];
                }
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
        <header data-app-header class="shrink-0 z-20 glass border-b border-slate-200/70 shadow-[0_1px_0_0_rgba(226,232,240,0.7)]">
            <div class="w-full min-w-0 max-w-[1600px] mx-auto px-4 lg:px-8 py-3 flex items-center gap-3">

                {{-- Hambúrguer mobile --}}
                <button class="lg:hidden w-10 h-10 grid place-items-center rounded-lg border border-slate-200 bg-white shadow-sm hover:shadow-md transition"
                    onclick="document.getElementById('sidebar').classList.remove('-translate-x-full');document.getElementById('backdrop').classList.remove('hidden')">
                    <span class="material-symbols-outlined text-gray-600">menu</span>
                </button>

                {{-- Breadcrumb --}}
                <nav class="hidden md:flex items-center gap-1.5 text-[13px] font-medium">
                    <span class="text-emerald-700 font-extrabold tracking-tight">Prumo</span>
                    <span class="material-symbols-outlined text-[15px] text-gray-300">chevron_right</span>
                    <span class="text-gray-600 font-semibold">@yield('breadcrumb', 'Visão Geral / Dashboard')</span>
                </nav>

                <div class="flex-1"></div>

                {{-- Sino de notificações (abre a sidebar lateral) --}}
                @php $naoLidas = $user->unreadNotifications()->count(); @endphp
                <button type="button" data-notif-open title="Notificações"
                    class="relative w-10 h-10 grid place-items-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:text-slate-800 hover:border-slate-300 transition">
                    <span class="material-symbols-outlined text-[20px]">notifications</span>
                    @if($naoLidas > 0)
                    <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-extrabold grid place-items-center num">{{ $naoLidas > 9 ? '9+' : $naoLidas }}</span>
                    @endif
                </button>

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
                        class="hidden absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-slate-200 py-2 overflow-hidden z-50 transition-all duration-200 origin-top-right scale-95 opacity-0">
                        <div class="px-4 py-3 border-b border-slate-200 mb-1 bg-slate-50/70">
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

                        <div class="border-t border-slate-200 my-1"></div>

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
        <footer class="shrink-0 border-t border-slate-200/80 bg-white/60 backdrop-blur">
            <div class="max-w-[1600px] mx-auto px-4 lg:px-8 py-3.5 flex flex-col sm:flex-row items-center justify-between gap-2.5">
                <div class="flex items-center gap-2 text-[11px] text-gray-400 font-medium">
                    <span class="w-6 h-6 rounded-md grid place-items-center text-white shrink-0"
                        style="background: linear-gradient(135deg, #059669, #064E3B);">
                        <span class="material-symbols-outlined text-[13px]">savings</span>
                    </span>
                    <span>© {{ date('Y') }} <b class="text-gray-500 font-bold">Prumo</b> · Gestão financeira familiar</span>
                </div>
                <nav class="flex items-center gap-4 text-[11px] font-semibold">
                    <a href="{{ route('termos') }}" class="text-gray-400 hover:text-emerald-600 transition">Termos de Uso</a>
                    <span class="text-slate-200 select-none">•</span>
                    <a href="{{ route('privacidade') }}" class="text-gray-400 hover:text-emerald-600 transition">Política de Privacidade (LGPD)</a>
                </nav>
            </div>
        </footer>
    </div>
</div>

{{-- ============ SIDEBAR DE NOTIFICAÇÕES (lateral direita) ============ --}}
<div data-notif-backdrop class="hidden fixed inset-0 z-40"
    style="background: rgba(15,23,42,.35); backdrop-filter: blur(4px);"></div>
<aside data-notif-panel
    class="fixed z-50 inset-y-0 right-0 w-[min(400px,94vw)] bg-white shadow-2xl border-l border-slate-200 flex flex-col translate-x-full transition-transform duration-300 ease-in-out"
    aria-label="Notificações">
    <div class="px-5 py-4 flex items-center gap-3 border-b border-slate-100">
        <span class="w-9 h-9 rounded-xl grid place-items-center shrink-0"
            style="background: linear-gradient(135deg, #ECFDF5, #D1FAE5);">
            <span class="material-symbols-outlined text-emerald-600 text-[20px]">notifications</span>
        </span>
        <div class="flex-1 min-w-0">
            <p class="text-[15px] font-extrabold text-gray-900">Notificações</p>
            <p class="text-[11px] text-gray-400 font-medium">{{ $naoLidas > 0 ? $naoLidas.' não lida(s)' : 'Tudo em dia' }}</p>
        </div>
        @if($naoLidas > 0)
        <form method="POST" action="{{ route('notificacoes.lidas') }}" class="inline shrink-0">
            @csrf
            <button class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700">Marcar todas como lidas</button>
        </form>
        @endif
        <button type="button" data-notif-close title="Fechar"
            class="w-9 h-9 grid place-items-center rounded-xl text-gray-400 hover:text-gray-700 hover:bg-slate-100 transition shrink-0">
            <span class="material-symbols-outlined text-[20px]">close</span>
        </button>
    </div>
    <div class="flex-1 overflow-y-auto py-2">
        @forelse($user->notifications()->latest()->get() as $n)
        <form method="POST" action="{{ route('notificacoes.ler', $n) }}" class="border-b border-slate-50">
            @csrf
            <button type="submit"
            class="w-full text-left flex items-start gap-3 px-5 py-3.5 hover:bg-slate-50 transition {{ $n->read_at ? '' : 'bg-emerald-50/40' }}">
            <span class="w-10 h-10 rounded-xl {{ $n->read_at ? 'bg-slate-100' : 'bg-emerald-100' }} grid place-items-center shrink-0">
                <span class="material-symbols-outlined text-[19px] {{ $n->read_at ? 'text-slate-400' : 'text-emerald-600' }}">{{ $n->data['icon'] ?? 'notifications' }}</span>
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-[13px] leading-snug text-gray-800 {{ $n->read_at ? 'font-medium' : 'font-extrabold' }}">{{ $n->data['title'] ?? 'Aviso' }}</span>
                <span class="block text-[12px] text-gray-500 mt-0.5 leading-snug">{{ $n->data['body'] ?? '' }}</span>
                <span class="block text-[10px] text-gray-300 mt-1 num">{{ $n->created_at->diffForHumans() }}</span>
            </span>
            @if(! $n->read_at)<span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0 mt-1.5"></span>@endif
        </button>
        </form>
        @empty
        <div class="text-center px-6 py-14 text-gray-400">
            <span class="material-symbols-outlined text-[48px] text-gray-300">notifications_off</span>
            <p class="text-[13px] font-bold mt-3 text-gray-500">Nenhuma notificação por aqui.</p>
            <p class="text-[12px] mt-1">Avisos de faturas e contas próximas do vencimento aparecem aqui.</p>
        </div>
        @endforelse
    </div>
</aside>

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
    btns.forEach(x=>x.className='filtro-btn h-8 px-4 rounded-lg text-[12px] font-bold border bg-white border-slate-200 text-gray-600 hover:border-slate-300 transition');
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

// Sidebar de notificações (lateral direita: abrir, fechar fora, Esc)
const notifPanel = document.querySelector('[data-notif-panel]');
const notifBackdrop = document.querySelector('[data-notif-backdrop]');
function setNotif(open) {
  if (!notifPanel) return;
  notifPanel.classList.toggle('translate-x-full', !open);
  notifBackdrop?.classList.toggle('hidden', !open);
  document.body.style.overflow = open ? 'hidden' : '';
}
document.querySelector('[data-notif-open]')?.addEventListener('click', (e) => {
  e.stopPropagation();
  setNotif(true);
});
notifPanel?.querySelector('[data-notif-close]')?.addEventListener('click', () => setNotif(false));
notifBackdrop?.addEventListener('click', () => setNotif(false));
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') setNotif(false);
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
