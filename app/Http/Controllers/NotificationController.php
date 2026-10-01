<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function readAll(): RedirectResponse
    {
        request()->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Notificações marcadas como lidas.');
    }

    /** Marca uma notificação como lida e leva ao destino dela. */
    public function read(DatabaseNotification $notification): RedirectResponse
    {
        $user = request()->user();
        abort_if($notification->notifiable_type !== $user::class || $notification->notifiable_id !== $user->id, 404);
        $notification->markAsRead();

        return redirect()->to($notification->data['url'] ?? route('dashboard'));
    }
}
