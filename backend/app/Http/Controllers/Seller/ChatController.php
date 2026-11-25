<?php

namespace App\Http\Controllers\Seller;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Store;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $storeId = $this->storeId($request);
        if (! $storeId) {
            return $this->ok(['conversations' => [], 'unread' => 0]);
        }

        $conversations = Conversation::query()
            ->where('store_id', $storeId)
            ->with(['customer', 'latestMessage'])
            ->withCount(['messages as unread_count' => function ($query) use ($request) {
                $query->whereNull('read_at')->where('sender_id', '!=', $request->user()->id);
            }])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        return $this->ok([
            'conversations' => $conversations
                ->map(fn (Conversation $conversation) => ConversationResource::make($conversation)->resolve())
                ->all(),
            'unread' => (int) $conversations->sum('unread_count'),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $conversation = $this->findConversation($request, $id);

        $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->orderBy('id')
            ->get()
            ->map(fn (Message $message) => ChatMessageResource::make($message)->resolve())
            ->all();

        return $this->ok([
            'conversation' => $this->payload($conversation, $request->user()->id),
            'messages' => $messages,
        ]);
    }

    public function sendMessage(Request $request, int $id)
    {
        $conversation = $this->findConversation($request, $id);
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);

        $body = trim($data['body']);
        if ($body === '') {
            throw ApiException::badRequest('Pesan tidak boleh kosong');
        }

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $body,
        ]);
        $conversation->update(['last_message_at' => $message->created_at]);

        return $this->ok(
            ['message' => ChatMessageResource::make($message->load('sender:id,name'))->resolve()],
            'Pesan terkirim',
        );
    }

    private function storeId(Request $request)
    {
        return Store::where('user_id', $request->user()->id)->value('id');
    }

    private function findConversation(Request $request, int $id)
    {
        $conversation = Conversation::with(['store', 'customer'])
            ->where('store_id', $this->storeId($request))
            ->find($id);

        if (! $conversation) {
            throw ApiException::notFound('Chat tidak ditemukan');
        }

        return $conversation;
    }

    private function payload(Conversation $conversation, int $userId)
    {
        $conversation->loadMissing(['store', 'customer', 'latestMessage']);
        $conversation->unread_count = $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $userId)
            ->count();

        return ConversationResource::make($conversation)->resolve();
    }
}
