<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    email:    '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('login'), { onFinish: () => form.reset('password') });
}
</script>

<template>
  <div class="min-h-screen bg-pf-bg flex items-center justify-center px-4">
    <div class="w-full max-w-sm animate-slide-up">

      <!-- Logo -->
      <div class="flex items-center justify-center gap-3 mb-10">
        <div class="w-10 h-10 rounded-xl bg-indigo-500/20 border border-indigo-500/40 flex items-center justify-center">
          <svg viewBox="0 0 24 24" class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 2L6 7l-4 5 4 5 6 5 6-5 4-5-4-5-6-5zm0 0v20M6 7l6 5 6-5M2 12l10 5 10-5" />
          </svg>
        </div>
        <span class="font-display font-bold text-xl text-white tracking-tight">Pixelforge</span>
      </div>

      <div class="bg-pf-card border border-pf rounded-2xl p-7 shadow-2xl shadow-black/40">
        <h1 class="font-display font-semibold text-2xl text-zinc-100 mb-1">Sign in</h1>
        <p class="text-sm text-zinc-400 mb-7">Welcome back — let's create something.</p>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-zinc-300 mb-1.5">Email</label>
            <input
              v-model="form.email"
              type="email"
              autocomplete="email"
              placeholder="you@example.com"
              class="w-full bg-pf-bg border border-pf rounded-lg px-3.5 py-2.5 text-sm text-zinc-200 placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-colors"
            />
            <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-400">{{ form.errors.email }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-zinc-300 mb-1.5">Password</label>
            <input
              v-model="form.password"
              type="password"
              autocomplete="current-password"
              placeholder="••••••••"
              class="w-full bg-pf-bg border border-pf rounded-lg px-3.5 py-2.5 text-sm text-zinc-200 placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-colors"
            />
            <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-400">{{ form.errors.password }}</p>
          </div>

          <div class="flex items-center gap-2">
            <input id="remember" v-model="form.remember" type="checkbox"
              class="w-4 h-4 rounded border-zinc-600 bg-pf-bg text-indigo-500 focus:ring-indigo-500/50"
            />
            <label for="remember" class="text-sm text-zinc-400 cursor-pointer">Remember me</label>
          </div>

          <button type="submit" :disabled="form.processing"
            class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-medium text-sm transition-colors mt-2"
          >
            <div v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            {{ form.processing ? 'Signing in…' : 'Sign in' }}
          </button>
        </form>

        <p class="text-center text-sm text-zinc-500 mt-6">
          No account?
          <Link :href="route('register')" class="text-indigo-400 hover:text-indigo-300 transition-colors">Create one</Link>
        </p>
      </div>
    </div>
  </div>
</template>
