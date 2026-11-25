<template>
  <section class="card flex min-h-[520px] flex-col overflow-hidden">
    <header class="flex items-center gap-3 border-b border-line p-4" :class="accentHeader ? 'border-tertiary bg-tertiary' : ''">
      <span class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-full text-xs font-bold" :class="accentHeader ? 'bg-neutral text-tertiary' : 'bg-surface-muted text-primary dark:text-tertiary'">
        <img v-if="avatar" :src="avatar" :alt="title" class="size-full object-cover" />
        <template v-else>{{ initials }}</template>
      </span>
      <div class="min-w-0">
        <p class="truncate text-sm font-bold" :class="accentHeader ? 'text-neutral' : 'text-strong'">{{ title }}</p>
        <p class="truncate text-xs" :class="accentHeader ? 'text-neutral/70' : 'text-muted'">{{ subtitle || 'Pesan akan diperbarui otomatis.' }}</p>
      </div>
    </header>

    <div ref="scroller" class="flex-1 space-y-3 overflow-y-auto bg-surface-muted/40 p-4">
      <p v-if="loading && !messages.length" class="py-10 text-center text-xs text-muted">Memuat pesan…</p>
      <div v-else-if="!messages.length" class="grid h-full place-items-center px-6 text-center text-sm text-muted">
        {{ emptyText || 'Belum ada pesan. Mulai percakapan dengan menyapa.' }}
      </div>

      <div
        v-for="message in messages"
        :key="message.id"
        class="flex"
        :class="message.senderId === meId ? 'justify-end' : 'justify-start'"
      >
        <div
          class="max-w-[80%] rounded-2xl px-3.5 py-2 text-sm leading-relaxed"
          :class="message.senderId === meId
            ? 'rounded-br-md bg-primary text-white'
            : 'rounded-bl-md border border-line bg-surface text-strong'"
        >
          <p class="whitespace-pre-line break-words">{{ message.body }}</p>
          <p class="mt-1 text-[10px]" :class="message.senderId === meId ? 'text-white/60' : 'text-muted'">
            {{ formatTime(message.createdAt) }}
          </p>
        </div>
      </div>
    </div>

    <form class="flex items-end gap-2 border-t border-line p-3" @submit.prevent="send">
      <textarea
        v-model="draft"
        rows="1"
        maxlength="1000"
        placeholder="Tulis pesan…"
        class="input max-h-32 min-h-10 flex-1 resize-none"
        @keydown.enter.exact.prevent="send"
      />
      <button class="btn btn-primary !px-3" :disabled="sending || !draft.trim()" aria-label="Kirim pesan">
        <Send class="size-4" />
      </button>
    </form>
  </section>
</template>

<script setup lang="ts">
import { Send } from 'lucide-vue-next';
import type { ChatMessage } from '~/types';

const props = defineProps<{
  conversationId: number;
  endpoint: string;
  meId: number;
  title: string;
  subtitle?: string;
  avatar?: string | null;
  emptyText?: string;
  accentHeader?: boolean;
}>();

const emit = defineEmits<{ sent: [] }>();

const api = useApi();
const ui = useUiStore();

const messages = ref<ChatMessage[]>([]);
const loading = ref(false);
const sending = ref(false);
const draft = ref('');
const scroller = ref<HTMLElement | null>(null);
let timer: ReturnType<typeof setInterval> | undefined;
let lastCount = 0;

const initials = computed(() =>
  props.title
    .split(' ')
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join(''),
);

const formatTime = (value: string) => {
  return new Date(value).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};

async function scrollToBottom() {
  await nextTick();
  if (scroller.value) scroller.value.scrollTop = scroller.value.scrollHeight;
}

async function load(silent = false) {
  if (!silent) loading.value = true;
  try {
    const data = await api.get<{ messages: ChatMessage[] }>(`${props.endpoint}/${props.conversationId}`);
    messages.value = data.messages;
    if (data.messages.length !== lastCount) {
      lastCount = data.messages.length;
      await scrollToBottom();
    }
  } catch (error) {
    if (!silent) ui.toast(apiError(error), 'error');
  } finally {
    loading.value = false;
  }
}

async function send() {
  const body = draft.value.trim();
  if (!body || sending.value) return;

  sending.value = true;
  try {
    await api.post(`${props.endpoint}/${props.conversationId}/messages`, { body });
    draft.value = '';
    await load(true);
    emit('sent');
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    sending.value = false;
  }
}

watch(
  () => props.conversationId,
  () => {
    messages.value = [];
    lastCount = 0;
    load();
  },
  { immediate: true },
);

onMounted(() => {
  timer = setInterval(() => {
    if (document.visibilityState === 'visible') load(true);
  }, 2000);
});

onBeforeUnmount(() => {
  if (timer) clearInterval(timer);
});
</script>
