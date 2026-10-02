<?php

namespace App\Http\Controllers;

use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(BillingService $billing): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return view('pages.landing', [
            'plans' => config('billing.plans'),
            'billingEnabled' => $billing->enabled(),
        ]);
    }
}
