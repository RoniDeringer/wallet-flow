<template>
  <section class="flex flex-1 flex-col px-6 pb-10 pt-6">
    <div class="rounded-3xl bg-white/5 p-8 ring-1 ring-white/10">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h2 class="text-xl font-semibold text-white">Depósitos</h2>
          <p class="mt-2 text-sm text-white/70">Últimos depósitos (data e valor).</p>
        </div>

        <button
          type="button"
          class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#e9c15e] to-[#c89a2e] px-5 py-3 text-sm font-semibold text-[#032952] shadow-lg shadow-[#e9c15e]/20 ring-1 ring-white/10 hover:brightness-105"
          @click="openModal()"
        >
          Realizar depósito
        </button>
      </div>

      <p v-if="error" class="mt-5 rounded-xl bg-rose-500/10 px-4 py-3 text-xs text-rose-100 ring-1 ring-rose-500/20">
        {{ error }}
      </p>

      <div class="mt-6 overflow-hidden rounded-2xl ring-1 ring-white/10">
        <div class="grid grid-cols-12 gap-2 bg-black/20 px-5 py-3 text-xs font-semibold text-white/75">
          <div class="col-span-7">Data</div>
          <div class="col-span-5 text-right">Valor</div>
        </div>

        <div v-if="loading" class="px-5 py-6 text-sm text-white/70">Carregando...</div>
        <div v-else-if="deposits.length === 0" class="px-5 py-6 text-sm text-white/70">Nenhum depósito encontrado.</div>

        <div v-else class="divide-y divide-white/10">
          <div v-for="d in deposits" :key="d.id" class="grid grid-cols-12 gap-2 px-5 py-4 text-sm">
            <div class="col-span-7 text-white/80">
              {{ formatDate(d.created_at) }}
              <span class="ml-2 rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-semibold ring-1 ring-white/10">
                {{ d.status }}
              </span>
            </div>
            <div class="col-span-5 text-right font-semibold text-[#e9c15e]">
              {{ formatBRL(d.amount) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50">
      <div class="absolute inset-0 bg-black/70" @click="closeModal()" />

      <div class="absolute inset-0 flex items-center justify-center px-6">
        <div class="w-full max-w-md rounded-3xl bg-[#021e3d] p-7 ring-1 ring-white/10 shadow-[0_40px_120px_-60px_rgba(0,0,0,0.9)]">
          <h3 class="text-lg font-semibold text-white">Realizar depósito</h3>
          <p class="mt-1 text-xs text-white/60">Informe o valor (não pode ser negativo).</p>

          <div class="mt-5">
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
              @keydown.enter.prevent="addDeposit()"
            />
            <p v-if="amountError" class="mt-1 text-xs text-rose-200">{{ amountError }}</p>
          </div>

          <div class="mt-6 flex items-center justify-end gap-3">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl border border-rose-400/60 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50 disabled:opacity-60"
              :disabled="saving"
              @click="closeModal()"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#e9c15e] to-[#c89a2e] px-4 py-2.5 text-sm font-semibold text-[#032952] ring-1 ring-white/10 hover:brightness-105 disabled:opacity-60"
              :disabled="saving"
              @click="addDeposit()"
            >
              {{ saving ? 'Adicionando...' : 'Adicionar' }}
            </button>
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

const deposits = ref([])
const loading = ref(false)
const error = ref('')

const modalOpen = ref(false)
const amount = ref('')
const amountError = ref('')
const saving = ref(false)

function formatBRL(cents) {
  const value = Number(cents || 0) / 100
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(value) {
  if (!value) return ''
  const dt = new Date(value)
  return dt.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })
}

async function fetchDeposits() {
  error.value = ''
  loading.value = true

  try {
    const { data } = await axios.get('/api/me/deposits', {
      headers: { 'X-User-Id': props.user.id },
    })
    deposits.value = data?.data ?? []
  } catch (e) {
    error.value = e?.response?.data?.message || 'Não foi possível carregar seus depósitos.'
  } finally {
    loading.value = false
  }
}

function openModal() {
  amount.value = ''
  amountError.value = ''
  modalOpen.value = true
}

function closeModal() {
  modalOpen.value = false
}

function validateAmount() {
  amountError.value = ''

  const value = Number(amount.value)
  if (!amount.value) amountError.value = 'Informe um valor.'
  else if (Number.isNaN(value)) amountError.value = 'Valor inválido.'
  else if (value < 0) amountError.value = 'O valor não pode ser negativo.'
  else if (value === 0) amountError.value = 'O valor deve ser maior que zero.'

  return !amountError.value
}

async function addDeposit() {
  if (!validateAmount()) return

  saving.value = true

  try {
    await axios.post(
      '/api/me/deposits',
      { amount: amount.value },
      { headers: { 'X-User-Id': props.user.id } }
    )
    closeModal()
    await fetchDeposits()
  } catch (e) {
    if (e?.response?.status === 422) {
      amountError.value = e?.response?.data?.errors?.amount?.[0] || 'Não foi possível adicionar o depósito.'
    } else {
      error.value = e?.response?.data?.message || 'Erro ao adicionar depósito.'
    }
  } finally {
    saving.value = false
  }
}

onMounted(fetchDeposits)
</script>

