<?php

namespace App\Http\Controllers;

use App\Support\Dashboard;
use App\Support\Fin;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $mes = Fin::month();
        $dados = Dashboard::data($mes);

        return view('pages.dashboard', $dados);
    }
}
