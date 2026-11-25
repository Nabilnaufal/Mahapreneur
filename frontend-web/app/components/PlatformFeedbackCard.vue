<template>
  <section class="card overflow-hidden">
    <div class="border-b border-line bg-surface-muted/50 px-5 py-4">
      <div class="flex items-start gap-3">
        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-tertiary/20 text-primary">
          <MessageSquare class="size-5" />
        </span>
        <div>
          <h2 class="text-sm font-bold text-strong">Beri masukan untuk kami</h2>
          <p class="mt-1 text-sm text-muted">Bagikan pengalamanmu agar Mahapreneur bisa terus menjadi lebih baik.</p>
        </div>
      </div>
    </div>

    <form class="space-y-5 p-5" @submit.prevent="submit">
      <div>
        <p class="label">Bagaimana pengalamanmu menggunakan Mahapreneur?</p>
        <div class="mt-2 flex items-center gap-1" role="radiogroup" aria-label="Rating Mahapreneur">
          <button
            v-for="star in 5"
            :key="star"
            type="button"
            class="rounded-lg p-1.5 transition hover:scale-110 hover:text-amber-400 focus:outline-none focus:ring-2 focus:ring-tertiary"
            :class="star <= rating ? 'text-amber-400' : 'text-slate-300 dark:text-slate-600'"
            :aria-label="star + ' bintang'"
            :aria-checked="rating === star"
            role="radio"
            @click="rating = star"
          >
            <Star class="size-7" :fill="star <= rating ? 'currentColor' : 'none'" stroke-width="1.8" />
          </button>
          <span class="ml-2 text-sm font-semibold text-muted">{{ rating ? rating + '/5' : 'Pilih rating' }}</span>
        </div>
      </div>

      <div>
        <label class="label" for="platform-feedback">Ceritakan pendapatmu</label>
        <textarea
          id="platform-feedback"
          v-model="comment"
          rows="4"
          maxlength="2000"
          class="input mt-2 resize-y"
          placeholder="Apa yang sudah bagus? Apa yang perlu kami perbaiki?"
          required
        />
        <p class="mt-1 text-right text-[11px] text-muted">{{ comment.length }}/2000</p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <button class="btn btn-primary" :disabled="submitting || !rating || comment.trim().length < 5">
          <Loader2 v-if="submitting" class="size-4 animate-spin" />
          Kirim masukan
        </button>
        <p v-if="submitted" class="text-sm font-semibold text-emerald-600">Terima kasih, masukanmu sudah terkirim.</p>
      </div>
    </form>
  </section>
</template>

<script setup lang="ts">
import { Loader2, MessageSquare, Star } from 'lucide-vue-next';

const api = useApi();
const ui = useUiStore();

const rating = ref(0);
const comment = ref('');
const submitting = ref(false);
const submitted = ref(false);

async function submit() {
  if (!rating.value || comment.value.trim().length < 5) return;

  submitting.value = true;
  submitted.value = false;
  try {
    await api.post('/feedback', { rating: rating.value, comment: comment.value.trim() });
    rating.value = 0;
    comment.value = '';
    submitted.value = true;
    ui.toast('Terima kasih atas masukanmu.', 'success');
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    submitting.value = false;
  }
}
</script>
