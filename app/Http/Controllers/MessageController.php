<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $messages = Message::where('receiver_id', $user->id)
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('messages.index', compact('messages'));
    }

    public function sent()
    {
        $user = Auth::user();
        
        $messages = Message::where('sender_id', $user->id)
            ->with('receiver')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('messages.sent', compact('messages'));
    }

    public function create()
    {
        $user = Auth::user();
        $recipients = collect();

        // Admin/manager pick one specific recipient. Coach messages go automatically
        // to every admin and manager at once (see store()), so no picker is needed.
        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            $recipients = User::whereHas('roles', function($q) {
                $q->whereIn('name', ['coach', 'admin', 'manager']);
            })->where('id', '!=', $user->id)->get();
        }

        return view('messages.create', compact('recipients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $sender = Auth::user();

        if ($sender->hasRole('coach')) {
            // A coach's message is always visible to every admin and manager at once.
            $recipients = User::whereHas('roles', function($q) {
                $q->whereIn('name', ['admin', 'manager']);
            })->get();
        } else {
            $request->validate(['receiver_id' => 'required|exists:users,id']);
            $recipients = User::where('id', $request->receiver_id)->get();
        }

        foreach ($recipients as $recipient) {
            Message::create([
                'sender_id' => $sender->id,
                'receiver_id' => $recipient->id,
                'subject' => $request->subject,
                'body' => $request->body,
            ]);
        }

        return redirect()->route('messages.index')->with('status', 'Message envoyé avec succès.');
    }

    public function show(Message $message)
    {
        // Check authorization
        if ($message->sender_id !== Auth::id() && $message->receiver_id !== Auth::id()) {
            abort(403);
        }

        if ($message->receiver_id === Auth::id() && is_null($message->read_at)) {
            $message->update(['read_at' => now()]);
        }

        return view('messages.show', compact('message'));
    }
}
