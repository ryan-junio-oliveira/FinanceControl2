@props(['action', 'mes'])
<form method="GET" action="{{ $action }}" class="flex items-center gap-2">
    <label class="text-[12px] font-bold text-gray-500" for="mes">Mês</label>
    <input type="month" name="mes" id="mes" value="{{ $mes }}" onchange="this.form.submit()"
        class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-[13px] font-bold text-gray-700 focus:border-emerald-500 outline-none transition">
</form>