<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\GlobalNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = GlobalNotification::latest()->paginate(12);

        return view('super-admin.notifications.index', compact('notifications'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:announcement,expiry_alert,maintenance_alert'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'channels' => ['nullable', 'array'],
        ]);

        GlobalNotification::create([
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'channels' => $data['channels'] ?? ['in_app'],
            'is_sent' => true,
            'sent_at' => now(),
        ]);

        return back()->with('success', 'Global notification sent successfully.');
    }
}
