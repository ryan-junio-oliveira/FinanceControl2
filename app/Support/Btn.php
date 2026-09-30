<?php

namespace App\Support;

/**
 * Fonte única das classes dos botões do sistema (x-btn-link, x-btn-submit, x-btn-action).
 *
 * Convenções de cor:
 * - primary: ações "Novo …" e submits neutros (emerald)
 * - danger: submits e ações de despesa / exclusões (red)
 * - success: submits de receita (green)
 * - cyan: submits de investimentos
 * - orange: submits de cartões
 * - blue: submits de contas · violet: categorias · rose: pessoas
 * - dark: ações administrativas secundárias
 * - ghost: "Voltar" / "Cancelar" e ações neutras
 * - soft-red: ações destrutivas pequenas (revogar, remover)
 */
final class Btn
{
    public const COLORS = ['primary', 'danger', 'success', 'blue', 'cyan', 'orange', 'violet', 'rose', 'dark', 'ghost', 'soft-red'];

    public const SIZES = ['md', 'sm'];

    public const ICON_COLORS = ['blue', 'red', 'green', 'gray'];

    public static function classes(string $color = 'primary', string $size = 'md', bool $iconOnly = false): string
    {
        if (! in_array($color, self::COLORS, true)) {
            $color = 'primary';
        }

        if ($iconOnly) {
            $tint = match ($color) {
                'danger', 'soft-red' => 'text-red-400 hover:bg-red-50 hover:text-red-600',
                'success' => 'text-emerald-500 hover:bg-emerald-50 hover:text-emerald-700',
                'dark' => 'text-gray-500 hover:bg-slate-100 hover:text-gray-800',
                default => 'text-blue-500 hover:bg-blue-50 hover:text-blue-700',
            };

            return "w-8 h-8 rounded-lg inline-grid place-items-center transition shrink-0 {$tint}";
        }

        $base = 'inline-flex items-center justify-center gap-2 font-bold whitespace-nowrap transition-all select-none cursor-pointer';

        $sizeClass = $size === 'sm'
            ? 'h-9 px-4 text-[12px] rounded-lg'
            : 'h-11 px-5 text-[13px] rounded-xl';

        $colorClass = match ($color) {
            'danger' => 'bg-gradient-to-br from-red-600 to-red-700 text-white shadow-[0_4px_12px_rgba(220,38,38,0.3)] hover:from-red-700 hover:to-red-800 hover:-translate-y-px',
            'success' => 'bg-gradient-to-br from-green-600 to-green-700 text-white shadow-[0_4px_12px_rgba(22,163,74,0.3)] hover:from-green-700 hover:to-green-800 hover:-translate-y-px',
            'cyan' => 'bg-gradient-to-br from-cyan-600 to-cyan-700 text-white shadow-[0_4px_12px_rgba(8,145,178,0.3)] hover:from-cyan-700 hover:to-cyan-800 hover:-translate-y-px',
            'blue' => 'bg-gradient-to-br from-blue-600 to-blue-700 text-white shadow-[0_4px_12px_rgba(37,99,235,0.3)] hover:from-blue-700 hover:to-blue-800 hover:-translate-y-px',
            'orange' => 'bg-gradient-to-br from-orange-500 to-orange-700 text-white shadow-[0_4px_12px_rgba(249,115,22,0.3)] hover:from-orange-600 hover:to-orange-800 hover:-translate-y-px',
            'violet' => 'bg-gradient-to-br from-violet-600 to-violet-700 text-white shadow-[0_4px_12px_rgba(124,58,237,0.3)] hover:from-violet-700 hover:to-violet-800 hover:-translate-y-px',
            'rose' => 'bg-gradient-to-br from-rose-500 to-rose-700 text-white shadow-[0_4px_12px_rgba(244,63,94,0.3)] hover:from-rose-600 hover:to-rose-800 hover:-translate-y-px',
            'dark' => 'bg-slate-900 text-white shadow-[0_4px_12px_rgba(15,23,42,0.25)] hover:bg-slate-800 hover:-translate-y-px',
            'ghost' => 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300 hover:text-slate-900',
            'soft-red' => 'bg-red-50 border border-red-100 text-red-500 hover:bg-red-100 hover:text-red-700 hover:border-red-200',
            default => 'bg-gradient-to-br from-emerald-600 to-emerald-700 text-white shadow-[0_4px_12px_rgba(5,150,105,0.3)] hover:from-emerald-700 hover:to-emerald-800 hover:-translate-y-px',
        };

        return "{$base} {$sizeClass} {$colorClass}";
    }

    public static function iconSize(string $size = 'md', bool $iconOnly = false): string
    {
        if ($iconOnly) {
            return 'text-[19px]';
        }

        return $size === 'sm' ? 'text-[15px]' : 'text-[17px]';
    }
}
