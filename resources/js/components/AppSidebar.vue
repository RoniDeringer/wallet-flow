<template>
  <aside class="w-full border-b border-white/10 bg-black/20 sm:w-72 sm:self-stretch sm:border-b-0 sm:border-r">
    <div class="flex h-full flex-col">
      <div class="px-6 py-6">
        <div class="text-xs text-white/60">Logado como</div>
        <div class="mt-1 text-sm font-semibold text-white">{{ user.name }}</div>
        <div class="mt-1 text-xs text-white/60">
          {{ user.email }} • <span class="text-[#e9c15e]">{{ user.role }}</span>
        </div>
      </div>

      <nav class="flex-1 px-3 pb-6">
        <button
          v-for="item in items"
          :key="item.key"
          type="button"
          class="group mb-2 flex w-full items-center justify-between rounded-xl px-4 py-3 text-left text-sm ring-1 ring-white/10 hover:bg-white/10"
          :class="item.key === activeKey ? 'bg-white/10 text-white' : 'text-white/80'"
          @click="$emit('select', item.key)"
        >
          <span class="font-semibold">{{ item.label }}</span>
          <span
            class="rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-white/10"
            :class="item.key === activeKey ? 'bg-[#e9c15e] text-[#032952]' : 'bg-white/5 text-white/60'"
          >
            {{ item.badge ?? 'menu' }}
          </span>
        </button>
      </nav>

      <div class="border-t border-white/10 p-3">
        <button
          type="button"
          class="flex w-full items-center justify-center rounded-xl bg-white/10 px-4 py-3 text-sm font-semibold text-white ring-1 ring-white/15 hover:bg-white/15"
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
