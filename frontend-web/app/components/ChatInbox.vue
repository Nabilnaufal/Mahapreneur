<template>
  <div class="grid gap-4 lg:grid-cols-[320px_1fr]">
    <aside class="card overflow-hidden" :class="activeId ? 'hidden lg:block' : ''">
      <div class="border-b border-line p-4">
        <p class="text-sm font-bold text-strong">{{ heading }}</p>
        <p class="mt-0.5 text-xs text-muted">
          {{ conversations.length }} percakapan · {{ unreadTotal }} belum dibaca
        </p>
      </div>

      <div v-if="conversations.length" class="max-h-[560px] divide-y divide-line overflow-y-auto">
        <button
          v-for="conversation in conversations"
          :key="conversation.id"
          class="flex w-full items-start gap-3 p-4 text-left transition hover:bg-surface-muted"
          :class="conversation.id === activeId ? 'bg-surface-muted' : ''"
          @click="select(conversation.id)"
        >
          <span class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-full bg-surface-muted text-xs font-bold text-primary dark:text-tertiary">
            <img v-if="partnerAvatar(conversation)" :src="partnerAvatar(conversation) ?? undefined" :alt="partnerName(conversation)" class="size-full object-cover" />
            <template v-else>{{ partnerInitials(conversation) }}</template>
          </span>

          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
              <p class="truncate text-sm font-bold text-strong">{{ partnerName(conversation) }}</p>
              <span class="shrink-0 text-[10px] text-muted">{{ formatRelative(conversation.lastMessageAt) }}</span>
            </div>
            <p class="mt-0.5 truncate text-xs text-muted">
              {{ conversation.lastMessage?.body ?? 'Belum ada pesan' }}
            </p>
          </div>

          <span
            v-if="conversation.unreadCount"
            class="mt-0.5 grid min-w-5 shrink-0 place-items-center rounded-full bg-tertiary px-1.5 text-[10px] font-bold text-neutral"
          >{{ conversation.unreadCount }}</span>
        </button>
      </div>

      <div v-else class="p-8 text-center text-sm text-muted">{{ emptyText || 'Belum ada chat masuk.' }}</div>
    </aside>

    <div :class="activeId ? '' : 'hidden lg:block'">
      <button v-if="activeId" class="btn btn-ghost mb-2 !px-2 lg:hidden" @click="select(null)">
        <ArrowLeft class="size-4" /> Kembali
      </button>

      <ChatPanel
        v-if="active"
        :conversation-id="active.id"
        :endpoint="endpoint"
        :me-id="meId"
        :title="partnerName(active)"
        :avatar="partnerAvatar(active)"
        :subtitle="partner === 'store' ? 'Penjual toko' : partner === 'support' ? 'Customer Service Mahapreneur' : 'Pembeli'"
        :empty-text="emptyText"
        @sent="refresh()"
      />

      <div v-else class="card grid min-h-[520px] place-items-center p-6 text-center">
        <div>
          <MessageCircle class="mx-auto size-8 text-muted" />
          <p class="mt-3 text-sm font-bold text-strong">Pilih percakapan</p>
          <p class="mt-1 text-xs text-muted">Pesan dari {{ partner === 'store' ? 'toko' : partner === 'support' ? 'customer service' : 'pembeli' }} akan muncul di sini.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ArrowLeft, MessageCircle } from 'lucide-vue-next';
import type { ChatConversation } from '~/types';

const props = defineProps<{
  endpoint: string;
  meId: number;
  partner: 'store' | 'customer' | 'support';
  heading?: string;
  queryKey?: string;
  emptyText?: string;
}>();

const heading = computed(() => props.heading ?? 'Daftar chat');
const queryKey = computed(() => props.queryKey ?? 'conversation');

const api = useApi();
const route = useRoute();
const router = useRouter();

const { data, refresh } = await useAsyncData(
  `chat-inbox-${props.endpoint}`,
  () => api.get<{ conversations: ChatConversation[]; unread: number }>(props.endpoint),
);

const conversations = computed(() => data.value?.conversations ?? []);
const unreadTotal = computed(() => data.value?.unread ?? 0);
const activeId = ref<number | null>(Number(route.query[queryKey.value]) || null);
const active = computed(() => conversations.value.find((conversation) => conversation.id === activeId.value) ?? null);

let timer: ReturnType<typeof setInterval> | undefined;

const partnerName = (conversation: ChatConversation) => {
  if (props.partner === 'support') return 'Customer Service Mahapreneur';
  return props.partner === 'store' ? conversation.store?.name ?? 'Toko' : conversation.customer?.name ?? 'Pembeli';
};

const partnerAvatar = (conversation: ChatConversation) => {
  if (props.partner === 'support') return null;
  return props.partner === 'store' ? conversation.store?.logo ?? null : conversation.customer?.avatar ?? null;
};

const partnerInitials = (conversation: ChatConversation) =>
  partnerName(conversation)
    .split(' ')
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('');

function select(id: number | null) {
  activeId.value = id;
  const query = { ...route.query };
  if (id) query[queryKey.value] = String(id);
  else delete query[queryKey.value];
  router.replace({ query });
}

watch(
  () => route.query[queryKey.value],
  (value) => {
    activeId.value = Number(value) || null;
  },
);

onMounted(() => {
  const first = conversations.value[0];
  if (!activeId.value && first && window.matchMedia('(min-width: 1024px)').matches) {
    select(first.id);
  }

  timer = setInterval(() => {
    if (document.visibilityState === 'visible') refresh();
  }, 5000);
});

onBeforeUnmount(() => {
  if (timer) clearInterval(timer);
});
</script>
