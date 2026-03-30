<template>
  <section class="flex flex-1 flex-col px-6 pb-10 pt-6">
    <div class="rounded-3xl bg-white/5 p-8 ring-1 ring-white/10">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h2 class="text-xl font-semibold text-white">Lista de clientes</h2>
          <p class="mt-2 text-sm text-white/70">Nome, e-mail e saldo total (Ledger, BRL).</p>
        </div>

        <button
          type="button"
          class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-3 text-sm font-semibold text-white ring-1 ring-white/15 hover:bg-white/15"
          :disabled="loading"
          @click="fetchClients()"
        >
          Atualizar
        </button>
      </div>

      <p v-if="error" class="mt-5 rounded-xl bg-rose-500/10 px-4 py-3 text-xs text-rose-100 ring-1 ring-rose-500/20">
        {{ error }}
      </p>

      <div class="mt-6 overflow-hidden rounded-2xl ring-1 ring-white/10">
        <div class="grid grid-cols-12 gap-2 bg-black/20 px-5 py-3 text-xs font-semibold text-white/75">
          <div class="col-span-5">Nome</div>
          <div class="col-span-5">E-mail</div>
          <div class="col-span-2 text-right">Saldo</div>
        </div>

        <div v-if="loading" class="px-5 py-6 text-sm text-white/70">Carregando...</div>

        <div v-else-if="clients.length === 0" class="px-5 py-6 text-sm text-white/70">Nenhum cliente encontrado.</div>

        <div v-else class="divide-y divide-white/10">
          <div v-for="c in clients" :key="c.id" class="grid grid-cols-12 gap-2 px-5 py-4 text-sm">
            <div class="col-span-5 font-semibold text-white">{{ c.name }}</div>
            <div class="col-span-5 text-white/75">{{ c.email }}</div>
            <div class="col-span-2 text-right font-semibold" :class="Number(c.balance_cents) < 0 ? 'text-rose-200' : 'text-[#e9c15e]'">
              {{ formatBRL(c.balance_cents) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import axios from 'axios'
import { onMounted, ref } from 'vue'

const props = defineProps({
  user: { type: Object, required: true },
})

const clients = ref([])
const loading = ref(false)
const error = ref('')

function formatBRL(cents) {
  const value = Number(cents || 0) / 100
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

async function fetchClients() {
  error.value = ''
  loading.value = true

  try {
    const { data } = await axios.get('/api/admin/clients', {
      headers: {
        'X-User-Id': props.user.id,
      },
    })

    clients.value = data?.data ?? []
  } catch (e) {
    error.value = e?.response?.data?.message || 'Não foi possível carregar a lista de clientes.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchClients)
</script>
