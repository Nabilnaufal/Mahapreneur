<?php

namespace App\Http\Controllers\Seller;

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
        $conversation = SupportConversation::firstOrCreate([
            'seller_id' => $request->user()->id,
        ]);

        $conversation->load(['seller', 'latestMessage']);
        $conversation->unread_count = $this->unreadCount($conversation, $request->user()->id);

        return $this->ok([
            'conversations' => [SupportConversationResource::make($conversation)->resolve()],
            'unread' => $conversation->unread_count,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $conversation = $this->findConversation($request, $id);
        $userId = $request->user()->id;

        $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->ok([
            'conversation' => $this->payload($conversation, $userId),
            'messages' => $this->messages($conversation),
        ]);
    }

    public function sendMessage(Request $request, int $id)
    {
        $conversation = $this->findConversation($request, $id);
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

    private function findConversation(Request $request, int $id): SupportConversation
    {
        $conversation = SupportConversation::with(['seller', 'latestMessage'])
            ->where('seller_id', $request->user()->id)
            ->find($id);

        if (! $conversation) {
            throw ApiException::notFound('Chat pelayanan tidak ditemukan');
        }

        return $conversation;
    }

    private function payload(SupportConversation $conversation, int $userId): array
    {
        $conversation->loadMissing(['seller', 'latestMessage']);
        $conversation->unread_count = $this->unreadCount($conversation, $userId);

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

    private function unreadCount(SupportConversation $conversation, int $userId): int
    {
        return $conversation->messages()->whereNull('read_at')->where('sender_id', '!=', $userId)->count();
    }
}
