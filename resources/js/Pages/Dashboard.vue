<script setup lang="ts">
import AppLayout from '@/Components/Layout/AppLayout.vue';
import GenerationCard from '@/Components/UI/GenerationCard.vue';
import { Link } from '@inertiajs/vue3';

interface RecentGeneration {
    id: number;
    prompt: string;
    status: { value: string; label: string; color: 'gray'|'blue'|'yellow'|'green'|'red' };
    image_url: string | null;
    created_at: string;
}

const props = defineProps<{
    stats: {
        totalGenerations: number;
        successfulGenerations: number;
        totalSpent: string;
    };
    recentGenerations: RecentGeneration[];
}>();

const statCards = [
    {
        label: 'Total Generations',
        key: 'totalGenerations' as const,
        icon: 'layers',
        colorClass: 'text-indigo-400',
        bgClass: 'bg-indigo-500/10 border-indigo-500/20',
    },
    {
        label: 'Successful',
        key: 'successfulGenerations' as const,
        icon: 'check',
        colorClass: 'text-green-400',
        bgClass: 'bg-green-500/10 border-green-500/20',
    },
    {
        label: 'Total Spent',
        key: 'totalSpent' as const,
        icon: 'dollar',
        colorClass: 'text-blue-400',
        bgClass: 'bg-blue-500/10 border-blue-500/20',
    },
];

function formatStat(key: 'totalGenerations'|'successfulGenerations'|'totalSpent'): string {
    if (key === 'totalSpent') return '$' + parseFloat(props.stats[key]).toFixed(4);
    return String(props.stats[key]);
}
</script>

<template>
  <AppLayout title="Dashboard">
    <template #actions>
      <Link :href="route('generations.create')"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition-colors duration-150"
      >
        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
        </svg>
        New Generation
      </Link>
    </template>

    <div class="p-6 space-y-8 max-w-6xl mx-auto">

      <!-- Stats grid -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div
          v-for="(card, i) in statCards"
          :key="card.key"
          :style="{ animationDelay: `${i * 80}ms` }"
          class="bg-pf-card border border-pf rounded-xl p-5 animate-slide-up"
        >
          <div class="flex items-start justify-between mb-4">
            <span class="text-sm text-zinc-400 font-medium">{{ card.label }}</span>
            <div :class="['w-9 h-9 rounded-lg border flex items-center justify-center', card.bgClass]">
              <!-- Layers icon -->
              <svg v-if="card.icon === 'layers'" viewBox="0 0 24 24" :class="['w-4.5 h-4.5', card.colorClass]" fill="none" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3" />
              </svg>
              <!-- Check icon -->
              <svg v-else-if="card.icon === 'check'" viewBox="0 0 24 24" :class="['w-4.5 h-4.5', card.colorClass]" fill="none" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <!-- Dollar icon -->
              <svg v-else viewBox="0 0 24 24" :class="['w-4.5 h-4.5', card.colorClass]" fill="none" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <p :class="['font-display font-semibold text-3xl tabular-nums', card.colorClass]">
            {{ formatStat(card.key) }}
          </p>
        </div>
      </div>

      <!-- Recent generations -->
      <section>
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-display font-semibold text-zinc-100 text-lg">Recent Generations</h2>
          <Link :href="route('generations.index')" class="text-sm text-indigo-400 hover:text-indigo-300 transition-colors">
            View all →
          </Link>
        </div>

        <!-- Empty state -->
        <div v-if="recentGenerations.length === 0"
          class="border border-pf border-dashed rounded-xl p-16 text-center animate-fade-in"
        >
          <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center mx-auto mb-4">
            <svg viewBox="0 0 24 24" class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
            </svg>
          </div>
          <p class="font-display font-semibold text-zinc-200 mb-2">Nothing generated yet</p>
          <p class="text-sm text-zinc-500 mb-6 max-w-xs mx-auto">Your generated images will appear here. Start by creating your first generation.</p>
          <Link :href="route('generations.create')"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition-colors"
          >
            Start generating
          </Link>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          <GenerationCard
            v-for="(gen, i) in recentGenerations"
            :key="gen.id"
            :generation="(gen as any)"
            :index="i"
          />
        </div>
      </section>
    </div>
  </AppLayout>
</template>
