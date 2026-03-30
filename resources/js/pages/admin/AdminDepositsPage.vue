<template>
  <section class="flex flex-1 flex-col px-6 pb-10 pt-6">
    <div class="rounded-3xl bg-white/5 p-8 ring-1 ring-white/10">
      <h2 class="text-xl font-semibold text-white">Depósitos</h2>
      <p class="mt-2 text-sm text-white/70">
        Área administrativa para auditoria e execução de depósitos (futuramente com fila e reversão).
      </p>

      <div class="mt-6 rounded-2xl bg-black/20 p-5 ring-1 ring-white/10">
        <div class="text-xs text-white/60">Sugestão de fluxo</div>
        <div class="mt-2 text-sm text-white/80">
          Criar um comando/job que registra o depósito no Ledger e publica na fila (RabbitMQ), garantindo
          idempotência.
        </div>
      </div>
    </div>
  </section>
</template>

