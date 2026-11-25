<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-xl font-extrabold text-strong">Customer Service</h1>
      <p class="mt-1 text-sm text-muted">Bantu customer menyelesaikan pertanyaan dan kendala mereka di Mahapreneur.</p>
    </header>

    <ChatInbox
      endpoint="/admin/support-chats"
      :me-id="auth.user?.id ?? 0"
      partner="customer"
      subtitle="Customer atau seller"
      :accent-header="true"
      heading="Pesan pelayanan"
      empty-text="Belum ada customer yang menghubungi pelayanan."
    />
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SUPERADMIN'] });

const auth = useAuthStore();

useSeoMeta({ title: 'Customer Service' });
</script>
