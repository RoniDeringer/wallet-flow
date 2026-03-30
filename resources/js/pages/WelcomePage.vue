<template>
  <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col px-6 py-12">
    <div class="rounded-3xl bg-white/5 p-8 ring-1 ring-white/10 shadow-[0_30px_90px_-40px_rgba(0,0,0,0.8)]">
      <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="text-xs text-white/70">Bem-vindo(a),</p>
          <h2 class="mt-1 text-2xl font-semibold tracking-tight text-white">
            {{ user.name }} <span class="text-[#e9c15e]">({{ user.role }})</span>
          </h2>
          <p class="mt-2 text-sm text-white/70">
            Você autenticou com sucesso. Próximo passo: criar dashboard, depósitos e transferências.
          </p>
        </div>

        <button
          type="button"
          class="inline-flex items-center justify-center rounded-xl bg-white/10 px-5 py-3 text-sm font-semibold text-white ring-1 ring-white/15 hover:bg-white/15 cursor-pointer"
          @click="$emit('logout')"
        >
          Sair
        </button>
      </div>

      <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl bg-black/20 p-5 ring-1 ring-white/10">
          <div class="text-xs text-white/60">Saldo</div>
          <div class="mt-2 text-lg font-semibold text-white">R$ 0,00</div>
          <div class="mt-1 text-xs text-white/60">Ledger ainda não aplicado no front</div>
        </div>
        <div class="rounded-2xl bg-black/20 p-5 ring-1 ring-white/10">
          <div class="text-xs text-white/60">Ações</div>
          <div class="mt-2 text-sm font-semibold text-white">Depósito / Transferência</div>
          <div class="mt-1 text-xs text-white/60">Vamos implementar com filas (RabbitMQ)</div>
        </div>
        <div class="rounded-2xl bg-black/20 p-5 ring-1 ring-white/10">
          <div class="text-xs text-white/60">Reversão</div>
          <div class="mt-2 text-sm font-semibold text-white">Ativada por transação</div>
          <div class="mt-1 text-xs text-white/60">Base pronta no banco</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  user: {
    type: Object,
    required: true,
  },
})

defineEmits(['logout'])
</script>
