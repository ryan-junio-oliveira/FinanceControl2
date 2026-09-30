<?php

namespace App\Http\Controllers;

use App\Support\MarketData;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MercadoController extends Controller
{
    public function index(): View
    {
        return view('pages.mercado');
    }

    /** Payload completo em JSON (a view busca via fetch com skeleton). */
    public function dados(): JsonResponse
    {
        return response()->json(MarketData::mercado());
    }
}
