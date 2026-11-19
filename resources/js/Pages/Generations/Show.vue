<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import type { Generation } from '@/types';

interface WebhookEvent {
    event_type: string;
    processed_at: string | null;
    created_at: string;
}

const props = defineProps<{
    generation: Generation;
    webhookEvents: WebhookEvent[];
}>();

const isActive = computed(() =>
    ['pending', 'queued', 'processing'].includes(props.generation.status.value)
);

let pollInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    if (isActive.value) {
        pollInterval = setInterval(() => {
            router.reload({ only: ['generation'] });
        }, 5_000);
    }
});

onUnmounted(() => { if (pollInterval) clearInterval(pollInterval); });

function fmt(iso: string): string {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
    }).format(new Date(iso));
}

const timeline = computed(() => [
    {
        label: 'Created',
        time: props.generation.created_at,
        done: true,
        icon: 'circle',
    },
    {
        label: 'Submitted to Pixelforge',
        time: null,
        done: ['queued','processing','succeeded','failed'].includes(props.generation.status.value),
        icon: 'send',
    },
    {
        label: 'Processing',
        time: null,
        done: ['processing','succeeded','failed'].includes(props.generation.status.value),
        icon: 'gear',
        active: props.generation.status.value === 'processing',
    },
    {
        label: props.generation.status.value === 'failed' ? 'Failed' : 'Completed',
        time: props.generation.completed_at,
        done: props.generation.status.isTerminal ?? ['succeeded','failed'].includes(props.generation.status.value),
        icon: props.generation.status.value === 'failed' ? 'x' : 'check',
        failed: props.generation.status.value === 'failed',
    },
]);
</script>

