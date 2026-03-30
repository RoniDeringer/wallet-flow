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
            <div class="text-xs text-white/70">Sistema financeiro de depósito e transferências</div>
          </div>
        </div>
      </div>
    </header>

    <LoginPage v-if="!user" @logged-in="onLoggedIn" />
    <div v-else class="flex w-full flex-1 flex-col py-8 sm:flex-row sm:min-h-0">
      <AppSidebar :user="user" :items="menuItems" :active-key="activeKey" @select="activeKey = $event" @logout="logout" />

      <main class="flex min-h-[60vh] flex-1 flex-col sm:min-h-0">
        <div class="w-full flex-1 sm:px-6">
          <component :is="activeComponent" :user="user" class="flex-1" />
        </div>
      </main>
    </div>

    <AppFooter />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import AppFooter from './components/AppFooter.vue'
import AppSidebar from './components/AppSidebar.vue'
import LoginPage from './pages/LoginPage.vue'
import AdminClientsPage from './pages/admin/AdminClientsPage.vue'
import AdminDepositsPage from './pages/admin/AdminDepositsPage.vue'
import AdminTransactionsPage from './pages/admin/AdminTransactionsPage.vue'
import ClientDepositsPage from './pages/client/ClientDepositsPage.vue'
import ClientTransactionsPage from './pages/client/ClientTransactionsPage.vue'
import ClientWalletPage from './pages/client/ClientWalletPage.vue'

const user = ref(null)
const activeKey = ref('wallet')

function onLoggedIn(payload) {
  user.value = payload
  activeKey.value = payload?.role === 'admin' ? 'clients' : 'wallet'
}

function logout() {
  user.value = null
  activeKey.value = 'wallet'
}

const menuItems = computed(() => {
  if (user.value?.role === 'admin') {
    return [
      { key: 'clients', label: 'Clientes', badge: 'admin' },
      { key: 'transactions', label: 'Transações', badge: 'ledger' },
      { key: 'deposits', label: 'Depósitos', badge: 'ops' },
    ]
  }

  return [
    { key: 'wallet', label: 'Minha carteira', badge: 'saldo' },
    { key: 'transactions', label: 'Transações', badge: 'minhas' },
    { key: 'deposits', label: 'Depósitos', badge: 'novo' },
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
