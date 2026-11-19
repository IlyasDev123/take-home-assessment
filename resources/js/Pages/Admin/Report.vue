<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/Components/Layout/AppLayout.vue';

interface DailyRow {
    date: string;
    total: number;
    succeeded: number;
    provider_cost: string;
    user_cost: string;
    profit: string;
}

interface UserRow {
    id: number;
    name: string;
    email: string;
    total: number;
    succeeded: number;
    provider_cost: string;
    user_cost: string;
    profit: string;
}

interface Totals {
    total: number;
    succeeded: number;
    provider_cost: string;
    user_cost: string;
    profit: string;
}

const props = defineProps<{
    daily: DailyRow[];
    users: UserRow[];
    totals: Totals;
    pixelforgeUsage: Record<string, unknown> | null;
}>();

const activeTab = ref<'daily' | 'users'>('daily');

function fmt(val: string) {
    const n = parseFloat(val);
    return n.toFixed(6);
}

function fmtShort(val: string) {
    const n = parseFloat(val);
    return '$' + n.toFixed(4);
}

const totalProviderFromUsage = computed(() => {
    if (!props.pixelforgeUsage) return null;
    // Try common fields: total_cost, total_provider_cost, cost
    const u = props.pixelforgeUsage as Record<string, unknown>;
    return u.total_cost ?? u.cost ?? u.total_provider_cost ?? null;
});
</script>

