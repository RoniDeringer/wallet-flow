<template>
  <div class="flex min-h-screen flex-col bg-gradient-to-br from-[#032952] via-[#021e3d] to-[#000814] text-white">
    <header class="mx-auto w-full max-w-6xl px-6 pt-10">
      <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="grid size-11 place-items-center rounded-2xl bg-white/10 ring-1 ring-white/15">
            <svg class="size-6 text-[#e9c15e]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 3l8 4v10l-8 4-8-4V7l8-4z"
              />
            </svg>
          </div>
          <div class="leading-tight">
            <div class="text-lg font-semibold tracking-tight">Wallet Flow</div>
            <div class="text-xs text-white/70">Azul &amp; dourado • Vue + Laravel</div>
          </div>
        </div>

        <div class="hidden sm:flex items-center gap-2 text-xs text-white/75">
          <span class="rounded-full bg-white/10 px-3 py-1 ring-1 ring-white/10">#032952</span>
          <span class="rounded-full bg-white/10 px-3 py-1 ring-1 ring-white/10">#e9c15e</span>
        </div>
      </div>
    </header>

    <LoginPage v-if="!user" @logged-in="onLoggedIn" />
    <WelcomePage v-else :user="user" @logout="user = null" />

    <AppFooter />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppFooter from './components/AppFooter.vue'
import LoginPage from './pages/LoginPage.vue'
import WelcomePage from './pages/WelcomePage.vue'

const user = ref(null)

function onLoggedIn(payload) {
  user.value = payload
}
</script>

