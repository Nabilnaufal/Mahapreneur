<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\SupportConversationResource;
use App\Http\Resources\SupportMessageResource;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Http\Request;

class SupportChatController extends Controller
{
    public function index(Request $request)
    {
        $conversations = SupportConversation::query()
            ->with(['customer', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($query) => $query
                ->whereNull('read_at')
                ->whereHas('sender', fn ($sender) => $sender->where('role', 'CUSTOMER'))
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        return $this->ok([
            'conversations' => $conversations
                ->map(fn (SupportConversation $conversation) => SupportConversationResource::make($conversation)->resolve())
                ->all(),
            'unread' => (int) $conversations->sum('unread_count'),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $conversation = $this->findConversation($id);

        $conversation->messages()
            ->whereNull('read_at')
            ->whereHas('sender', fn ($sender) => $sender->where('role', 'CUSTOMER'))
            ->update(['read_at' => now()]);

        return $this->ok([
            'conversation' => $this->payload($conversation),
            'messages' => $this->messages($conversation),
        ]);
    }

    public function sendMessage(Request $request, int $id)
    {
        $conversation = $this->findConversation($id);
        $body = trim($request->validate(['body' => ['required', 'string', 'max:1000']])['body']);
        if ($body === '') {
            throw ApiException::badRequest('Pesan tidak boleh kosong');
        }

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $body,
        ]);
        $conversation->update(['last_message_at' => $message->created_at]);

        return $this->ok([
            'message' => SupportMessageResource::make($message->load('sender:id,name'))->resolve(),
        ], 'Pesan terkirim');
    }

    private function findConversation(int $id): SupportConversation
    {
        $conversation = SupportConversation::with(['customer', 'latestMessage'])->find($id);
        if (! $conversation) {
            throw ApiException::notFound('Chat pelayanan tidak ditemukan');
        }

        return $conversation;
    }

    private function payload(SupportConversation $conversation): array
    {
        $conversation->loadMissing(['customer', 'latestMessage']);

        return SupportConversationResource::make($conversation)->resolve();
    }

    private function messages(SupportConversation $conversation): array
    {
        return $conversation->messages()
            ->with('sender:id,name')
            ->orderBy('id')
            ->get()
            ->map(fn (SupportMessage $message) => SupportMessageResource::make($message)->resolve())
            ->all();
    }
}
