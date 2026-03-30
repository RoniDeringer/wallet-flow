<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-950 via-indigo-950 to-slate-900 text-white">
    <header class="mx-auto max-w-6xl px-6 py-10">
      <div class="flex items-center justify-between gap-6">
        <div class="flex items-center gap-3">
          <div
            class="grid size-11 place-items-center rounded-2xl bg-white/10 ring-1 ring-white/15 shadow-[0_20px_60px_-20px_rgba(0,0,0,0.65)]"
          >
            <svg class="size-6 text-indigo-200" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 7h18M7 12h10M6 17h12"
              />
            </svg>
          </div>
          <div class="leading-tight">
            <div class="text-lg font-semibold tracking-tight">Wallet Flow</div>
            <div class="text-xs text-white/70">Organize, acompanhe e evolua suas finanças</div>
          </div>
        </div>

        <div class="hidden sm:flex items-center gap-2 text-sm text-white/80">
          <span class="rounded-full bg-white/10 px-3 py-1 ring-1 ring-white/10">Vue 3</span>
          <span class="rounded-full bg-white/10 px-3 py-1 ring-1 ring-white/10">Laravel + Vite</span>
          <span class="rounded-full bg-white/10 px-3 py-1 ring-1 ring-white/10">Tailwind</span>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-6xl px-6 pb-16">
      <section class="grid gap-10 lg:grid-cols-2 lg:items-center">
        <div class="space-y-6">
          <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm text-white/85 ring-1 ring-white/10">
            <span class="text-indigo-200">🚀</span>
            <span>{{ tagline }}</span>
          </p>

          <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">
            Sua carteira, com
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-200 to-sky-200">clareza</span>
            e
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-200 to-emerald-200">controle</span>.
          </h1>

          <p class="max-w-xl text-base text-white/75 sm:text-lg">
            Tenha uma visão rápida do que entra, do que sai e do que realmente importa. Comece com uma configuração mínima e evolua aos poucos.
          </p>

          <div class="flex flex-wrap items-center gap-3">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl bg-indigo-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 ring-1 ring-white/10 hover:bg-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-200"
              @click="start()"
            >
              Começar agora
            </button>

            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl bg-white/10 px-5 py-3 text-sm font-semibold text-white/90 ring-1 ring-white/15 hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/30"
              @click="preview = !preview"
            >
              {{ preview ? 'Fechar prévia' : 'Ver prévia' }}
            </button>

            <p v-if="started" class="text-sm text-emerald-200">Pronto! Agora você pode conectar suas próximas telas.</p>
          </div>

          <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
              <div class="text-xs text-white/60">Objetivo</div>
              <div class="mt-1 text-sm font-semibold">Fluxo diário</div>
            </div>
            <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
              <div class="text-xs text-white/60">Foco</div>
              <div class="mt-1 text-sm font-semibold">Categorias</div>
            </div>
            <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
              <div class="text-xs text-white/60">Resultado</div>
              <div class="mt-1 text-sm font-semibold">Decisões melhores</div>
            </div>
          </div>
        </div>

        <div class="relative">
          <div class="absolute -inset-4 rounded-[2rem] bg-gradient-to-r from-indigo-500/20 via-sky-400/10 to-emerald-400/10 blur-2xl" />

          <div class="relative overflow-hidden rounded-[2rem] bg-white/5 ring-1 ring-white/10 shadow-[0_30px_90px_-40px_rgba(0,0,0,0.7)]">
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/10">
              <div class="text-sm font-semibold text-white/90">Resumo do mês</div>
              <div class="text-xs text-white/60">Dados de exemplo</div>
            </div>

            <div class="p-6 space-y-4">
              <div class="grid grid-cols-3 gap-3">
                <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10">
                  <div class="text-xs text-white/60">Entradas</div>
                  <div class="mt-1 text-lg font-semibold text-emerald-200">R$ 8.420</div>
                </div>
                <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10">
                  <div class="text-xs text-white/60">Saídas</div>
                  <div class="mt-1 text-lg font-semibold text-rose-200">R$ 5.130</div>
                </div>
                <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10">
                  <div class="text-xs text-white/60">Saldo</div>
                  <div class="mt-1 text-lg font-semibold text-sky-200">R$ 3.290</div>
                </div>
              </div>

              <div class="rounded-2xl bg-white/5 p-5 ring-1 ring-white/10">
                <div class="flex items-start justify-between gap-4">
                  <div>
                    <div class="text-sm font-semibold">Top categorias</div>
                    <div class="mt-1 text-xs text-white/60">Acompanhe tendências e ajuste seu plano.</div>
                  </div>
                  <div class="text-xs text-white/60">últimos 30 dias</div>
                </div>

                <div class="mt-4 space-y-3">
                  <div class="flex items-center gap-3">
                    <div class="w-24 text-xs text-white/70">Moradia</div>
                    <div class="h-2 flex-1 rounded-full bg-white/10">
                      <div class="h-2 w-[72%] rounded-full bg-gradient-to-r from-indigo-300 to-sky-200" />
                    </div>
                    <div class="w-16 text-right text-xs text-white/70">R$ 1.850</div>
                  </div>
                  <div class="flex items-center gap-3">
                    <div class="w-24 text-xs text-white/70">Mercado</div>
                    <div class="h-2 flex-1 rounded-full bg-white/10">
                      <div class="h-2 w-[48%] rounded-full bg-gradient-to-r from-emerald-200 to-sky-200" />
                    </div>
                    <div class="w-16 text-right text-xs text-white/70">R$ 1.230</div>
                  </div>
                  <div class="flex items-center gap-3">
                    <div class="w-24 text-xs text-white/70">Transporte</div>
                    <div class="h-2 flex-1 rounded-full bg-white/10">
                      <div class="h-2 w-[32%] rounded-full bg-gradient-to-r from-rose-200 to-indigo-200" />
                    </div>
                    <div class="w-16 text-right text-xs text-white/70">R$ 820</div>
                  </div>
                </div>
              </div>

              <div v-if="preview" class="rounded-2xl bg-black/25 p-5 ring-1 ring-white/10">
                <div class="text-sm font-semibold">Prévia</div>
                <p class="mt-1 text-xs text-white/65">
                  Este card é só uma demonstração visual. Você pode substituir por componentes reais quando criar o dashboard.
                </p>
                <div class="mt-4 grid gap-2">
                  <div class="rounded-xl bg-white/5 px-4 py-3 ring-1 ring-white/10">
                    <div class="text-xs text-white/60">Sugestão</div>
                    <div class="text-sm font-semibold">Defina um teto de gastos por categoria</div>
                  </div>
                  <div class="rounded-xl bg-white/5 px-4 py-3 ring-1 ring-white/10">
                    <div class="text-xs text-white/60">Próximo passo</div>
                    <div class="text-sm font-semibold">Criar tela de transações</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <footer class="mt-16 border-t border-white/10 pt-8 text-xs text-white/60">
        © {{ year }} Wallet Flow. Feito com Vue 3.
      </footer>
    </main>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const tagline = 'Bem-vindo! Sua tela inicial está pronta.'

const started = ref(false)
const preview = ref(false)
const year = computed(() => new Date().getFullYear())

function start() {
  started.value = true
  preview.value = true
}
</script>
