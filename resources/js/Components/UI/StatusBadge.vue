<script setup lang="ts">
import type { StatusBadge } from '@/types';

const props = defineProps<{ status: StatusBadge }>();

const colorMap: Record<string, string> = {
    gray:   'bg-zinc-500/15 text-zinc-300 ring-1 ring-zinc-500/25',
    blue:   'bg-blue-500/15 text-blue-300 ring-1 ring-blue-500/25',
    yellow: 'bg-amber-500/15 text-amber-300 ring-1 ring-amber-500/25',
    green:  'bg-green-500/15 text-green-300 ring-1 ring-green-500/25',
    red:    'bg-red-500/15 text-red-300 ring-1 ring-red-500/25',
};

const dotMap: Record<string, string> = {
    gray:   'bg-zinc-400',
    blue:   'bg-blue-400',
    yellow: 'bg-amber-400 animate-pulse',
    green:  'bg-green-400',
    red:    'bg-red-400',
};
</script>

<template>
  <span :class="['inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium font-mono', colorMap[props.status.color]]">
    <span :class="['w-1.5 h-1.5 rounded-full shrink-0', dotMap[props.status.color]]" />
    {{ props.status.label }}
  </span>
</template>
