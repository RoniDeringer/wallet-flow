<template>
  <section class="flex flex-1 flex-col px-6 pb-10 pt-6">
    <div class="rounded-3xl bg-white/5 p-8 ring-1 ring-white/10">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h2 class="text-xl font-semibold text-white">Lista de clientes</h2>
          <p class="mt-2 text-sm text-white/70">Nome, e-mail e saldo total (Ledger, BRL).</p>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">
          <button
            type="button"
            class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#e9c15e] to-[#c89a2e] px-5 py-3 text-sm font-semibold text-[#032952] shadow-lg shadow-[#e9c15e]/20 ring-1 ring-white/10 hover:brightness-105 disabled:opacity-60"
            :disabled="loading"
            @click="openCreateModal()"
          >
            Cadastrar Cliente
          </button>
          <button
            type="button"
            class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-3 text-sm font-semibold text-white ring-1 ring-white/15 hover:bg-white/15 disabled:opacity-60"
            :disabled="loading"
            @click="fetchClients()"
          >
            Atualizar
          </button>
        </div>
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

    <!-- Modal Cadastrar Cliente -->
    <div v-if="createModalOpen" class="fixed inset-0 z-50">
      <div class="absolute inset-0 bg-black/70" @click="closeCreateModal()" />

      <div class="absolute inset-0 flex items-center justify-center px-6">
        <div class="w-full max-w-md rounded-3xl bg-[#021e3d] p-7 ring-1 ring-white/10 shadow-[0_40px_120px_-60px_rgba(0,0,0,0.9)]">
          <h3 class="text-lg font-semibold text-white">Cadastrar Cliente</h3>
          <p class="mt-1 text-xs text-white/60">Senha padrão: <span class="font-semibold text-[#e9c15e]">12345</span></p>

          <form class="mt-5 space-y-4" @submit.prevent="submitCreate()">
            <div>
              <label class="mb-1 block text-xs font-medium text-white/80" for="create-name">Nome</label>
              <input
                id="create-name"
                v-model.trim="createForm.name"
                class="w-full rounded-xl bg-black/30 px-4 py-3 text-sm text-white placeholder:text-white/30 ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70"
                type="text"
                placeholder="Nome do cliente"
                :disabled="createSaving"
              />
              <p v-if="createErrors.name" class="mt-1 text-xs text-rose-200">{{ createErrors.name }}</p>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-white/80" for="create-email">E-mail</label>
              <input
                id="create-email"
                v-model.trim="createForm.email"
                class="w-full rounded-xl bg-black/30 px-4 py-3 text-sm text-white placeholder:text-white/30 ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70"
                type="email"
                placeholder="cliente@exemplo.com"
                autocomplete="off"
                :disabled="createSaving"
              />
              <p v-if="createErrors.email" class="mt-1 text-xs text-rose-200">{{ createErrors.email }}</p>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-white/80" for="create-username">Username</label>
              <input
                id="create-username"
                v-model.trim="createForm.username"
                class="w-full rounded-xl bg-black/30 px-4 py-3 text-sm text-white placeholder:text-white/30 ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70"
                type="text"
                placeholder="sem espaços, sem caracteres especiais"
                autocomplete="off"
                :disabled="createSaving"
              />
              <p v-if="createErrors.username" class="mt-1 text-xs text-rose-200">{{ createErrors.username }}</p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-xl border border-rose-400/60 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50 disabled:opacity-60"
                :disabled="createSaving"
                @click="closeCreateModal()"
              >
                Cancelar
              </button>
              <button
                type="submit"
                class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#e9c15e] to-[#c89a2e] px-4 py-2.5 text-sm font-semibold text-[#032952] ring-1 ring-white/10 hover:brightness-105 disabled:opacity-60"
                :disabled="createSaving"
              >
                {{ createSaving ? 'Cadastrando...' : 'Cadastrar' }}
              </button>
            </div>
          </form>
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
const loading = ref(false)
const error = ref('')

const createModalOpen = ref(false)
const createSaving = ref(false)

const createForm = reactive({
  name: '',
  email: '',
  username: '',
})

const createErrors = reactive({
  name: '',
  email: '',
  username: '',
})

function formatBRL(cents) {
  const value = Number(cents || 0) / 100
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function resetCreateErrors() {
  createErrors.name = ''
  createErrors.email = ''
  createErrors.username = ''
}

function openCreateModal() {
  resetCreateErrors()
  createForm.name = ''
  createForm.email = ''
  createForm.username = ''
  createModalOpen.value = true
}

function closeCreateModal() {
  resetCreateErrors()
  createForm.name = ''
  createForm.email = ''
  createForm.username = ''
  createSaving.value = false
  createModalOpen.value = false
}

function validateCreate() {
  resetCreateErrors()

  if (!createForm.name) createErrors.name = 'Informe o nome.'

  if (!createForm.email) createErrors.email = 'Informe o e-mail.'
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(createForm.email)) createErrors.email = 'Informe um e-mail válido.'

  if (!createForm.username) createErrors.username = 'Informe o username.'
  else if (!/^[A-Za-z0-9_]+$/.test(createForm.username)) createErrors.username = 'Use apenas letras, números e underscore (_).'

  return !createErrors.name && !createErrors.email && !createErrors.username
}

async function fetchClients() {
  error.value = ''
  loading.value = true

  try {
    const { data } = await axios.get('/api/admin/clients')

    clients.value = data?.data ?? []
  } catch (e) {
    error.value = e?.response?.data?.message || 'Não foi possível carregar a lista de clientes.'
  } finally {
    loading.value = false
  }
}

async function submitCreate() {
  if (!validateCreate()) return

  createSaving.value = true

  try {
    await axios.post('/api/admin/clients', { name: createForm.name, email: createForm.email, username: createForm.username })

    toastSuccess('Cliente cadastrado', 'Senha padrão: 12345')
    closeCreateModal()
    await fetchClients()
  } catch (e) {
    if (e?.response?.status === 422) {
      const errs = e?.response?.data?.errors || {}
      if (errs.name?.[0]) createErrors.name = errs.name[0]
      if (errs.email?.[0]) createErrors.email = errs.email[0]
      if (errs.username?.[0]) createErrors.username = errs.username[0]

      toastError('Erro ao cadastrar', e?.response?.data?.message || 'Verifique os campos.')
      return
    }

    toastError('Erro ao cadastrar', e?.response?.data?.message || 'Erro inesperado.')
  } finally {
    createSaving.value = false
  }
}

onMounted(fetchClients)
</script>
