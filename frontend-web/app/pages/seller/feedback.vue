<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-xl font-extrabold text-strong">Beri masukan untuk kami</h1>
      <p class="mt-1 text-sm text-muted">Bantu kami meningkatkan pengalaman seller di Mahapreneur.</p>
    </header>

    <PlatformFeedbackCard />
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SELLER'] });

useSeoMeta({ title: 'Beri Masukan' });
</script>
