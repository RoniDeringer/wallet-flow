<template>
  <aside class="w-full sm:w-72 sm:pl-6 sm:pr-0 sm:h-full">
    <div
      class="flex h-full flex-col overflow-hidden border-b border-white/10 bg-white/5 shadow-[0_30px_100px_-60px_rgba(0,0,0,0.9)] ring-1 ring-white/10 backdrop-blur sm:rounded-3xl sm:border-b-0"
    >
      <div class="px-6 py-6">
        <div class="text-[11px] font-semibold uppercase tracking-wide text-white/50">Sessão</div>
        <div class="mt-2 text-sm font-semibold text-white">{{ user.name }}</div>
        <div class="mt-1 text-xs text-white/60">{{ user.email }}</div>
        <div class="mt-2 inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-[11px] font-semibold text-[#e9c15e] ring-1 ring-white/10">
          {{ user.role === 'admin' ? 'Administrador' : 'Cliente' }}
        </div>
      </div>

      <nav class="flex-1 overflow-y-auto px-3 pb-6">
        <button
          v-for="item in items"
          :key="item.key"
          type="button"
          class="group mb-2 flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left text-sm ring-1 ring-white/10 hover:bg-white/10 cursor-pointer"
          :class="item.key === activeKey ? 'bg-white/10 text-white shadow-[0_12px_40px_-25px_rgba(233,193,94,0.35)]' : 'text-white/80'"
          @click="$emit('select', item.key)"
        >
          <span class="flex items-center gap-3 font-semibold">
            <span
              class="size-2 rounded-full ring-1 ring-white/15"
              :class="item.key === activeKey ? 'bg-[#e9c15e]' : 'bg-white/30'"
              aria-hidden="true"
            />
            {{ item.label }}
          </span>
          <svg class="size-4 text-white/40 transition group-hover:text-white/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </nav>

      <div class="border-t border-white/10 p-3">
        <button
          type="button"
          class="flex w-full items-center justify-center rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold text-white ring-1 ring-white/15 hover:bg-white/15 cursor-pointer"
          @click="$emit('logout')"
        >
          Sair
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup>
defineProps({
  user: { type: Object, required: true },
  items: { type: Array, required: true },
  activeKey: { type: String, required: true },
})

defineEmits(['select', 'logout'])
</script>
