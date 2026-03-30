<template>
  <section class="flex flex-1 flex-col px-6 pb-10 pt-6">
    <div class="rounded-3xl bg-white/5 p-8 ring-1 ring-white/10">
      <h2 class="text-xl font-semibold text-white">Minha carteira</h2>
      <p class="mt-2 text-sm text-white/70">Resumo da sua conta e saldo (calculado via Ledger).</p>

      <div class="mt-6 grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl bg-black/20 p-5 ring-1 ring-white/10">
          <div class="text-xs text-white/60">Saldo atual</div>
          <div class="mt-2 text-2xl font-semibold" :class="balanceCents < 0 ? 'text-rose-200' : 'text-white'">
            {{ formatBRL(balanceCents) }}
          </div>
          <div class="mt-1 text-xs text-white/60">Ledger (BRL)</div>
        </div>

        <div class="rounded-2xl bg-black/20 p-5 ring-1 ring-white/10">
          <div class="text-xs text-white/60">Conta</div>
          <div class="mt-2 text-sm font-semibold text-white">{{ user.email }}</div>
          <div class="mt-1 text-xs text-white/60">BRL</div>
        </div>

        <div class="rounded-2xl bg-black/20 p-5 ring-1 ring-white/10">
          <div class="text-xs text-white/60">Ação rápida</div>
          <div class="mt-2 text-sm font-semibold text-white">Depósitos</div>
          <div class="mt-1 text-xs text-white/60">Menu à esquerda</div>
        </div>
      </div>

      <p v-if="error" class="mt-5 rounded-xl bg-rose-500/10 px-4 py-3 text-xs text-rose-100 ring-1 ring-rose-500/20">
        {{ error }}
      </p>
    </div>
  </section>
</template>

<script setup>
import axios from 'axios'
import { onMounted, ref } from 'vue'

const props = defineProps({
  user: { type: Object, required: true },
})

const balanceCents = ref(0)
const error = ref('')

function formatBRL(cents) {
  const value = Number(cents || 0) / 100
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

async function fetchBalance() {
  error.value = ''

  try {
    const { data } = await axios.get('/api/me/wallet')
    balanceCents.value = Number(data?.data?.balance_cents ?? 0)
  } catch (e) {
    error.value = e?.response?.data?.message || 'Não foi possível carregar seu saldo.'
  }
}

onMounted(fetchBalance)
</script>
