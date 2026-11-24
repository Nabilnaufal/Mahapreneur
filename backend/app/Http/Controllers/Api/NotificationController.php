<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::where('user_id', $request->user()->id);
        $notifications = (clone $query)->latest('created_at')->paginate($this->perPage($request, 20));

        return $this->ok([
            'notifications' => $notifications->items(),
            'unread' => (clone $query)->where('is_read', false)->count(),
            'meta' => $this->meta($notifications),
        ]);
    }

    public function read(Request $request, int $id)
    {
        Notification::where('user_id', $request->user()->id)->whereKey($id)->update(['is_read' => true]);

        return $this->ok(null, 'Notifikasi dibaca');
    }

    public function readAll(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->where('is_read', false)->update(['is_read' => true]);

        return $this->ok(null, 'Semua notifikasi ditandai sudah dibaca');
    }
}
