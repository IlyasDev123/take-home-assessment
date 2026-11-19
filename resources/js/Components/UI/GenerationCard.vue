<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import StatusBadge from './StatusBadge.vue';
import type { Generation } from '@/types';

const props = defineProps<{ generation: Generation; index?: number }>();

function formatDate(iso: string): string {
    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(iso));
}

function displayCost(gen: Generation): { amount: string; label: string } {
    if (gen.actual_user_cost) return { amount: '$' + parseFloat(gen.actual_user_cost).toFixed(6), label: 'Final' };
    return { amount: '$' + parseFloat(gen.estimated_user_cost).toFixed(6), label: 'Est.' };
}

const gradients = [
    'from-indigo-900/60 via-purple-900/40 to-zinc-900',
    'from-blue-900/60 via-cyan-900/40 to-zinc-900',
    'from-violet-900/60 via-pink-900/40 to-zinc-900',
    'from-emerald-900/60 via-teal-900/40 to-zinc-900',
];

function gradient(id: number): string {
    return gradients[id % gradients.length];
}
</script>

<template>
  <Link
    :href="route('generations.show', generation.id)"
    :style="{ animationDelay: `${(props.index ?? 0) * 60}ms` }"
    class="group block bg-pf-card border border-pf rounded-xl overflow-hidden hover:border-indigo-500/40 transition-all duration-200 hover:shadow-lg hover:shadow-indigo-950/50 animate-fade-in"
  >
    <!-- Thumbnail -->
    <div class="relative h-44 overflow-hidden">
      <img
        v-if="generation.image_url"
        :src="generation.image_url"
        :alt="generation.prompt"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
      />
      <div v-else
        :class="['w-full h-full bg-gradient-to-br flex items-center justify-center', gradient(generation.id)]"
      >
        <!-- Processing spinner -->
        <div v-if="generation.status.value === 'queued' || generation.status.value === 'processing'"
          class="flex flex-col items-center gap-2"
        >
          <div class="w-8 h-8 border-2 border-indigo-400/40 border-t-indigo-400 rounded-full animate-spin" />
          <span class="text-xs text-indigo-300/70 font-mono">generating…</span>
        </div>
        <!-- Failed X -->
        <div v-else-if="generation.status.value === 'failed'"
          class="flex flex-col items-center gap-1.5"
        >
          <div class="w-10 h-10 rounded-full bg-red-500/15 flex items-center justify-center">
            <svg viewBox="0 0 24 24" class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>
          <span class="text-xs text-red-300/70 font-mono">failed</span>
        </div>
        <!-- Pending gem icon -->
        <div v-else class="opacity-30">
          <svg viewBox="0 0 24 24" class="w-10 h-10 text-indigo-300" fill="none" stroke="currentColor" stroke-width="1">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 2L6 7l-4 5 4 5 6 5 6-5 4-5-4-5-6-5zm0 0v20M6 7l6 5 6-5M2 12l10 5 10-5" />
          </svg>
        </div>
      </div>

      <!-- Size label overlay -->
      <div class="absolute top-2 left-2">
        <span class="text-[10px] font-mono bg-black/50 text-zinc-300 px-1.5 py-0.5 rounded backdrop-blur-sm">
          {{ generation.size.value }}
        </span>
      </div>
    </div>

    <!-- Card body -->
    <div class="p-3.5 space-y-2.5">
      <p class="text-sm text-zinc-200 leading-snug line-clamp-2 font-medium">{{ generation.prompt }}</p>

      <div class="flex items-center justify-between">
        <StatusBadge :status="generation.status" />
        <span class="text-xs text-zinc-500">{{ formatDate(generation.created_at) }}</span>
      </div>

      <div class="flex items-center gap-1 pt-0.5 border-t border-pf">
        <span class="text-[10px] text-zinc-500 uppercase tracking-wide font-mono">{{ displayCost(generation).label }}</span>
        <span class="text-xs font-mono text-zinc-300 ml-auto">{{ displayCost(generation).amount }}</span>
      </div>
    </div>
  </Link>
</template>
