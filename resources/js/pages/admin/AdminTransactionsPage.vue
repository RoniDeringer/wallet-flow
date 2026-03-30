<template>
  <section class="flex flex-1 flex-col px-6 pb-10 pt-6">
    <div class="rounded-3xl bg-white/5 p-8 ring-1 ring-white/10">
      <h2 class="text-xl font-semibold text-white">Transações</h2>
      <p class="mt-2 text-sm text-white/70">
        Visão administrativa das transações do Ledger (deposit, transfer, reversal).
      </p>

      <div class="mt-6 grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl bg-black/20 p-5 ring-1 ring-white/10">
          <div class="text-xs text-white/60">Tabela</div>
          <div class="mt-1 text-sm font-semibold text-white">`ledger_transactions`</div>
        </div>
        <div class="rounded-2xl bg-black/20 p-5 ring-1 ring-white/10">
          <div class="text-xs text-white/60">Entries</div>
          <div class="mt-1 text-sm font-semibold text-white">`ledger_entries`</div>
        </div>
        <div class="rounded-2xl bg-black/20 p-5 ring-1 ring-white/10">
          <div class="text-xs text-white/60">Filtro</div>
          <div class="mt-1 text-sm font-semibold text-white">por usuário / período</div>
        </div>
      </div>
    </div>
  </section>
</template>

