<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Notification;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user:id,name,email,role')
            ->when($request->query('entity'), fn ($query, string $entity) => $query->where('entity', $entity))
            ->when($request->query('q'), fn ($query, string $keyword) => $query->where('description', 'like', "%{$keyword}%"))
            ->latest('created_at')
            ->paginate($this->perPage($request, 20));

        return $this->ok([
            'logs' => $logs->items(),
            'meta' => $this->meta($logs),
        ]);
    }

    public function notifications(Request $request)
    {
        return $this->ok([
            'notifications' => Notification::where('user_id', $request->user()->id)
                ->latest('created_at')
                ->take(30)
                ->get(),
        ]);
    }
}
