<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications()->paginate(15);
        return view('notifications.index', compact('notifications'));
    }

    public function read($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect()->back()->with('status', 'Notification marquée comme lue.');
    }

    public function readAll()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return redirect()->back()->with('status', 'Toutes les notifications ont été marquées comme lues.');
    }
}
