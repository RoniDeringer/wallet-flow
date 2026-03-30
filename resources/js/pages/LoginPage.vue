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
              d="M12 3l8 4v10l-8 4-8-4V7l8-4z"
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
                class="absolute inset-y-0 right-0 mr-2 inline-flex items-center rounded-lg px-2 text-xs text-white/70 hover:bg-white/10 cursor-pointer"
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
            class="mt-2 inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-[#e9c15e] to-[#c89a2e] px-5 py-3 text-sm font-semibold text-[#032952] shadow-lg shadow-[#e9c15e]/20 ring-1 ring-white/10 hover:brightness-105 disabled:opacity-60 cursor-pointer"
            :disabled="loading"
          >
            <span v-if="!loading">Entrar</span>
            <span v-else>Validando...</span>
          </button>

          <div class="pt-3 text-center text-xs text-white/60">
            Dica: use <span class="font-semibold text-[#e9c15e]">admin@admin.com</span> / <span class="font-semibold text-[#e9c15e]">12345</span>
          </div>

          <div class="pt-1 text-center text-xs text-white/60">
            Não tem conta?
            <button type="button" class="font-semibold text-[#e9c15e] hover:underline" @click="openRegisterModal()">Criar cadastro</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Cadastro -->
  <div v-if="registerModalOpen" class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/70" @click="closeRegisterModal()" />

    <div class="absolute inset-0 flex items-center justify-center px-6">
      <div class="w-full max-w-md rounded-3xl bg-[#021e3d] p-7 ring-1 ring-white/10 shadow-[0_40px_120px_-60px_rgba(0,0,0,0.9)]">
        <h3 class="text-lg font-semibold text-white">Criar cadastro</h3>
        <p class="mt-1 text-xs text-white/60">Cliente • senha mínima 5 caracteres</p>

        <form class="mt-5 space-y-4" @submit.prevent="submitRegister()">
          <div>
            <label class="mb-1 block text-xs font-medium text-white/80" for="reg-name">Nome</label>
            <input
              id="reg-name"
              v-model.trim="registerForm.name"
              class="w-full rounded-xl bg-black/30 px-4 py-3 text-sm text-white placeholder:text-white/30 ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70"
              type="text"
              placeholder="Seu nome"
              :disabled="registerSaving"
            />
            <p v-if="registerErrors.name" class="mt-1 text-xs text-rose-200">{{ registerErrors.name }}</p>
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium text-white/80" for="reg-email">E-mail</label>
            <input
              id="reg-email"
              v-model.trim="registerForm.email"
              class="w-full rounded-xl bg-black/30 px-4 py-3 text-sm text-white placeholder:text-white/30 ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70"
              type="email"
              placeholder="voce@exemplo.com"
              autocomplete="off"
              :disabled="registerSaving"
            />
            <p v-if="registerErrors.email" class="mt-1 text-xs text-rose-200">{{ registerErrors.email }}</p>
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium text-white/80" for="reg-username">Username</label>
            <input
              id="reg-username"
              v-model.trim="registerForm.username"
              class="w-full rounded-xl bg-black/30 px-4 py-3 text-sm text-white placeholder:text-white/30 ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70"
              type="text"
              placeholder="sem espaços, sem caracteres especiais"
              autocomplete="off"
              :disabled="registerSaving"
            />
            <p v-if="registerErrors.username" class="mt-1 text-xs text-rose-200">{{ registerErrors.username }}</p>
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium text-white/80" for="reg-pass">Senha</label>
            <input
              id="reg-pass"
              v-model="registerForm.password"
              class="w-full rounded-xl bg-black/30 px-4 py-3 text-sm text-white placeholder:text-white/30 ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70"
              type="password"
              placeholder="•••••"
              autocomplete="new-password"
              :disabled="registerSaving"
            />
            <p v-if="registerErrors.password" class="mt-1 text-xs text-rose-200">{{ registerErrors.password }}</p>
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium text-white/80" for="reg-pass2">Confirmar senha</label>
            <input
              id="reg-pass2"
              v-model="registerForm.password_confirmation"
              class="w-full rounded-xl bg-black/30 px-4 py-3 text-sm text-white placeholder:text-white/30 ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70"
              type="password"
              placeholder="•••••"
              autocomplete="new-password"
              :disabled="registerSaving"
            />
            <p v-if="registerErrors.password_confirmation" class="mt-1 text-xs text-rose-200">{{ registerErrors.password_confirmation }}</p>
          </div>

          <div class="mt-6 flex items-center justify-end gap-3">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl border border-rose-400/60 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50 disabled:opacity-60"
              :disabled="registerSaving"
              @click="closeRegisterModal()"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#e9c15e] to-[#c89a2e] px-4 py-2.5 text-sm font-semibold text-[#032952] ring-1 ring-white/10 hover:brightness-105 disabled:opacity-60"
              :disabled="registerSaving"
            >
              {{ registerSaving ? 'Criando...' : 'Criar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { reactive, ref } from 'vue'
import { toastError, toastSuccess } from '../lib/alerts'

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

const registerModalOpen = ref(false)
const registerSaving = ref(false)

const registerForm = reactive({
  name: '',
  email: '',
  username: '',
  password: '',
  password_confirmation: '',
})

const registerErrors = reactive({
  name: '',
  email: '',
  username: '',
  password: '',
  password_confirmation: '',
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

function resetRegisterErrors() {
  registerErrors.name = ''
  registerErrors.email = ''
  registerErrors.username = ''
  registerErrors.password = ''
  registerErrors.password_confirmation = ''
}

function openRegisterModal() {
  resetRegisterErrors()
  registerForm.name = ''
  registerForm.email = ''
  registerForm.username = ''
  registerForm.password = ''
  registerForm.password_confirmation = ''
  registerModalOpen.value = true
}

function closeRegisterModal() {
  resetRegisterErrors()
  registerForm.name = ''
  registerForm.email = ''
  registerForm.username = ''
  registerForm.password = ''
  registerForm.password_confirmation = ''
  registerSaving.value = false
  registerModalOpen.value = false
}

function validateRegister() {
  resetRegisterErrors()

  if (!registerForm.name) registerErrors.name = 'Informe seu nome.'

  if (!registerForm.email) registerErrors.email = 'Informe seu e-mail.'
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(registerForm.email)) registerErrors.email = 'Informe um e-mail válido.'

  if (!registerForm.username) registerErrors.username = 'Informe o username.'
  else if (!/^[A-Za-z0-9_]+$/.test(registerForm.username)) registerErrors.username = 'Use apenas letras, números e underscore (_).'

  if (!registerForm.password) registerErrors.password = 'Informe uma senha.'
  else if (String(registerForm.password).length < 5) registerErrors.password = 'A senha deve ter no mínimo 5 caracteres.'

  if (registerForm.password_confirmation !== registerForm.password) registerErrors.password_confirmation = 'As senhas não conferem.'

  return (
    !registerErrors.name &&
    !registerErrors.email &&
    !registerErrors.username &&
    !registerErrors.password &&
    !registerErrors.password_confirmation
  )
}

async function submitRegister() {
  if (!validateRegister()) return

  registerSaving.value = true

  try {
    const { data } = await axios.post('/api/register', {
      name: registerForm.name,
      email: registerForm.email,
      username: registerForm.username,
      password: registerForm.password,
    })

    toastSuccess('Cadastro criado', 'Bem-vindo!')
    if (data?.token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`
    }
    closeRegisterModal()
    emit('loggedIn', { user: data.user, token: data.token })
  } catch (error) {
    if (error?.response?.status === 422) {
      const errs = error?.response?.data?.errors || {}
      if (errs.name?.[0]) registerErrors.name = errs.name[0]
      if (errs.email?.[0]) registerErrors.email = errs.email[0]
      if (errs.username?.[0]) registerErrors.username = errs.username[0]
      if (errs.password?.[0]) registerErrors.password = errs.password[0]
      toastError('Erro no cadastro', error?.response?.data?.message || 'Verifique os campos.')
    } else {
      toastError('Erro no cadastro', 'Erro inesperado. Tente novamente.')
    }
  } finally {
    registerSaving.value = false
  }
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

    toastSuccess('Login realizado', 'Bem-vindo!')
    if (data?.token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`
    }
    emit('loggedIn', { user: data.user, token: data.token })
  } catch (error) {
    if (error?.response?.status === 422) {
      const message = error?.response?.data?.message || 'Não foi possível autenticar.'
      const fieldErrors = error?.response?.data?.errors || {}

      errors.general = message
      if (fieldErrors.email?.[0]) errors.email = fieldErrors.email[0]
      if (fieldErrors.password?.[0]) errors.password = fieldErrors.password[0]
      if (!errors.email && !errors.password) errors.general = 'E-mail ou senha inválidos.'

      toastError('Falha no login', errors.general)
    } else {
      errors.general = 'Erro inesperado. Tente novamente.'
      toastError('Falha no login', errors.general)
    }
  } finally {
    loading.value = false
  }
}
</script>
