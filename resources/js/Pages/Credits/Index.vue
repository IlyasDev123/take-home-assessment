<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

interface Transaction {
    id: number;
    type: 'credit' | 'debit';
    amount: string;
    description: string;
    balance_after: string;
    generation_id: number | null;
    created_at: string;
}

interface Meta {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
}

interface Links {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
}

const props = defineProps<{
    balance: string;
    transactions: { data: Transaction[]; links: Links; meta: Meta };
}>();

const balanceFloat = computed(() => parseFloat(props.balance));
const totalSpent   = computed(() =>
    props.transactions.data
        .filter(t => t.type === 'debit')
        .reduce((sum, t) => sum + parseFloat(t.amount), 0)
);
const totalAdded = computed(() =>
    props.transactions.data
        .filter(t => t.type === 'credit')
        .reduce((sum, t) => sum + parseFloat(t.amount), 0)
);

function fmt(val: string) {
    return parseFloat(val).toFixed(6);
}
function fmtShort(val: number) {
    return '$' + val.toFixed(2);
}
function formatDate(iso: string) {
    const d = new Date(iso);
    const now = new Date();
    const diffMs = now.getTime() - d.getTime();
    const diffHrs = diffMs / (1000 * 60 * 60);

    if (diffHrs < 1)   return 'Just now';
    if (diffHrs < 24)  return `${Math.floor(diffHrs)}h ago`;
    if (diffHrs < 168) return `${Math.floor(diffHrs / 24)}d ago`;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
function formatFull(iso: string) {
    return new Date(iso).toLocaleString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}
</script>

<template>
  <AppLayout title="Credits">
    <div class="max-w-4xl mx-auto px-6 py-8 space-y-7">

      <!-- Balance hero -->
      <div class="rounded-2xl overflow-hidden border border-pf bg-gradient-to-br from-indigo-500/10 via-pf-card to-pf-card">
        <div class="p-6 sm:p-8">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
            <div>
              <p class="text-xs font-semibold text-indigo-400 uppercase tracking-widest mb-2">Available Balance</p>
              <div class="flex items-baseline gap-2">
                <span class="font-mono font-bold text-5xl text-white leading-none">
                  ${{ balanceFloat.toFixed(2) }}
                </span>
                <span class="text-sm text-zinc-500 font-mono">({{ balanceFloat.toFixed(6) }})</span>
              </div>
              <p class="text-xs text-zinc-500 mt-2">Credits are US dollars. They never expire.</p>
            </div>
            <Link :href="route('credits.buy')"
              class="shrink-0 inline-flex items-center gap-2.5 px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-xl transition-all duration-150 shadow-lg shadow-indigo-500/20"
            >
              <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
              </svg>
              Add Credits
            </Link>
          </div>
        </div>

        <!-- Quick stats bar -->
        <div class="border-t border-pf grid grid-cols-3 divide-x divide-pf">
          <div class="px-5 py-4 text-center">
            <p class="text-xs text-zinc-500 mb-0.5">Total Added</p>
            <p class="font-mono text-sm font-semibold text-green-400">+{{ fmtShort(totalAdded) }}</p>
          </div>
          <div class="px-5 py-4 text-center">
            <p class="text-xs text-zinc-500 mb-0.5">Total Spent</p>
            <p class="font-mono text-sm font-semibold text-red-400">−{{ fmtShort(totalSpent) }}</p>
          </div>
          <div class="px-5 py-4 text-center">
            <p class="text-xs text-zinc-500 mb-0.5">Transactions</p>
            <p class="font-mono text-sm font-semibold text-zinc-200">{{ transactions.meta.total }}</p>
          </div>
        </div>
      </div>

      <!-- Transaction history -->
      <div>
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-sm font-semibold text-zinc-300">Transaction History</h2>
          <span v-if="transactions.meta.total > 0" class="text-xs text-zinc-500">
            {{ transactions.meta.total }} transactions
          </span>
        </div>

        <!-- Empty state -->
        <div v-if="transactions.data.length === 0"
          class="rounded-2xl border border-dashed border-pf py-16 text-center"
        >
          <div class="w-14 h-14 rounded-2xl bg-zinc-800 border border-pf flex items-center justify-center mx-auto mb-4">
            <svg viewBox="0 0 24 24" class="w-7 h-7 text-zinc-500" fill="none" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
            </svg>
          </div>
          <p class="text-sm font-medium text-zinc-300 mb-1">No transactions yet</p>
          <p class="text-xs text-zinc-500 mb-6 max-w-xs mx-auto">Add credits to your account to start generating images.</p>
          <Link :href="route('credits.buy')"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors"
          >
            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Add Credits
          </Link>
        </div>

        <!-- Transaction list -->
        <div v-else class="rounded-2xl border border-pf bg-pf-card overflow-hidden">
          <div
            v-for="(tx, i) in transactions.data"
            :key="tx.id"
            :class="['flex items-center gap-4 px-5 py-4 hover:bg-white/2 transition-colors',
                     i < transactions.data.length - 1 ? 'border-b border-pf' : '']"
          >
            <!-- Icon -->
            <div :class="['w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border',
                           tx.type === 'credit'
                             ? 'bg-green-500/10 border-green-500/20'
                             : 'bg-red-500/10 border-red-500/20']">
              <svg v-if="tx.type === 'credit'" viewBox="0 0 24 24" class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
              </svg>
              <svg v-else viewBox="0 0 24 24" class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
              </svg>
            </div>

            <!-- Description + date -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <p class="text-sm text-zinc-200 font-medium truncate">{{ tx.description }}</p>
                <Link
                  v-if="tx.generation_id"
                  :href="route('generations.show', { generation: tx.generation_id })"
                  class="text-xs text-indigo-400 hover:text-indigo-300 shrink-0 transition-colors"
                >
                  View →
                </Link>
              </div>
              <p class="text-xs text-zinc-500 mt-0.5" :title="formatFull(tx.created_at)">
                {{ formatDate(tx.created_at) }}
              </p>
            </div>

            <!-- Amount + balance -->
            <div class="text-right shrink-0 space-y-0.5">
              <p :class="['font-mono text-sm font-bold',
                           tx.type === 'credit' ? 'text-green-400' : 'text-red-400']">
                {{ tx.type === 'credit' ? '+' : '−' }}${{ fmt(tx.amount) }}
              </p>
              <p class="font-mono text-xs text-zinc-600">
                bal ${{ fmt(tx.balance_after) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="transactions.meta.last_page > 1"
          class="flex items-center justify-between mt-4 px-1"
        >
          <p class="text-xs text-zinc-500">
            Showing {{ transactions.meta.from }}–{{ transactions.meta.to }} of {{ transactions.meta.total }}
          </p>
          <div class="flex gap-2">
            <Link
              v-if="transactions.links.prev"
              :href="transactions.links.prev"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-zinc-400 border border-pf rounded-lg hover:border-zinc-600 hover:text-zinc-200 transition-all"
            >
              <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
              </svg>
              Previous
            </Link>
            <Link
              v-if="transactions.links.next"
              :href="transactions.links.next"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-zinc-400 border border-pf rounded-lg hover:border-zinc-600 hover:text-zinc-200 transition-all"
            >
              Next
              <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
              </svg>
            </Link>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
