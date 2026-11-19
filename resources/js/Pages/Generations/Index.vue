<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import GenerationCard from '@/Components/UI/GenerationCard.vue';
import type { Generation, PaginatedResource } from '@/types';

const props = defineProps<{
    generations: PaginatedResource<Generation>;
}>();

const hasActiveGenerations = computed(() =>
    props.generations.data.some(g =>
        g.status.value === 'queued' || g.status.value === 'processing'
    )
);

let pollInterval: ReturnType<typeof setInterval> | null = null;

function startPolling() {
    pollInterval = setInterval(() => {
        if (hasActiveGenerations.value) {
            router.reload({ only: ['generations'] });
        }
    }, 10_000);
}

onMounted(startPolling);
onUnmounted(() => { if (pollInterval) clearInterval(pollInterval); });
</script>

<template>
  <AppLayout title="History">
    <template #actions>
      <Link :href="route('generations.create')"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition-colors"
      >
        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        New
      </Link>
    </template>

    <div class="px-6 py-8 max-w-6xl mx-auto">

      <!-- Active indicator -->
      <div v-if="hasActiveGenerations"
        class="flex items-center gap-2.5 mb-6 px-4 py-2.5 rounded-lg bg-blue-500/10 border border-blue-500/25 text-blue-300 text-sm animate-fade-in"
      >
        <div class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse" />
        <span>{{ props.generations.data.filter(g => ['queued','processing'].includes(g.status.value)).length }} generation(s) in progress — auto-refreshing every 10s</span>
      </div>

      <!-- Empty state -->
      <div v-if="generations.data.length === 0"
        class="border border-pf border-dashed rounded-2xl p-20 text-center animate-fade-in"
      >
        <div class="w-20 h-20 rounded-3xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center mx-auto mb-6">
          <svg viewBox="0 0 24 24" class="w-10 h-10 text-indigo-400/70" fill="none" stroke="currentColor" stroke-width="1.25">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
          </svg>
        </div>
        <p class="font-display font-semibold text-zinc-200 text-xl mb-2">No generations yet</p>
        <p class="text-sm text-zinc-500 max-w-sm mx-auto mb-7">
          Your generation history will appear here. Create your first image to get started.
        </p>
        <Link :href="route('generations.create')"
          class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium transition-colors"
        >
          Create first generation
        </Link>
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <GenerationCard
          v-for="(gen, i) in generations.data"
          :key="gen.id"
          :generation="gen"
          :index="i"
        />
      </div>

      <!-- Pagination -->
      <div v-if="generations.meta.last_page > 1" class="flex items-center justify-center gap-2 mt-10">
        <Link
          v-if="generations.links.prev"
          :href="generations.links.prev"
          class="px-3 py-1.5 rounded-lg bg-pf-card border border-pf text-sm text-zinc-300 hover:border-indigo-500/40 transition-colors"
        >← Prev</Link>
        <span class="text-xs text-zinc-500 px-2">
          Page {{ generations.meta.current_page }} of {{ generations.meta.last_page }}
        </span>
        <Link
          v-if="generations.links.next"
          :href="generations.links.next"
          class="px-3 py-1.5 rounded-lg bg-pf-card border border-pf text-sm text-zinc-300 hover:border-indigo-500/40 transition-colors"
        >Next →</Link>
      </div>

    </div>
  </AppLayout>
</template>
