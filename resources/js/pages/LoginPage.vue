<template>
  <div class="flex flex-1 items-center justify-center px-6 py-12">
    <div class="w-full max-w-md">
      <div class="mb-6 text-center">
        <div class="mx-auto mb-4 grid size-14 place-items-center rounded-2xl bg-white/10 ring-1 ring-white/15">
          <svg class="size-7 text-[#e9c15e]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 6v12m6-6H6"
            />
          </svg>
        </div>
        <h1 class="text-3xl font-semibold tracking-tight text-white">Entrar</h1>
        <p class="mt-2 text-sm text-white/70">Acesse sua carteira com segurança</p>
      </div>

      <div class="rounded-3xl bg-white/5 p-7 ring-1 ring-white/10 shadow-[0_30px_90px_-40px_rgba(0,0,0,0.8)]">
        <form class="space-y-4" @submit.prevent="submit">
          <div>
            <label class="mb-1 block text-xs font-medium text-white/80" for="email">E-mail</label>
            <input
              id="email"
              v-model.trim="email"
              class="w-full rounded-xl bg-black/30 px-4 py-3 text-sm text-white placeholder:text-white/30 ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70"
              type="email"
              autocomplete="email"
              placeholder="voce@exemplo.com"
              :disabled="loading"
              @blur="touched.email = true"
            />
            <p v-if="touched.email && errors.email" class="mt-1 text-xs text-rose-200">{{ errors.email }}</p>
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium text-white/80" for="password">Senha</label>
            <div class="relative">
              <input
                id="password"
                v-model="password"
                class="w-full rounded-xl bg-black/30 px-4 py-3 pr-12 text-sm text-white placeholder:text-white/30 ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                placeholder="•••••"
                :disabled="loading"
                @blur="touched.password = true"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 mr-2 inline-flex items-center rounded-lg px-2 text-xs text-white/70 hover:bg-white/10"
                :disabled="loading"
                @click="showPassword = !showPassword"
              >
                {{ showPassword ? 'Ocultar' : 'Mostrar' }}
              </button>
            </div>
            <p v-if="touched.password && errors.password" class="mt-1 text-xs text-rose-200">{{ errors.password }}</p>
          </div>

          <p v-if="errors.general" class="rounded-xl bg-rose-500/10 px-4 py-3 text-xs text-rose-100 ring-1 ring-rose-500/20">
            {{ errors.general }}
          </p>

          <button
            type="submit"
            class="mt-2 inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-[#e9c15e] to-[#c89a2e] px-5 py-3 text-sm font-semibold text-[#032952] shadow-lg shadow-[#e9c15e]/20 ring-1 ring-white/10 hover:brightness-105 disabled:opacity-60"
            :disabled="loading"
          >
            <span v-if="!loading">Entrar</span>
            <span v-else>Validando...</span>
          </button>

          <div class="pt-3 text-center text-xs text-white/60">
            Dica: use <span class="font-semibold text-[#e9c15e]">admin@admin.com</span> / <span class="font-semibold text-[#e9c15e]">12345</span>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { reactive, ref } from 'vue'

const emit = defineEmits(['loggedIn'])

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)

const touched = reactive({
  email: false,
  password: false,
})

const errors = reactive({
  email: '',
  password: '',
  general: '',
})

function resetErrors() {
  errors.email = ''
  errors.password = ''
  errors.general = ''
}

function validate() {
  resetErrors()

  if (!email.value) errors.email = 'Informe seu e-mail.'
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) errors.email = 'Informe um e-mail válido.'

  if (!password.value) errors.password = 'Informe sua senha.'

  return !errors.email && !errors.password
}

async function submit() {
  touched.email = true
  touched.password = true

  if (!validate()) return

  loading.value = true

  try {
    const { data } = await axios.post('/api/login', {
      email: email.value,
      password: password.value,
    })

    emit('loggedIn', data.user)
  } catch (error) {
    if (error?.response?.status === 422) {
      const message = error?.response?.data?.message || 'Não foi possível autenticar.'
      const fieldErrors = error?.response?.data?.errors || {}

      errors.general = message
      if (fieldErrors.email?.[0]) errors.email = fieldErrors.email[0]
      if (fieldErrors.password?.[0]) errors.password = fieldErrors.password[0]
      if (!errors.email && !errors.password) errors.general = 'E-mail ou senha inválidos.'
    } else {
      errors.general = 'Erro inesperado. Tente novamente.'
    }
  } finally {
    loading.value = false
  }
}
</script>