<template>
  <AppLayout title="Admin Report">
    <div class="max-w-6xl mx-auto px-6 py-8 space-y-8">

      <!-- Summary cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-pf bg-pf-card p-4">
          <p class="text-xs text-zinc-500 font-medium mb-1">Total Generations</p>
          <p class="font-mono text-2xl font-bold text-zinc-100">{{ totals.total }}</p>
          <p class="text-xs text-zinc-600 mt-1">{{ totals.succeeded }} succeeded</p>
        </div>
        <div class="rounded-xl border border-pf bg-pf-card p-4">
          <p class="text-xs text-zinc-500 font-medium mb-1">Provider Cost</p>
          <p class="font-mono text-2xl font-bold text-red-400">{{ fmtShort(totals.provider_cost) }}</p>
          <p class="text-xs text-zinc-600 mt-1">What Pixelforge charged</p>
        </div>
        <div class="rounded-xl border border-pf bg-pf-card p-4">
          <p class="text-xs text-zinc-500 font-medium mb-1">Credit Spend</p>
          <p class="font-mono text-2xl font-bold text-indigo-300">{{ fmtShort(totals.user_cost) }}</p>
          <p class="text-xs text-zinc-600 mt-1">What users were charged</p>
        </div>
        <div class="rounded-xl border border-pf bg-pf-card p-4">
          <p class="text-xs text-zinc-500 font-medium mb-1">Profit</p>
          <p class="font-mono text-2xl font-bold text-green-400">{{ fmtShort(totals.profit) }}</p>
          <p class="text-xs text-zinc-600 mt-1">25% markup</p>
        </div>
      </div>

      <!-- Pixelforge usage comparison -->
      <div v-if="pixelforgeUsage !== null"
        class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-4"
      >
        <div class="flex items-start gap-3">
          <svg viewBox="0 0 24 24" class="w-4 h-4 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
          </svg>
          <div class="flex-1">
            <p class="text-xs font-semibold text-amber-300 mb-1">Pixelforge Usage API</p>
            <p class="text-xs text-zinc-400 mb-2">Raw response from GET /v1/usage (last 30 days):</p>
            <pre class="text-xs text-zinc-300 bg-pf-bg rounded-lg p-3 overflow-x-auto font-mono leading-relaxed">{{ JSON.stringify(pixelforgeUsage, null, 2) }}</pre>
            <div v-if="totalProviderFromUsage !== null" class="mt-2 flex items-center gap-2">
              <span class="text-xs text-zinc-500">Provider reports:</span>
              <span class="font-mono text-xs text-amber-300">{{ totalProviderFromUsage }}</span>
              <span class="text-xs text-zinc-600">vs our records:</span>
              <span class="font-mono text-xs text-indigo-300">{{ fmtShort(totals.provider_cost) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div>
        <div class="flex gap-1 p-1 bg-pf-card border border-pf rounded-xl w-fit mb-5">
          <button
            @click="activeTab = 'daily'"
            :class="['px-4 py-1.5 rounded-lg text-sm font-medium transition-all duration-150',
                     activeTab === 'daily'
                       ? 'bg-indigo-500/20 text-indigo-200 border border-indigo-500/30'
                       : 'text-zinc-400 hover:text-zinc-200']"
          >Daily Breakdown</button>
          <button
            @click="activeTab = 'users'"
            :class="['px-4 py-1.5 rounded-lg text-sm font-medium transition-all duration-150',
                     activeTab === 'users'
                       ? 'bg-indigo-500/20 text-indigo-200 border border-indigo-500/30'
                       : 'text-zinc-400 hover:text-zinc-200']"
          >Per-User Breakdown</button>
        </div>

        <!-- Daily table -->
        <div v-if="activeTab === 'daily'" class="rounded-xl border border-pf bg-pf-card overflow-hidden">
          <div v-if="daily.length === 0" class="py-12 text-center text-zinc-500 text-sm">
            No completed generations in the last 30 days.
          </div>
          <table v-else class="w-full text-sm">
            <thead>
              <tr class="border-b border-pf bg-pf-bg/50">
                <th class="text-left px-5 py-3 text-xs font-medium text-zinc-500">Date</th>
                <th class="text-right px-5 py-3 text-xs font-medium text-zinc-500">Generations</th>
                <th class="text-right px-5 py-3 text-xs font-medium text-zinc-500">Provider Cost</th>
                <th class="text-right px-5 py-3 text-xs font-medium text-zinc-500">Credit Spend</th>
                <th class="text-right px-5 py-3 text-xs font-medium text-zinc-500">Profit</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-pf">
              <tr v-for="row in daily" :key="row.date" class="hover:bg-white/2 transition-colors">
                <td class="px-5 py-3 font-mono text-xs text-zinc-300">{{ row.date }}</td>
                <td class="px-5 py-3 text-right text-zinc-400">
                  {{ row.succeeded }}<span class="text-zinc-600">/{{ row.total }}</span>
                </td>
                <td class="px-5 py-3 text-right font-mono text-xs text-red-400">{{ fmt(row.provider_cost) }}</td>
                <td class="px-5 py-3 text-right font-mono text-xs text-indigo-300">{{ fmt(row.user_cost) }}</td>
                <td class="px-5 py-3 text-right font-mono text-xs text-green-400">{{ fmt(row.profit) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Users table -->
        <div v-if="activeTab === 'users'" class="rounded-xl border border-pf bg-pf-card overflow-hidden">
          <div v-if="users.length === 0" class="py-12 text-center text-zinc-500 text-sm">
            No generation data yet.
          </div>
          <table v-else class="w-full text-sm">
            <thead>
              <tr class="border-b border-pf bg-pf-bg/50">
                <th class="text-left px-5 py-3 text-xs font-medium text-zinc-500">User</th>
                <th class="text-right px-5 py-3 text-xs font-medium text-zinc-500">Generations</th>
                <th class="text-right px-5 py-3 text-xs font-medium text-zinc-500">Provider Cost</th>
                <th class="text-right px-5 py-3 text-xs font-medium text-zinc-500">Credit Spend</th>
                <th class="text-right px-5 py-3 text-xs font-medium text-zinc-500">Profit</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-pf">
              <tr v-for="u in users" :key="u.id" class="hover:bg-white/2 transition-colors">
                <td class="px-5 py-3">
                  <p class="text-zinc-200 text-sm font-medium">{{ u.name }}</p>
                  <p class="text-zinc-500 text-xs">{{ u.email }}</p>
                </td>
                <td class="px-5 py-3 text-right text-zinc-400">
                  {{ u.succeeded }}<span class="text-zinc-600">/{{ u.total }}</span>
                </td>
                <td class="px-5 py-3 text-right font-mono text-xs text-red-400">{{ fmt(u.provider_cost) }}</td>
                <td class="px-5 py-3 text-right font-mono text-xs text-indigo-300">{{ fmt(u.user_cost) }}</td>
                <td class="px-5 py-3 text-right font-mono text-xs text-green-400">{{ fmt(u.profit) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