<template>
  <AppLayout :title="`Generation #${generation.id}`">
    <template #actions>
      <Link :href="route('generations.index')"
        class="text-sm text-zinc-400 hover:text-zinc-200 transition-colors"
      >← History</Link>
    </template>

    <div class="px-6 py-8 max-w-5xl mx-auto">

      <!-- Active indicator -->
      <div v-if="isActive"
        class="flex items-center gap-2.5 mb-6 px-4 py-2.5 rounded-lg bg-blue-500/10 border border-blue-500/25 text-blue-300 text-sm"
      >
        <div class="w-4 h-4 border border-blue-400/40 border-t-blue-400 rounded-full animate-spin shrink-0" />
        Auto-refreshing every 5 seconds…
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        <!-- Left: image + prompt -->
        <div class="lg:col-span-3 space-y-5">

          <!-- Image -->
          <div class="bg-pf-card border border-pf rounded-2xl overflow-hidden">
            <img
              v-if="generation.image_url"
              :src="generation.image_url"
              :alt="generation.prompt"
              class="w-full object-cover"
            />
            <div v-else class="h-80 flex items-center justify-center bg-gradient-to-br from-indigo-950/40 to-zinc-900">
              <div v-if="isActive" class="flex flex-col items-center gap-3">
                <div class="w-10 h-10 border-2 border-indigo-400/30 border-t-indigo-400 rounded-full animate-spin" />
                <p class="text-sm text-zinc-400 font-mono">Generating…</p>
              </div>
              <div v-else-if="generation.status.value === 'failed'" class="flex flex-col items-center gap-2">
                <div class="w-14 h-14 rounded-full bg-red-500/15 flex items-center justify-center">
                  <svg viewBox="0 0 24 24" class="w-7 h-7 text-red-400" fill="none" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                  </svg>
                </div>
                <p class="text-sm text-red-300 font-medium">Generation Failed</p>
                <p v-if="generation.failure_reason" class="text-xs text-red-400/70 text-center max-w-xs px-4">{{ generation.failure_reason }}</p>
              </div>
              <div v-else class="opacity-30">
                <svg viewBox="0 0 24 24" class="w-14 h-14 text-indigo-300" fill="none" stroke="currentColor" stroke-width="0.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 2L6 7l-4 5 4 5 6 5 6-5 4-5-4-5-6-5zm0 0v20M6 7l6 5 6-5M2 12l10 5 10-5" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Prompt -->
          <div class="bg-pf-card border border-pf rounded-xl p-4">
            <p class="text-xs text-zinc-500 uppercase tracking-wider font-mono mb-2">Prompt</p>
            <p class="text-sm text-zinc-200 leading-relaxed">{{ generation.prompt }}</p>
          </div>

          <!-- Settings row -->
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-pf-card border border-pf rounded-xl p-3.5">
              <p class="text-xs text-zinc-500 uppercase tracking-wider font-mono mb-1.5">Size</p>
              <p class="text-sm text-zinc-200 font-medium">{{ generation.size.label }}</p>
              <p class="text-xs font-mono text-zinc-500">{{ generation.size.value }}</p>
            </div>
            <div class="bg-pf-card border border-pf rounded-xl p-3.5">
              <p class="text-xs text-zinc-500 uppercase tracking-wider font-mono mb-1.5">Quality</p>
              <p class="text-sm text-zinc-200 font-medium">{{ generation.quality.label }}</p>
            </div>
          </div>

          <!-- Reference images -->
          <div v-if="generation.reference_images.length > 0" class="bg-pf-card border border-pf rounded-xl p-4">
            <p class="text-xs text-zinc-500 uppercase tracking-wider font-mono mb-3">Reference Images</p>
            <div class="space-y-2">
              <a v-for="url in generation.reference_images" :key="url"
                :href="url" target="_blank" rel="noopener"
                class="block text-xs font-mono text-indigo-400 hover:text-indigo-300 transition-colors truncate"
              >{{ url }}</a>
            </div>
          </div>
        </div>

        <!-- Right: status + cost -->
        <div class="lg:col-span-2 space-y-4">

          <!-- Status card -->
          <div class="bg-pf-card border border-pf rounded-xl p-5">
            <p class="text-xs text-zinc-500 uppercase tracking-wider font-mono mb-3">Status</p>
            <StatusBadge :status="generation.status" />
          </div>

          <!-- Cost breakdown -->
          <div class="bg-pf-card border border-pf rounded-xl p-5 space-y-4">
            <p class="text-xs text-zinc-500 uppercase tracking-wider font-mono">Cost Breakdown</p>

            <div class="space-y-3">
              <div>
                <div class="flex justify-between items-baseline mb-0.5">
                  <span class="text-xs text-zinc-500">Estimated (you)</span>
                  <span class="font-mono text-sm text-zinc-300">${{ parseFloat(generation.estimated_user_cost).toFixed(6) }}</span>
                </div>
                <div class="flex justify-between items-baseline">
                  <span class="text-xs text-zinc-600">Estimated (provider)</span>
                  <span class="font-mono text-xs text-zinc-500">${{ parseFloat(generation.estimated_provider_cost).toFixed(6) }}</span>
                </div>
              </div>

              <div v-if="generation.actual_user_cost" class="pt-3 border-t border-pf">
                <div class="flex justify-between items-baseline mb-0.5">
                  <span class="text-xs text-zinc-400 font-medium">Final (you)</span>
                  <span class="font-mono text-base text-green-300 font-semibold">${{ parseFloat(generation.actual_user_cost).toFixed(6) }}</span>
                </div>
                <div class="flex justify-between items-baseline">
                  <span class="text-xs text-zinc-600">Final (provider)</span>
                  <span class="font-mono text-xs text-zinc-500">${{ parseFloat(generation.actual_provider_cost ?? '0').toFixed(6) }}</span>
                </div>
              </div>

              <div v-else class="pt-3 border-t border-pf">
                <p class="text-xs text-zinc-500 italic">Final cost calculated after generation completes.</p>
              </div>
            </div>
          </div>

          <!-- Webhook events -->
          <div v-if="webhookEvents.length > 0" class="bg-pf-card border border-pf rounded-xl p-5">
            <p class="text-xs text-zinc-500 uppercase tracking-wider font-mono mb-3">Webhook Events</p>
            <div class="space-y-2">
              <div v-for="event in webhookEvents" :key="event.created_at"
                class="flex items-start justify-between gap-2 text-xs"
              >
                <span class="font-mono text-indigo-300/80">{{ event.event_type }}</span>
                <span class="text-zinc-600 shrink-0">{{ fmt(event.created_at) }}</span>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Timeline -->
      <div class="mt-8 bg-pf-card border border-pf rounded-2xl p-6">
        <p class="text-xs text-zinc-500 uppercase tracking-wider font-mono mb-6">Timeline</p>
        <div class="relative">
          <!-- Vertical line -->
          <div class="absolute left-4 top-0 bottom-0 w-px bg-pf-border" />

          <div class="space-y-6">
            <div v-for="(step, i) in timeline" :key="i" class="flex gap-4 pl-0">
              <!-- Icon -->
              <div :class="['w-8 h-8 rounded-full border flex items-center justify-center shrink-0 relative z-10',
                            step.failed    ? 'bg-red-500/15 border-red-500/40' :
                            step.done      ? 'bg-green-500/15 border-green-500/40' :
                            (step as any).active ? 'bg-blue-500/15 border-blue-500/40' :
                                             'bg-pf-bg border-pf-muted']"
              >
                <div v-if="(step as any).active" class="w-3 h-3 border border-blue-400/40 border-t-blue-400 rounded-full animate-spin" />
                <svg v-else-if="step.done && !step.failed" viewBox="0 0 24 24" class="w-3.5 h-3.5 text-green-400" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <svg v-else-if="step.failed" viewBox="0 0 24 24" class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <div v-else class="w-2 h-2 rounded-full bg-zinc-600" />
              </div>

              <div class="pb-2">
                <p :class="['text-sm font-medium',
                            step.failed ? 'text-red-300' :
                            step.done   ? 'text-zinc-200' : 'text-zinc-500']">
                  {{ step.label }}
                </p>
                <p v-if="step.time" class="text-xs font-mono text-zinc-500 mt-0.5">{{ fmt(step.time) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
