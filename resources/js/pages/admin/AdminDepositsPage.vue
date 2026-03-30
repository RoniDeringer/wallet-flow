<template>
  <section class="flex flex-1 flex-col px-6 pb-10 pt-6">
    <div class="rounded-3xl bg-white/5 p-8 ring-1 ring-white/10">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h2 class="text-xl font-semibold text-white">Depósitos</h2>
          <p class="mt-2 text-sm text-white/70">Todos os depósitos de todos os clientes (Ledger).</p>
        </div>

        <button
          type="button"
          class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-rose-500 px-5 py-3 text-sm font-semibold text-white ring-1 ring-white/10 hover:bg-rose-400 disabled:opacity-60"
          @click="openResetModal()"
        >
          <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6c0-1.105 3.582-2 8-2s8 .895 8 2-3.582 2-8 2-8-.895-8-2z"
            />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6v6c0 1.105 3.582 2 8 2s8-.895 8-2V6" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12v6c0 1.105 3.582 2 8 2s8-.895 8-2v-6" />
          </svg>
          Limpar dados
        </button>
      </div>

      <!-- Filtros -->
      <div class="mt-6 grid gap-3 rounded-2xl bg-black/20 p-5 ring-1 ring-white/10 sm:grid-cols-5">
        <div class="sm:col-span-2">
          <div class="text-xs font-semibold text-white/70">Cliente</div>
          <select
            v-model="filters.client_id"
            class="mt-2 w-full rounded-xl bg-black/30 pl-3 pr-10 py-2.5 text-sm text-white ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70"
          >
            <option value="">Todos</option>
            <option v-for="c in clients" :key="c.id" :value="String(c.id)">{{ c.name }} ({{ c.email }})</option>
          </select>
        </div>

        <div>
          <div class="text-xs font-semibold text-white/70">Status</div>
          <select
            v-model="filters.status"
            class="mt-2 w-full rounded-xl bg-black/30 pl-3 pr-10 py-2.5 text-sm text-white ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70"
          >
            <option value="">Todos</option>
            <option value="pending">pending</option>
            <option value="posted">posted</option>
            <option value="failed">failed</option>
            <option value="reversed">reversed</option>
          </select>
        </div>

        <div>
          <div class="text-xs font-semibold text-white/70">De</div>
          <input
            v-model="filters.date_from"
            type="date"
            class="mt-2 w-full rounded-xl bg-black/30 px-3 py-2.5 text-sm text-white ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70"
          />
        </div>

        <div>
          <div class="text-xs font-semibold text-white/70">Até</div>
          <input
            v-model="filters.date_to"
            type="date"
            class="mt-2 w-full rounded-xl bg-black/30 px-3 py-2.5 text-sm text-white ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70"
          />
        </div>

        <div class="sm:col-span-5 flex flex-wrap justify-end gap-3">
          <button
            type="button"
            class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-transparent px-4 py-2.5 text-sm font-semibold text-white/85 hover:bg-white/5"
            :disabled="loading"
            @click="clearFilters()"
          >
            Limpar filtros
          </button>
          <button
            type="button"
            class="inline-flex cursor-pointer items-center justify-center rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/15 hover:bg-white/15"
            :disabled="loading"
            @click="applyFilters()"
          >
            Aplicar filtros
          </button>
        </div>
      </div>

      <p v-if="notice" class="mt-5 rounded-xl bg-white/10 px-4 py-3 text-xs text-white/80 ring-1 ring-white/10">
        {{ notice }}
      </p>

      <p v-if="error" class="mt-5 rounded-xl bg-rose-500/10 px-4 py-3 text-xs text-rose-100 ring-1 ring-rose-500/20">
        {{ error }}
      </p>

      <!-- Lista -->
      <div class="mt-6 overflow-hidden rounded-2xl ring-1 ring-white/10">
        <div class="grid grid-cols-12 gap-2 bg-black/20 px-5 py-3 text-xs font-semibold text-white/75">
          <div class="col-span-5">Data</div>
          <div class="col-span-4">Cliente</div>
          <div class="col-span-1 text-center">Status</div>
          <div class="col-span-2 text-right">Valor</div>
        </div>

        <div v-if="loading" class="px-5 py-6 text-sm text-white/70">Carregando...</div>
        <div v-else-if="rows.length === 0" class="px-5 py-6 text-sm text-white/70">Nenhum depósito encontrado.</div>

        <div v-else class="divide-y divide-white/10">
          <div v-for="r in rows" :key="r.id" class="grid grid-cols-12 items-center gap-2 px-5 py-4 text-sm">
            <div class="col-span-5 text-white/80">{{ formatDate(r.created_at) }}</div>
            <div class="col-span-4 text-white/85">
              {{ r.to_user_name || '—' }}
              <div class="text-xs text-white/60">{{ r.to_user_email || '' }}</div>
            </div>
            <div class="col-span-1 text-center">
              <span class="rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-semibold ring-1 ring-white/10">{{ r.status }}</span>
            </div>
            <div class="col-span-2 text-right font-semibold text-[#e9c15e]">{{ formatBRL(r.amount) }}</div>
          </div>
        </div>
      </div>

      <!-- Paginação -->
      <div class="mt-4 flex items-center justify-between text-xs text-white/60">
        <div>Total: {{ meta.total }}</div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="rounded-lg bg-white/10 px-3 py-2 font-semibold text-white/80 ring-1 ring-white/10 hover:bg-white/15 disabled:opacity-60 cursor-pointer"
            :disabled="meta.page <= 1 || loading"
            @click="goTo(meta.page - 1)"
          >
            Anterior
          </button>
          <div>Página {{ meta.page }}</div>
          <button
            type="button"
            class="rounded-lg bg-white/10 px-3 py-2 font-semibold text-white/80 ring-1 ring-white/10 hover:bg-white/15 disabled:opacity-60 cursor-pointer"
            :disabled="(meta.page * meta.per_page) >= meta.total || loading"
            @click="goTo(meta.page + 1)"
          >
            Próxima
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Reset Ledger -->
    <div v-if="resetModalOpen" class="fixed inset-0 z-50">
      <div class="absolute inset-0 bg-black/70" @click="closeResetModal()" />

      <div class="absolute inset-0 flex items-center justify-center px-6">
        <div class="w-full max-w-md rounded-3xl bg-[#021e3d] p-7 ring-1 ring-white/10 shadow-[0_40px_120px_-60px_rgba(0,0,0,0.9)]">
          <h3 class="text-lg font-semibold text-white">Limpar dados</h3>
          <p class="mt-1 text-xs text-white/60">
            Isso remove todas as transações e entries (`ledger_transactions` e `ledger_entries`). Recomendado somente para testes.
          </p>

          <div class="mt-5 rounded-2xl bg-rose-500/10 p-5 text-xs text-rose-100 ring-1 ring-rose-500/20">
            Esta ação não pode ser desfeita.
          </div>

          <div class="mt-6 flex items-center justify-end gap-3">
            <button
              type="button"
              class="inline-flex cursor-pointer items-center justify-center rounded-xl border border-rose-400/60 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50 disabled:opacity-60"
              :disabled="resetSaving"
              @click="closeResetModal()"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="inline-flex cursor-pointer items-center justify-center rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/10 hover:bg-rose-400 disabled:opacity-60"
              :disabled="resetSaving"
              @click="confirmReset()"
            >
              {{ resetSaving ? 'Limpando...' : 'Confirmar limpeza' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import axios from 'axios'
import { onMounted, reactive, ref } from 'vue'
import { toastError, toastSuccess } from '../../lib/alerts'

const props = defineProps({
  user: { type: Object, required: true },
})

const clients = ref([])
const rows = ref([])
const loading = ref(false)
const error = ref('')
const notice = ref('')

const filters = reactive({
  client_id: '',
  status: '',
  date_from: '',
  date_to: '',
})

const meta = reactive({
  page: 1,
  per_page: 25,
  total: 0,
})

const resetModalOpen = ref(false)
const resetSaving = ref(false)

function formatBRL(cents) {
  const value = Number(cents || 0) / 100
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(value) {
  if (!value) return ''
  const dt = new Date(value)
  return dt.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })
}

async function fetchClients() {
  const { data } = await axios.get('/api/admin/clients')
  clients.value = data?.data ?? data ?? []
}

async function fetchRows() {
  loading.value = true
  error.value = ''

  try {
    const params = {
      type: 'deposit',
      status: filters.status || undefined,
      client_id: filters.client_id || undefined,
      date_from: filters.date_from || undefined,
      date_to: filters.date_to || undefined,
      page: meta.page,
      per_page: meta.per_page,
    }

    const { data } = await axios.get('/api/admin/ledger/transactions', {
      params,
    })

    rows.value = data?.data ?? []
    meta.total = Number(data?.meta?.total ?? 0)
    meta.page = Number(data?.meta?.page ?? meta.page)
    meta.per_page = Number(data?.meta?.per_page ?? meta.per_page)
  } catch (e) {
    error.value = e?.response?.data?.message || 'Não foi possível carregar os depósitos.'
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  meta.page = 1
  fetchRows()
}

function clearFilters() {
  filters.client_id = ''
  filters.status = ''
  filters.date_from = ''
  filters.date_to = ''
  meta.page = 1
  fetchRows()
}

function goTo(page) {
  meta.page = page
  fetchRows()
}

function openResetModal() {
  notice.value = ''
  error.value = ''
  resetModalOpen.value = true
}

function closeResetModal() {
  resetSaving.value = false
  resetModalOpen.value = false
}

async function confirmReset() {
  error.value = ''
  notice.value = ''
  resetSaving.value = true

  try {
    const { data } = await axios.post('/api/admin/ledger/reset', { confirm: true })
    const deletedEntries = data?.data?.deleted_entries ?? 0
    const deletedTransactions = data?.data?.deleted_transactions ?? 0
    notice.value = `Dados limpos. Transações removidas: ${deletedTransactions}. Entries removidas: ${deletedEntries}.`
    toastSuccess('Dados limpos', `Transações: ${deletedTransactions} • Entries: ${deletedEntries}`)
    closeResetModal()
    meta.page = 1
    await fetchRows()
  } catch (e) {
    error.value = e?.response?.data?.message || 'Não foi possível limpar a base.'
    toastError('Erro ao limpar', error.value)
  } finally {
    resetSaving.value = false
  }
}

onMounted(async () => {
  await fetchClients()
  await fetchRows()
})
</script>
