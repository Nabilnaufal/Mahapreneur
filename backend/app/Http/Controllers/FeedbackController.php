<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlatformFeedbackResource;
use App\Models\PlatformFeedback;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $feedback = PlatformFeedback::create([
            'user_id' => $request->user()->id,
            'rating' => $data['rating'],
            'comment' => trim($data['comment']),
        ]);

        Activity::log(
            $request->user()->id,
            'PLATFORM_FEEDBACK_CREATED',
            'PlatformFeedback',
            $feedback->id,
            "Memberi masukan platform dengan rating {$feedback->rating}/5",
        );

        return $this->ok(['feedback' => PlatformFeedbackResource::make($feedback->load('user'))], 'Terima kasih atas masukanmu', 201);
    }

    public function index(Request $request)
    {
        $feedback = PlatformFeedback::with('user:id,name,email,role')
            ->when($request->query('rating'), fn ($query, string $rating) => $query->where('rating', (int) $rating))
            ->latest('created_at')
            ->paginate($this->perPage($request, 20));

        $distribution = PlatformFeedback::query()
            ->select('rating', DB::raw('COUNT(*) as total'))
            ->groupBy('rating')
            ->pluck('total', 'rating');

        return $this->ok([
            'summary' => [
                'total' => PlatformFeedback::count(),
                'average' => round((float) (PlatformFeedback::avg('rating') ?? 0), 1),
                'distribution' => collect(range(1, 5))->mapWithKeys(fn (int $rating) => [$rating => (int) ($distribution[$rating] ?? 0)]),
            ],
            'feedback' => PlatformFeedbackResource::collection($feedback),
            'meta' => $this->meta($feedback),
        ]);
    }
}
