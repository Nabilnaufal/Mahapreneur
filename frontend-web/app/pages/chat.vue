<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-2xl font-extrabold text-strong">Pesan</h1>
      <p class="mt-1 text-sm text-muted">Tanya langsung penjual tentang produk, stok, atau pengantaran.</p>
    </header>

    <ChatInbox
      endpoint="/customer/chats"
      :me-id="auth.user?.id ?? 0"
      partner="store"
      empty-text="Belum ada pesan. Mulai dengan menyapa penjual."
    />
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'account', requiresAuth: true, roles: ['CUSTOMER'] });

const auth = useAuthStore();

useSeoMeta({ title: 'Pesan' });
</script>
