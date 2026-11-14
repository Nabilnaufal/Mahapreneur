<template>
  <span class="badge" :class="tone">{{ label }}</span>
</template>

<script setup lang="ts">
import type { OrderStatus, PaymentStatus } from '~/types';

const props = defineProps<{ status: OrderStatus | PaymentStatus; kind?: 'order' | 'payment' }>();

const label = computed(() =>
  props.kind === 'payment'
    ? paymentStatusLabel(props.status as PaymentStatus)
    : orderStatusLabel(props.status as OrderStatus),
);

const tone = computed(() =>
  props.kind === 'payment'
    ? PAYMENT_STATUS_TONE[props.status as PaymentStatus]
    : ORDER_STATUS_TONE[props.status as OrderStatus],
);
</script>