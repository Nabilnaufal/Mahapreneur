<template>
  <div>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
    <ToastStack />
  </div>
</template>

<script setup lang="ts">
const ui = useUiStore();
const auth = useAuthStore();

onMounted(async () => {
  ui.initTheme();
  if (!auth.loaded) await auth.fetchUser();
  if (auth.isCustomer) useCartStore().fetch();
});

useHead({
  titleTemplate: (title) => (title ? `${title} · Mahapreneur` : 'Mahapreneur — Marketplace Bisnis Mahasiswa'),
});
</script>