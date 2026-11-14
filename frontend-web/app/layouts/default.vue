<template>
  <div class="flex min-h-screen flex-col">
    <AppNavbar />
    <main class="flex-1 pb-24 md:pb-0">
      <slot />
    </main>
    <AppFooter />
    <MobileBottomNav />
    <LoginModal />
  </div>
</template>

<script setup lang="ts"></script>
