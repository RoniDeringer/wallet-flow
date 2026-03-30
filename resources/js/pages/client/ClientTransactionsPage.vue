<template>
  <section class="flex flex-1 flex-col px-6 pb-10 pt-6">
    <div class="rounded-3xl bg-white/5 p-8 ring-1 ring-white/10">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h2 class="text-xl font-semibold text-white">Transações</h2>
          <p class="mt-2 text-sm text-white/70">Todas as suas transações (Ledger, BRL).</p>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">
          <a
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/10 px-4 py-3 text-sm font-semibold text-white ring-1 ring-white/15 hover:bg-white/15"
            href="http://127.0.0.1:15672"
            target="_blank"
            rel="noopener noreferrer"
            title="Abrir RabbitMQ"
          >
            RabbitMQ
            <svg class="size-4 text-[#e9c15e]" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7v7m0-7L10 14" />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 14v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6"
              />
            </svg>
          </a>

          <button
            type="button"
            class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#e9c15e] to-[#c89a2e] px-5 py-3 text-sm font-semibold text-[#032952] shadow-lg shadow-[#e9c15e]/20 ring-1 ring-white/10 hover:brightness-105"
            @click="openTransferModal()"
          >
            Realizar transferência
          </button>
        </div>
      </div>

      <!-- Overview -->
      <div class="mt-6 grid gap-3 sm:grid-cols-5">
        <div class="rounded-2xl bg-black/20 p-5 ring-1 ring-white/10">
          <div class="text-xs text-white/60">Saldo total</div>
          <div class="mt-2 text-lg font-semibold" :class="overview.balance_cents < 0 ? 'text-rose-200' : 'text-white'">
            {{ formatBRL(overview.balance_cents) }}
          </div>
        </div>
        <div class="rounded-2xl bg-black/20 p-5 ring-1 ring-white/10">
          <div class="text-xs text-white/60">Depósitos (total)</div>
          <div class="mt-2 text-lg font-semibold text-[#e9c15e]">{{ formatBRL(overview.deposits_total_cents) }}</div>
        </div>
        <div class="rounded-2xl bg-black/20 p-5 ring-1 ring-white/10">
          <div class="text-xs text-white/60">Transferências recebidas</div>
          <div class="mt-2 text-lg font-semibold text-[#e9c15e]">{{ formatBRL(overview.transfers_received_total_cents) }}</div>
        </div>
        <div class="rounded-2xl bg-black/20 p-5 ring-1 ring-white/10">
          <div class="text-xs text-white/60">Transferências enviadas</div>
          <div class="mt-2 text-lg font-semibold text-rose-200">{{ formatBRL(overview.transfers_sent_total_cents) }}</div>
        </div>
        <div class="rounded-2xl bg-black/20 p-5 ring-1 ring-white/10">
          <div class="text-xs text-white/60">Transferências pendentes</div>
          <div class="mt-2 text-lg font-semibold" :class="overview.pending_transfers_count > 0 ? 'text-rose-200' : 'text-white/80'">
            {{ overview.pending_transfers_count }}
          </div>
        </div>
      </div>

      <p v-if="error" class="mt-5 rounded-xl bg-rose-500/10 px-4 py-3 text-xs text-rose-100 ring-1 ring-rose-500/20">
        {{ error }}
      </p>

      <div class="mt-6 overflow-hidden rounded-2xl ring-1 ring-white/10">
        <div class="grid grid-cols-12 gap-2 bg-black/20 px-5 py-3 text-xs font-semibold text-white/75">
          <div class="col-span-5">Data</div>
          <div class="col-span-3">Tipo</div>
          <div class="col-span-2 text-right">Valor</div>
          <div class="col-span-2 text-right">Ações</div>
        </div>

        <div v-if="loading" class="px-5 py-6 text-sm text-white/70">Carregando...</div>
        <div v-else-if="transactions.length === 0" class="px-5 py-6 text-sm text-white/70">Nenhuma transação encontrada.</div>

        <div v-else class="divide-y divide-white/10">
          <div v-for="t in transactions" :key="t.id" class="grid grid-cols-12 items-center gap-2 px-5 py-4 text-sm">
            <div class="col-span-5 text-white/80">
              {{ formatDate(t.created_at) }}
              <span class="ml-2 rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-semibold ring-1 ring-white/10">
                {{ t.status }}
              </span>
            </div>
            <div class="col-span-3 font-semibold text-white/85">{{ t.type }}</div>
            <div class="col-span-2 text-right font-semibold" :class="Number(t.amount_signed) < 0 ? 'text-rose-200' : 'text-[#e9c15e]'">
              {{ formatSignedBRL(t.amount_signed) }}
            </div>
            <div class="col-span-2 flex justify-end">
              <button
                v-if="canRollback(t)"
                type="button"
                class="inline-flex items-center justify-center rounded-xl bg-white/10 px-3 py-2 text-xs font-semibold text-white/80 ring-1 ring-white/15 hover:bg-white/15"
                title="Fazer rollback"
                aria-label="Fazer rollback"
                @click="openRollbackModal(t)"
              >
                <svg class="size-4 text-[#e9c15e]" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 1 0 3-6.708M3 4v5h5" />
                </svg>
              </button>
              <span v-else class="text-xs text-white/35">—</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Transferência -->
    <div v-if="transferModalOpen" class="fixed inset-0 z-50">
      <div class="absolute inset-0 bg-black/70" @click="closeTransferModal()" />

      <div class="absolute inset-0 flex items-center justify-center px-6">
        <div class="w-full max-w-md rounded-3xl bg-[#021e3d] p-7 ring-1 ring-white/10 shadow-[0_40px_120px_-60px_rgba(0,0,0,0.9)]">
          <h3 class="text-lg font-semibold text-white">Realizar transferência</h3>
          <p class="mt-1 text-xs text-white/60">Informe o e-mail do destinatário e o valor.</p>

          <div class="mt-5 space-y-4">
            <div>
              <label class="mb-1 block text-xs font-medium text-white/80" for="recipient">E-mail do destinatário</label>
              <input
                id="recipient"
                v-model.trim="recipientEmail"
                class="w-full rounded-xl bg-black/30 px-4 py-3 text-sm text-white placeholder:text-white/30 ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70"
                type="email"
                autocomplete="off"
                placeholder="destinatario@exemplo.com"
                :disabled="saving"
              />
              <p v-if="recipientError" class="mt-1 text-xs text-rose-200">{{ recipientError }}</p>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-white/80" for="amount">Valor</label>
              <input
                id="amount"
                v-model="amount"
                class="w-full rounded-xl bg-black/30 px-4 py-3 text-sm text-white placeholder:text-white/30 ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70"
                type="number"
                min="0"
                step="0.01"
                placeholder="0.00"
                :disabled="saving"
                @keydown.enter.prevent="confirmTransfer()"
              />
              <p v-if="amountError" class="mt-1 text-xs text-rose-200">{{ amountError }}</p>
            </div>
          </div>

          <div class="mt-6 flex items-center justify-end gap-3">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl border border-rose-400/60 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50 disabled:opacity-60"
              :disabled="saving"
              @click="closeTransferModal()"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#e9c15e] to-[#c89a2e] px-4 py-2.5 text-sm font-semibold text-[#032952] ring-1 ring-white/10 hover:brightness-105 disabled:opacity-60"
              :disabled="saving"
              @click="confirmTransfer()"
            >
              {{ saving ? 'Transferindo...' : 'Transferir' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Rollback -->
    <div v-if="rollbackModalOpen" class="fixed inset-0 z-50">
      <div class="absolute inset-0 bg-black/70" @click="closeRollbackModal()" />

      <div class="absolute inset-0 flex items-center justify-center px-6">
        <div class="w-full max-w-md rounded-3xl bg-[#021e3d] p-7 ring-1 ring-white/10 shadow-[0_40px_120px_-60px_rgba(0,0,0,0.9)]">
          <h3 class="text-lg font-semibold text-white">Confirmar rollback</h3>
          <p class="mt-1 text-xs text-white/60">Regras: não pode gerar saldo negativo e não pode existir transferência pendente.</p>

          <div v-if="rollbackTx" class="mt-5 rounded-2xl bg-black/20 p-5 ring-1 ring-white/10">
            <div class="text-xs text-white/60">Transação</div>
            <div class="mt-2 text-sm font-semibold text-white">{{ rollbackTx.type }} • {{ rollbackTx.status }}</div>
            <div class="mt-1 text-xs text-white/70">{{ formatDate(rollbackTx.created_at) }}</div>
            <div class="mt-3 text-sm font-semibold text-[#e9c15e]">{{ formatSignedBRL(rollbackTx.amount_signed) }}</div>
          </div>

          <p
            v-if="overview.pending_transfers_count > 0"
            class="mt-5 rounded-xl bg-rose-500/10 px-4 py-3 text-xs text-rose-100 ring-1 ring-rose-500/20"
          >
            Você possui transferência pendente. Finalize/cancele antes de solicitar rollback.
          </p>

          <p
            v-else-if="wouldBecomeNegative"
            class="mt-5 rounded-xl bg-rose-500/10 px-4 py-3 text-xs text-rose-100 ring-1 ring-rose-500/20"
          >
            Não é possível: este rollback deixaria seu saldo negativo.
          </p>

          <p v-if="rollbackError" class="mt-5 rounded-xl bg-rose-500/10 px-4 py-3 text-xs text-rose-100 ring-1 ring-rose-500/20">
            {{ rollbackError }}
          </p>

          <div class="mt-6 flex items-center justify-end gap-3">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl border border-rose-400/60 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50 disabled:opacity-60"
              :disabled="rollbackSaving"
              @click="closeRollbackModal()"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/10 hover:bg-rose-400 disabled:opacity-60"
              :disabled="rollbackSaving || overview.pending_transfers_count > 0 || wouldBecomeNegative"
              @click="confirmRollback()"
            >
              {{ rollbackSaving ? 'Solicitando...' : 'Fazer rollback' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import axios from 'axios'
import { computed, onMounted, reactive, ref } from 'vue'
import { toastError, toastSuccess } from '../../lib/alerts'

const props = defineProps({
  user: { type: Object, required: true },
})

const overview = reactive({
  balance_cents: 0,
  deposits_total_cents: 0,
  transfers_received_total_cents: 0,
  transfers_sent_total_cents: 0,
  pending_transfers_count: 0,
})

const transactions = ref([])
const loading = ref(false)
const error = ref('')

// Transfer modal
const transferModalOpen = ref(false)
const recipientEmail = ref('')
const amount = ref('')
const recipientError = ref('')
const amountError = ref('')
const saving = ref(false)

// Rollback modal
const rollbackModalOpen = ref(false)
const rollbackTx = ref(null)
const rollbackSaving = ref(false)
const rollbackError = ref('')

const wouldBecomeNegative = computed(() => {
  if (!rollbackTx.value) return false
  const signed = Number(rollbackTx.value.amount_signed || 0)
  if (signed <= 0) return false
  const newBalance = Number(overview.balance_cents || 0) - signed
  return newBalance < 0
})

function canRollback(tx) {
  return tx?.status === 'posted' && (tx?.type === 'deposit' || tx?.type === 'transfer')
}

function formatBRL(cents) {
  const value = Number(cents || 0) / 100
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatSignedBRL(signedCents) {
  const cents = Number(signedCents || 0)
  const sign = cents < 0 ? '-' : ''
  const value = Math.abs(cents) / 100
  return sign + value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(value) {
  if (!value) return ''
  const dt = new Date(value)
  return dt.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })
}

async function fetchOverview() {
  try {
    const { data } = await axios.get('/api/me/overview')
    const payload = data?.data ?? {}
    overview.balance_cents = Number(payload.balance_cents ?? 0)
    overview.deposits_total_cents = Number(payload.deposits_total_cents ?? 0)
    overview.transfers_received_total_cents = Number(payload.transfers_received_total_cents ?? 0)
    overview.transfers_sent_total_cents = Number(payload.transfers_sent_total_cents ?? 0)
    overview.pending_transfers_count = Number(payload.pending_transfers_count ?? 0)
  } catch {
    // keep defaults
  }
}

async function fetchTransactions() {
  error.value = ''
  loading.value = true

  try {
    const { data } = await axios.get('/api/me/transactions')
    transactions.value = data?.data ?? []
  } catch (e) {
    error.value = e?.response?.data?.message || 'Não foi possível carregar suas transações.'
  } finally {
    loading.value = false
  }
}

function openTransferModal() {
  recipientEmail.value = ''
  amount.value = ''
  recipientError.value = ''
  amountError.value = ''
  transferModalOpen.value = true
}

function closeTransferModal() {
  recipientEmail.value = ''
  amount.value = ''
  recipientError.value = ''
  amountError.value = ''
  saving.value = false
  transferModalOpen.value = false
}

function validateTransfer() {
  recipientError.value = ''
  amountError.value = ''

  if (!recipientEmail.value) recipientError.value = 'Informe o e-mail do destinatário.'
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(recipientEmail.value)) recipientError.value = 'Informe um e-mail válido.'

  const value = Number(amount.value)
  if (!amount.value) amountError.value = 'Informe um valor.'
  else if (Number.isNaN(value)) amountError.value = 'Valor inválido.'
  else if (value < 0) amountError.value = 'O valor não pode ser negativo.'
  else if (value === 0) amountError.value = 'O valor deve ser maior que zero.'

  return !recipientError.value && !amountError.value
}

async function confirmTransfer() {
  error.value = ''

  if (!validateTransfer()) return

  saving.value = true

  try {
    await axios.post(
      '/api/me/transfers',
      { recipient_email: recipientEmail.value, amount: amount.value }
    )

    closeTransferModal()
    toastSuccess('Transferência enviada', 'Sua transferência foi registrada para processamento.')
    await Promise.all([fetchTransactions(), fetchOverview()])
  } catch (e) {
    if (e?.response?.status === 422) {
      const errs = e?.response?.data?.errors || {}
      if (errs.recipient_email?.[0]) recipientError.value = errs.recipient_email[0]
      if (errs.amount?.[0]) amountError.value = errs.amount[0]
      if (!recipientError.value && !amountError.value) error.value = e?.response?.data?.message || 'Falha ao transferir.'
      toastError('Erro na transferência', error.value || 'Verifique os campos.')
    } else {
      error.value = e?.response?.data?.message || 'Erro ao transferir.'
      toastError('Erro na transferência', error.value)
    }
  } finally {
    saving.value = false
  }
}

function openRollbackModal(tx) {
  rollbackError.value = ''
  rollbackTx.value = tx
  rollbackModalOpen.value = true
}

function closeRollbackModal() {
  rollbackModalOpen.value = false
  rollbackTx.value = null
  rollbackError.value = ''
  rollbackSaving.value = false
}

async function confirmRollback() {
  rollbackError.value = ''

  if (!rollbackTx.value?.id) return

  rollbackSaving.value = true

  try {
    await axios.post(`/api/me/transactions/${rollbackTx.value.id}/reversal`)

    closeRollbackModal()
    toastSuccess('Rollback solicitado', 'A reversão foi enviada para processamento.')
    await Promise.all([fetchTransactions(), fetchOverview()])
  } catch (e) {
    rollbackError.value = e?.response?.data?.message || 'Erro ao solicitar rollback.'
    toastError('Erro no rollback', rollbackError.value)
  } finally {
    rollbackSaving.value = false
  }
}

onMounted(async () => {
  await Promise.all([fetchOverview(), fetchTransactions()])
})
</script>
