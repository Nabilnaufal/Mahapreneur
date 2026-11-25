<template>
  <div class="space-y-5">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-extrabold text-strong">Pesan Masuk</h1>
        <p class="mt-1 text-sm text-muted">Balas pertanyaan pembeli dan pantau siapa saja yang menghubungi tokomu.</p>
      </div>
    </header>

    <ChatInbox
      endpoint="/seller/chats"
      :me-id="auth.user?.id ?? 0"
      partner="customer"
      empty-text="Belum ada pembeli yang mengirim pesan."
    />
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SELLER'] });

const auth = useAuthStore();

useSeoMeta({ title: 'Pesan Masuk' });
</script>
