<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Support\AuthToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_seller_can_exchange_messages(): void
    {
        $seller = $this->makeUser('seller-chat@example.test', 'SELLER');
        $customer = $this->makeUser('customer-chat@example.test', 'CUSTOMER');

        $store = Store::create([
            'user_id' => $seller->id,
            'name' => 'Toko Chat',
            'slug' => 'toko-chat',
            'status' => 'ACTIVE',
        ]);

        $conversationId = $this->withToken(AuthToken::issue($customer))
            ->postJson('/api/customer/chats', ['storeId' => $store->id])
            ->assertOk()
            ->json('data.conversation.id');

        $this->withToken(AuthToken::issue($customer))
            ->postJson("/api/customer/chats/{$conversationId}/messages", ['body' => 'Apakah stok masih ada?'])
            ->assertOk()
            ->assertJsonPath('data.message.body', 'Apakah stok masih ada?');

        $this->withToken(AuthToken::issue($seller))
            ->getJson('/api/seller/chats')
            ->assertOk()
            ->assertJsonPath('data.unread', 1)
            ->assertJsonPath('data.conversations.0.lastMessage.body', 'Apakah stok masih ada?');

        $this->withToken(AuthToken::issue($seller))
            ->getJson("/api/seller/chats/{$conversationId}")
            ->assertOk()
            ->assertJsonCount(1, 'data.messages')
            ->assertJsonPath('data.conversation.unreadCount', 0);

        $this->withToken(AuthToken::issue($seller))
            ->postJson("/api/seller/chats/{$conversationId}/messages", ['body' => 'Masih, silakan dipesan.'])
            ->assertOk();

        $this->withToken(AuthToken::issue($customer))
            ->getJson('/api/customer/chats')
            ->assertOk()
            ->assertJsonPath('data.unread', 1);

        $this->withToken(AuthToken::issue($customer))
            ->getJson("/api/customer/chats/{$conversationId}")
            ->assertOk()
            ->assertJsonCount(2, 'data.messages')
            ->assertJsonPath('data.conversation.unreadCount', 0)
            ->assertJsonPath('data.messages.1.body', 'Masih, silakan dipesan.');
    }

    public function test_customer_cannot_open_another_customers_conversation(): void
    {
        $seller = $this->makeUser('seller-chat-2@example.test', 'SELLER');
        $owner = $this->makeUser('owner-chat@example.test', 'CUSTOMER');
        $other = $this->makeUser('other-chat@example.test', 'CUSTOMER');

        $store = Store::create([
            'user_id' => $seller->id,
            'name' => 'Toko Chat 2',
            'slug' => 'toko-chat-2',
            'status' => 'ACTIVE',
        ]);

        $conversationId = $this->withToken(AuthToken::issue($owner))
            ->postJson('/api/customer/chats', ['storeId' => $store->id])
            ->json('data.conversation.id');

        $this->withToken(AuthToken::issue($other))
            ->getJson("/api/customer/chats/{$conversationId}")
            ->assertNotFound();
    }

    public function test_seller_can_open_chat_from_an_order(): void
    {
        $seller = $this->makeUser('seller-chat-3@example.test', 'SELLER');
        $customer = $this->makeUser('customer-chat-3@example.test', 'CUSTOMER');
        $stranger = $this->makeUser('stranger-chat-3@example.test', 'CUSTOMER');

        $store = Store::create([
            'user_id' => $seller->id,
            'name' => 'Toko Chat 3',
            'slug' => 'toko-chat-3',
            'status' => 'ACTIVE',
        ]);

        Order::create([
            'order_number' => 'MP-TEST-CHAT-1',
            'customer_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'CONFIRMED',
            'payment_status' => 'UNPAID_COD',
            'payment_method' => 'COD',
            'subtotal' => 18000,
            'total' => 18000,
            'recipient_name' => 'Pembeli Chat',
            'recipient_phone' => '081234567890',
            'address' => 'Gedung A',
        ]);

        $this->withToken(AuthToken::issue($seller))
            ->postJson('/api/seller/chats', ['customerId' => $customer->id])
            ->assertOk()
            ->assertJsonPath('data.conversation.customerId', $customer->id);

        // Seller tidak boleh membuka chat dengan orang yang tidak pernah memesan.
        $this->withToken(AuthToken::issue($seller))
            ->postJson('/api/seller/chats', ['customerId' => $stranger->id])
            ->assertUnprocessable();
    }

    private function makeUser(string $email, string $role)
    {
        return User::create([
            'name' => 'Pengguna '.$role,
            'email' => $email,
            'password' => 'Rahasia123',
            'role' => $role,
            'email_verified_at' => now(),
        ]);
    }
}
