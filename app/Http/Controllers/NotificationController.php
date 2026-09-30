<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function readAll(): RedirectResponse
    {
        request()->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Notificações marcadas como lidas.');
    }
}
