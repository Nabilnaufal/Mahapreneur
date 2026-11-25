<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-2xl font-extrabold text-strong">Pesan</h1>
      <p class="mt-1 text-sm text-muted">Tanya penjual tentang produk, atau hubungi customer service jika butuh bantuan.</p>
    </header>

    <ChatInbox
      endpoint="/customer/chats"
      :me-id="auth.user?.id ?? 0"
      partner="store"
      heading="Daftar chat"
      empty-text="Belum ada pesan. Mulai dengan menyapa penjual."
    />

    <ChatInbox
      endpoint="/customer/support-chats"
      :me-id="auth.user?.id ?? 0"
      partner="support"
      heading="Chat pelayanan"
      query-key="supportConversation"
      empty-text="Belum ada percakapan dengan customer service."
    />
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'account', requiresAuth: true, roles: ['CUSTOMER'] });

const auth = useAuthStore();

useSeoMeta({ title: 'Pesan' });
</script>
