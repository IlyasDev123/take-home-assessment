<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types';

const props = defineProps<{ title: string }>();

const page    = usePage<SharedProps>();
const user    = computed(() => page.props.auth.user);
const flash   = computed(() => page.props.flash);
const balance = computed(() => page.props.credits?.balance ?? null);

const sidebarOpen = ref(false);
const toast = ref<{ message: string; type: 'success' | 'error' } | null>(null);
let toastTimer: ReturnType<typeof setTimeout> | null = null;

function showToast(message: string, type: 'success' | 'error') {
    toast.value = { message, type };
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.value = null; }, 4000);
}

onMounted(() => {
    if (flash.value.success) showToast(flash.value.success, 'success');
    if (flash.value.error)   showToast(flash.value.error,   'error');
});

onUnmounted(() => { if (toastTimer) clearTimeout(toastTimer); });

function logout() {
    router.post(route('logout'));
}

const navLinks = computed(() => {
    const links: Array<{ name: string; href: string; icon: string }> = [
        { name: 'Dashboard', href: route('dashboard'),          icon: 'grid' },
        { name: 'Generate',  href: route('generations.create'), icon: 'sparkle' },
        { name: 'History',   href: route('generations.index'),  icon: 'clock' },
        { name: 'Credits',   href: route('credits.index'),      icon: 'credits' },
    ];
    if (user.value?.is_admin) {
        links.push({ name: 'Admin Report', href: route('admin.report'), icon: 'admin' });
    }
    return links;
});

function isActive(href: string): boolean {
    return window.location.pathname === new URL(href, window.location.origin).pathname
        || window.location.pathname.startsWith(new URL(href, window.location.origin).pathname + '/');
}
</script>

<template>
  <div class="flex h-screen bg-pf-bg overflow-hidden">

    <!-- Mobile backdrop -->
    <Transition enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-from-class="opacity-100" leave-to-class="opacity-0"
                enter-active-class="transition-opacity duration-200"
                leave-active-class="transition-opacity duration-200">
      <div v-if="sidebarOpen" class="fixed inset-0 z-20 bg-black/60 lg:hidden"
           @click="sidebarOpen = false" />
    </Transition>

    <!-- Sidebar -->
    <aside
      :class="['fixed inset-y-0 left-0 z-30 flex flex-col w-64 bg-pf-surface border-r border-pf transition-transform duration-300 lg:static lg:translate-x-0',
               sidebarOpen ? 'translate-x-0' : '-translate-x-full']"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-5 h-16 border-b border-pf shrink-0">
        <div class="w-8 h-8 rounded-lg bg-indigo-500/20 border border-indigo-500/40 flex items-center justify-center shrink-0">
          <svg viewBox="0 0 24 24" class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 2L6 7l-4 5 4 5 6 5 6-5 4-5-4-5-6-5zm0 0v20M6 7l6 5 6-5M2 12l10 5 10-5" />
          </svg>
        </div>
        <span class="font-display font-700 text-lg text-white tracking-tight">Pixelforge</span>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <Link
          v-for="link in navLinks"
          :key="link.name"
          :href="link.href"
          :class="['group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150',
                   isActive(link.href)
                     ? 'bg-indigo-500/15 text-indigo-300 border border-indigo-500/25'
                     : 'text-zinc-400 hover:text-zinc-100 hover:bg-white/5 border border-transparent']"
          @click="sidebarOpen = false"
        >
          <svg v-if="link.icon === 'grid'" viewBox="0 0 24 24" class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75">
            <rect x="3" y="3" width="7" height="7" rx="1.5" /><rect x="14" y="3" width="7" height="7" rx="1.5" />
            <rect x="3" y="14" width="7" height="7" rx="1.5" /><rect x="14" y="14" width="7" height="7" rx="1.5" />
          </svg>
          <svg v-if="link.icon === 'sparkle'" viewBox="0 0 24 24" class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
          </svg>
          <svg v-if="link.icon === 'clock'" viewBox="0 0 24 24" class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75">
            <circle cx="12" cy="12" r="9" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3" />
          </svg>
          <!-- Credits coin icon -->
          <svg v-if="link.icon === 'credits'" viewBox="0 0 24 24" class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75">
            <circle cx="12" cy="12" r="9" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.5 9A2.5 2.5 0 0012 6.5h-1a2 2 0 000 4h2a2 2 0 010 4h-1A2.5 2.5 0 009.5 15M12 6v1.5m0 9V18" />
          </svg>
          <!-- Admin icon -->
          <svg v-if="link.icon === 'admin'" viewBox="0 0 24 24" class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6M3 10h18M3 6h18M3 14h18M3 18h18" />
          </svg>
          {{ link.name }}
        </Link>
      </nav>

      <!-- Balance + User profile -->
      <div class="p-3 border-t border-pf space-y-2">
        <!-- Credit balance -->
        <Link :href="route('credits.index')"
          class="flex items-center justify-between px-3 py-2 rounded-lg bg-indigo-500/8 border border-indigo-500/20 hover:bg-indigo-500/12 transition-colors group"
        >
          <div class="flex items-center gap-2">
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 text-indigo-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="9" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M14.5 9A2.5 2.5 0 0012 6.5h-1a2 2 0 000 4h2a2 2 0 010 4h-1A2.5 2.5 0 009.5 15M12 6v1.5m0 9V18" />
            </svg>
            <span class="text-xs text-zinc-400">Balance</span>
          </div>
          <span class="text-xs font-mono font-semibold text-indigo-300">
            {{ balance !== null ? '$' + parseFloat(balance).toFixed(4) : '—' }}
          </span>
        </Link>
        <!-- User -->
        <div class="flex items-center gap-3 px-2 py-2 rounded-lg">
          <div class="w-7 h-7 rounded-full bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center shrink-0">
            <span class="text-xs font-semibold text-indigo-300">{{ user?.name?.charAt(0)?.toUpperCase() }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-medium text-zinc-200 truncate">{{ user?.name }}</p>
            <p class="text-xs text-zinc-500 truncate">{{ user?.email }}</p>
          </div>
          <button @click="logout"
            class="p-1.5 rounded-md text-zinc-500 hover:text-zinc-200 hover:bg-white/8 transition-colors"
            title="Sign out"
          >
            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
            </svg>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

      <!-- Top header -->
      <header class="flex items-center justify-between px-5 h-16 border-b border-pf bg-pf-surface/50 backdrop-blur-sm shrink-0">
        <!-- Mobile hamburger -->
        <button @click="sidebarOpen = !sidebarOpen"
          class="lg:hidden p-2 rounded-md text-zinc-400 hover:text-zinc-200 hover:bg-white/8 transition-colors mr-3"
        >
          <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <h1 class="font-display font-semibold text-lg text-zinc-100 tracking-tight">{{ props.title }}</h1>

        <div class="flex items-center gap-3">
          <slot name="actions" />
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto">
        <slot />
      </main>
    </div>

    <!-- Toast notification -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 translate-y-2 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-2 scale-95"
    >
      <div v-if="toast"
        :class="['fixed bottom-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-xl border shadow-2xl max-w-sm',
                 toast.type === 'success'
                   ? 'bg-zinc-900 border-green-500/30 text-green-300'
                   : 'bg-zinc-900 border-red-500/30 text-red-300']"
      >
        <svg v-if="toast.type === 'success'" viewBox="0 0 24 24" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg v-else viewBox="0 0 24 24" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        </svg>
        <p class="text-sm font-medium">{{ toast.message }}</p>
        <button @click="toast = null" class="ml-2 opacity-60 hover:opacity-100 transition-opacity">
          <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </Transition>
  </div>
</template>
