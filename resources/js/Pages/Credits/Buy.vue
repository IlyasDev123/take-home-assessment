<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

interface Package {
    amount: string;
    label: string;
    description: string;
    popular?: boolean;
}

const props = defineProps<{
    balance: string;
    packages: Package[];
}>();

const form          = useForm({ amount: '' });
const customAmount  = ref('');
const selectedPkg   = ref<string | null>(null);

const previewBalance = computed(() => {
    const current = parseFloat(props.balance);
    const adding  = parseFloat(form.amount) || 0;
    return current + adding;
});

function selectPackage(pkg: Package) {
    selectedPkg.value  = pkg.amount;
    form.amount        = parseFloat(pkg.amount).toFixed(2);
    customAmount.value = '';
}

function onCustomInput() {
    selectedPkg.value = null;
    form.amount       = customAmount.value;
}

function submit() {
    form.post(route('credits.purchase'));
}
</script>

<template>
  <AppLayout title="Add Credits">
    <div class="max-w-2xl mx-auto px-6 py-8">

      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center gap-3 mb-1">
          <Link :href="route('credits.index')" class="text-zinc-500 hover:text-zinc-300 transition-colors">
            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
          </Link>
          <h2 class="font-display font-semibold text-2xl text-zinc-100">Add Credits</h2>
        </div>
        <p class="text-sm text-zinc-400 pl-7">
          Current balance:
          <span class="font-mono text-indigo-300">${{ parseFloat(balance).toFixed(4) }}</span>
        </p>
      </div>

      <form @submit.prevent="submit" class="space-y-7">

        <!-- Package grid -->
        <div>
          <p class="text-xs font-medium text-zinc-500 uppercase tracking-wider mb-3">Choose a package</p>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <button
              v-for="pkg in packages"
              :key="pkg.amount"
              type="button"
              @click="selectPackage(pkg)"
              :class="['relative group px-4 py-5 rounded-2xl border text-left transition-all duration-150',
                       selectedPkg === pkg.amount
                         ? 'bg-indigo-500/15 border-indigo-500/40 shadow-lg shadow-indigo-500/10'
                         : 'bg-pf-card border-pf hover:border-zinc-600 hover:bg-white/3']"
            >
              <span class="block text-xs text-zinc-500 font-medium mb-2">{{ pkg.description }}</span>
              <span :class="['block font-mono font-bold text-3xl transition-colors',
                             selectedPkg === pkg.amount ? 'text-indigo-200' : 'text-zinc-100 group-hover:text-white']">
                {{ pkg.label }}
              </span>
              <!-- Selected check -->
              <div v-if="selectedPkg === pkg.amount"
                class="absolute top-3 right-3 w-5 h-5 bg-indigo-500 rounded-full flex items-center justify-center"
              >
                <svg viewBox="0 0 24 24" class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
              </div>
            </button>
          </div>
        </div>

        <!-- Divider -->
        <div class="relative">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-pf"></div>
          </div>
          <div class="relative flex justify-center">
            <span class="bg-pf-bg px-3 text-xs text-zinc-600">or enter a custom amount</span>
          </div>
        </div>

        <!-- Custom amount -->
        <div>
          <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-mono text-zinc-400 text-lg select-none">$</span>
            <input
              v-model="customAmount"
              @input="onCustomInput"
              type="number"
              min="1"
              max="1000"
              step="0.01"
              placeholder="0.00"
              class="w-full bg-pf-card border border-pf rounded-xl pl-9 pr-4 py-3.5 font-mono text-lg text-zinc-100 placeholder-zinc-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-colors"
              :class="{ 'border-indigo-500/40 ring-2 ring-indigo-500/20': selectedPkg === null && customAmount }"
            />
          </div>
          <p v-if="form.errors.amount" class="mt-2 text-xs text-red-400">{{ form.errors.amount }}</p>
        </div>

        <!-- Preview card -->
        <div v-if="form.amount && parseFloat(form.amount) > 0"
          class="rounded-2xl border border-indigo-500/20 bg-indigo-500/8 p-5"
        >
          <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-medium text-indigo-300">Balance after purchase</span>
            <span class="font-mono font-bold text-2xl text-white">
              ${{ previewBalance.toFixed(4) }}
            </span>
          </div>
          <div class="flex items-center gap-2 text-xs text-zinc-500">
            <span class="font-mono">${{ parseFloat(balance).toFixed(4) }}</span>
            <span>+</span>
            <span class="font-mono text-green-400">${{ parseFloat(form.amount).toFixed(4) }}</span>
            <span>=</span>
            <span class="font-mono text-indigo-300">${{ previewBalance.toFixed(4) }}</span>
          </div>
        </div>

        <!-- Disclaimer -->
        <p class="text-xs text-zinc-600 leading-relaxed">
          This is a simulated purchase — no real payment is processed. Credits are added instantly to your account and can be used to generate images. Unused credits carry over indefinitely.
        </p>

        <!-- Submit -->
        <button
          type="submit"
          :disabled="form.processing || !form.amount || parseFloat(form.amount) <= 0"
          class="w-full flex items-center justify-center gap-2.5 px-5 py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold text-sm transition-all duration-150 shadow-lg shadow-indigo-500/20"
        >
          <div v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
          <svg v-else viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          {{ form.processing ? 'Processing…' : `Add $${form.amount ? parseFloat(form.amount).toFixed(2) : '0.00'} Credits` }}
        </button>

      </form>
    </div>
  </AppLayout>
</template>
