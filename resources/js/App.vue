<template>
  <div class="flex h-screen flex-col bg-gradient-to-br from-[#032952] via-[#021e3d] to-[#000814] text-white">
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
            <div class="text-xs text-white/70">Sistema financeiro de depósito e transferências</div>
          </div>
        </div>
      </div>
    </header>

    <LoginPage v-if="!user" @logged-in="onLoggedIn" />
    <div v-else class="flex w-full flex-1 flex-col overflow-hidden py-8 sm:flex-row sm:min-h-0">
      <AppSidebar :user="user" :items="menuItems" :active-key="activeKey" @select="activeKey = $event" @logout="logout" />

      <main class="flex min-h-[60vh] flex-1 flex-col overflow-hidden sm:min-h-0">
        <div class="w-full flex-1 overflow-y-auto sm:px-6">
          <component :is="activeComponent" :user="user" class="flex-1" />
        </div>
      </main>
    </div>

    <AppFooter />
  </div>
</template>

<script setup>
import axios from 'axios'
import { computed, ref, watch } from 'vue'
import AppFooter from './components/AppFooter.vue'
import AppSidebar from './components/AppSidebar.vue'
import LoginPage from './pages/LoginPage.vue'
import AdminClientsPage from './pages/admin/AdminClientsPage.vue'
import AdminDepositsPage from './pages/admin/AdminDepositsPage.vue'
import AdminTransactionsPage from './pages/admin/AdminTransactionsPage.vue'
import ClientDepositsPage from './pages/client/ClientDepositsPage.vue'
import ClientTransactionsPage from './pages/client/ClientTransactionsPage.vue'
import ClientWalletPage from './pages/client/ClientWalletPage.vue'

function loadSession() {
  try {
    return JSON.parse(localStorage.getItem('wf_session') || 'null')
  } catch {
    return null
  }
}

const session = loadSession()
const user = ref(session?.user ?? null)
const token = ref(session?.token ?? '')
const activeKey = ref(session?.activeKey ?? 'wallet')

function onLoggedIn(payload) {
  user.value = payload?.user ?? null
  token.value = payload?.token ?? ''
  activeKey.value = user.value?.role === 'admin' ? 'clients' : 'wallet'
}

async function logout() {
  try {
    await axios.post('/api/logout')
  } catch {
    // ignore
  }

  delete axios.defaults.headers.common['Authorization']
  user.value = null
  token.value = ''
  activeKey.value = 'wallet'
}

watch(
  () => ({ user: user.value, token: token.value, activeKey: activeKey.value }),
  (next) => {
    if (!next.user) {
      localStorage.removeItem('wf_session')
      return
    }

    localStorage.setItem(
      'wf_session',
      JSON.stringify({
        user: next.user,
        token: next.token,
        activeKey: next.activeKey,
      })
    )
  },
  { deep: true }
)

const menuItems = computed(() => {
  if (user.value?.role === 'admin') {
    return [
      { key: 'clients', label: 'Clientes' },
      { key: 'transactions', label: 'Transações' },
      { key: 'deposits', label: 'Depósitos' },
    ]
  }

  return [
    { key: 'wallet', label: 'Minha carteira' },
    { key: 'transactions', label: 'Transações' },
    { key: 'deposits', label: 'Depósitos' },
  ]
})

const activeComponent = computed(() => {
  if (user.value?.role === 'admin') {
    if (activeKey.value === 'clients') return AdminClientsPage
    if (activeKey.value === 'transactions') return AdminTransactionsPage
    if (activeKey.value === 'deposits') return AdminDepositsPage
    return AdminClientsPage
  }

  if (activeKey.value === 'wallet') return ClientWalletPage
  if (activeKey.value === 'transactions') return ClientTransactionsPage
  if (activeKey.value === 'deposits') return ClientDepositsPage
  return ClientWalletPage
})
</script>
