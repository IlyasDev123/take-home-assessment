<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import type { SharedProps } from '@/types';

const props = defineProps<{
    sizes:     Array<{ value: string; label: string }>;
    qualities: Array<{ value: string; label: string }>;
}>();

const page    = usePage<SharedProps>();
const balance = computed(() => {
    const b = page.props.credits?.balance;
    return b !== null && b !== undefined ? parseFloat(b) : null;
});

const form = useForm({
    prompt:           '',
    size:             props.sizes[0]?.value ?? '',
    quality:          props.qualities[0]?.value ?? '',
    reference_images: [] as string[],
});

// Cost estimation
const estimatedCost   = ref<{ provider: string; user: string } | null>(null);
const estimating      = ref(false);
const estimatedAmount = computed(() => {
    if (!estimatedCost.value) return null;
    return parseFloat(estimatedCost.value.user.replace(/[^0-9.]/g, ''));
});
const canAfford = computed(() => {
    if (balance.value === null || estimatedAmount.value === null) return true;
    return balance.value >= estimatedAmount.value;
});
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

async function fetchEstimate() {
    if (!form.size || !form.quality) return;
    estimating.value = true;
    try {
        const params = new URLSearchParams({
            size: form.size,
            quality: form.quality,
            prompt: form.prompt,
            reference_image_count: String(form.reference_images.filter(u => u.trim()).length),
        });
        const res = await fetch(`/generations/estimate-cost?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        });
        if (res.ok) estimatedCost.value = await res.json();
    } finally {
        estimating.value = false;
    }
}

watch([() => form.size, () => form.quality, () => form.prompt, () => form.reference_images.length], () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchEstimate, 300);
}, { immediate: true });

// Reference image URLs
function addReferenceImage() {
    if (form.reference_images.length < 4) form.reference_images.push('');
}
function removeReferenceImage(i: number) {
    form.reference_images.splice(i, 1);
}

// Auto-grow textarea
function autoGrow(e: Event) {
    const el = e.target as HTMLTextAreaElement;
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
}

function submit() {
    form.reference_images = form.reference_images.filter(u => u.trim() !== '');
    form.post(route('generations.store'));
}
</script>

<template>
  <AppLayout title="New Generation">
    <div class="max-w-2xl mx-auto px-6 py-8">

      <div class="mb-7">
        <h2 class="font-display font-semibold text-2xl text-zinc-100 mb-1.5">Create a generation</h2>
        <p class="text-sm text-zinc-400">Describe what you want to create and configure your settings.</p>
      </div>

      <form @submit.prevent="submit" class="space-y-6">

        <!-- Prompt -->
        <div>
          <label class="block text-sm font-medium text-zinc-300 mb-2">Prompt</label>
          <textarea
            v-model="form.prompt"
            @input="autoGrow"
            rows="4"
            placeholder="A cinematic portrait of an astronaut on a neon-lit alien market, volumetric fog, ultra-detailed…"
            class="w-full bg-pf-card border border-pf rounded-xl px-4 py-3 text-sm text-zinc-200 placeholder-zinc-600 resize-none focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-colors min-h-[108px]"
          />
          <p v-if="form.errors.prompt" class="mt-1.5 text-xs text-red-400">{{ form.errors.prompt }}</p>
        </div>

        <!-- Size selector -->
        <div>
          <label class="block text-sm font-medium text-zinc-300 mb-2">Image Size</label>
          <div class="grid grid-cols-3 gap-3">
            <button
              v-for="size in props.sizes"
              :key="size.value"
              type="button"
              @click="form.size = size.value"
              :class="['px-3 py-3 rounded-xl border text-sm text-center transition-all duration-150',
                       form.size === size.value
                         ? 'bg-indigo-500/15 border-indigo-500/50 text-indigo-200'
                         : 'bg-pf-card border-pf text-zinc-400 hover:border-zinc-600 hover:text-zinc-200']"
            >
              <span class="block font-mono text-xs mb-1 opacity-70">{{ size.value }}</span>
              <span class="block text-xs font-medium leading-tight">{{ size.label.split('(')[0].trim() }}</span>
            </button>
          </div>
          <p v-if="form.errors.size" class="mt-1.5 text-xs text-red-400">{{ form.errors.size }}</p>
        </div>

        <!-- Quality toggle -->
        <div>
          <label class="block text-sm font-medium text-zinc-300 mb-2">Quality</label>
          <div class="flex gap-3">
            <button
              v-for="quality in props.qualities"
              :key="quality.value"
              type="button"
              @click="form.quality = quality.value"
              :class="['flex-1 py-2.5 px-4 rounded-xl border text-sm font-medium transition-all duration-150',
                       form.quality === quality.value
                         ? 'bg-indigo-500/15 border-indigo-500/50 text-indigo-200'
                         : 'bg-pf-card border-pf text-zinc-400 hover:border-zinc-600 hover:text-zinc-200']"
            >
              {{ quality.label }}
            </button>
          </div>
          <p v-if="form.errors.quality" class="mt-1.5 text-xs text-red-400">{{ form.errors.quality }}</p>
        </div>

        <!-- Reference images -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="text-sm font-medium text-zinc-300">Reference Images <span class="text-zinc-500">(optional)</span></label>
            <button
              v-if="form.reference_images.length < 5"
              type="button"
              @click="addReferenceImage"
              class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors font-medium"
            >
              + Add URL
            </button>
          </div>
          <div v-if="form.reference_images.length === 0" class="text-xs text-zinc-600 italic">
            No reference images added.
          </div>
          <div v-else class="space-y-2">
            <div v-for="(_, i) in form.reference_images" :key="i" class="flex gap-2">
              <input
                v-model="form.reference_images[i]"
                type="url"
                :placeholder="`https://example.com/reference-${i + 1}.jpg`"
                class="flex-1 bg-pf-card border border-pf rounded-lg px-3 py-2 text-sm text-zinc-200 placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-colors font-mono"
              />
              <button type="button" @click="removeReferenceImage(i)"
                class="p-2 rounded-lg text-zinc-500 hover:text-red-400 hover:bg-red-500/10 transition-colors"
              >
                <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Cost estimate panel -->
        <div :class="['rounded-xl border p-4',
                       !canAfford ? 'border-red-500/30 bg-red-500/5' : 'border-pf bg-pf-card/60']">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-zinc-300">Cost Estimate</span>
            <div v-if="estimating" class="w-4 h-4 border border-indigo-400/40 border-t-indigo-400 rounded-full animate-spin" />
          </div>
          <div v-if="estimatedCost && !estimating">
            <div class="flex items-center justify-between">
              <span class="text-xs text-zinc-500">Your charge</span>
              <span :class="['font-mono font-semibold text-lg',
                             !canAfford ? 'text-red-400' : 'text-indigo-300']">
                {{ estimatedCost.user }}
              </span>
            </div>
            <div class="flex items-center justify-between mt-1">
              <span class="text-xs text-zinc-600">Provider cost</span>
              <span class="font-mono text-xs text-zinc-500">{{ estimatedCost.provider }}</span>
            </div>
            <div v-if="balance !== null" class="flex items-center justify-between mt-1">
              <span class="text-xs text-zinc-600">Your balance</span>
              <span class="font-mono text-xs text-zinc-400">${{ balance.toFixed(4) }}</span>
            </div>
          </div>
          <!-- Insufficient balance warning -->
          <div v-if="!canAfford && estimatedCost" class="mt-3 flex items-start gap-2 p-2.5 rounded-lg bg-red-500/10 border border-red-500/20">
            <svg viewBox="0 0 24 24" class="w-4 h-4 text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <div>
              <p class="text-xs font-medium text-red-300">Insufficient balance</p>
              <p class="text-xs text-red-400/80 mt-0.5">
                <a :href="route('credits.buy')" class="underline hover:text-red-300">Buy credits</a> to generate images.
              </p>
            </div>
          </div>
          <p class="text-xs text-zinc-500 mt-3 leading-relaxed border-t border-pf pt-3">
            Estimated cost only. Final cost is calculated after generation completes and may differ based on actual provider usage.
          </p>
        </div>

        <!-- Submit -->
        <button
          type="submit"
          :disabled="form.processing || !form.prompt.trim() || !canAfford"
          class="w-full flex items-center justify-center gap-2.5 px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium transition-all duration-150 text-sm"
        >
          <div v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
          <svg v-else viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
          </svg>
          {{ form.processing ? 'Queueing…' : 'Generate Image' }}
        </button>

      </form>
    </div>
  </AppLayout>
</template>
