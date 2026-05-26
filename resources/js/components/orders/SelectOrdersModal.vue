<template>
    <div v-if="selectOrdersModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="closeSelectOrdersModal()"></div>

        <div class="relative w-full max-w-4xl overflow-hidden rounded-3xl bg-[#0b0f14] ring-1 ring-white/10 shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-white/10 bg-white/5 px-6 py-4">
                <div>
                    <div class="text-sm font-semibold text-white/90">Selecionar pedidos</div>
                    <div class="mt-1 text-xs text-white/60">Selecione um ou mais pedidos.</div>
                </div>

                <button type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-white/15 bg-transparent px-3 py-2 text-xs font-semibold text-white/80 hover:bg-white/5"
                    @click="closeSelectOrdersModal()">
                    Fechar
                </button>
            </div>

            <div class="px-6 py-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="text-xs text-white/60">
                        Selecionados: <span class="font-semibold text-white/85">{{ selectedOrderIds.length }}</span>
                    </div>
                </div>

                <div class="mt-4 overflow-hidden rounded-2xl ring-1 ring-white/10">
                    <div class="grid grid-cols-12 gap-2 bg-black/20 px-5 py-3 text-xs font-semibold text-white/75">
                        <div class="col-span-1"></div>
                        <div class="col-span-3">Cliente</div>
                        <div class="col-span-3">Produto/Qtd.</div>
                        <div class="col-span-2 text-right">Valor total</div>
                        <div class="col-span-3 text-right">Status</div>
                    </div>

                    <div v-if="selectOrdersLoading" class="px-5 py-6 text-sm text-white/70">Carregando...</div>
                    <div v-else-if="selectableOrders.length === 0" class="px-5 py-6 text-sm text-white/70">
                        Nenhum pedido encontrado.
                    </div>
                    <div v-else class="max-h-[55vh] divide-y divide-white/10 overflow-auto">
                        <label v-for="o in selectableOrders" :key="o.id"
                            class="grid cursor-pointer grid-cols-12 items-center gap-2 px-5 py-4 text-sm hover:bg-white/5">
                            <div class="col-span-1 flex items-center">
                                <input v-model="selectedOrderIds" type="checkbox" :value="o.id"
                                    class="size-4 rounded border-white/20 bg-black/40 text-[#e9c15e] ring-1 ring-white/10 focus:ring-2 focus:ring-[#e9c15e]/70" />
                            </div>
                            <div class="col-span-3 text-white/80">{{ o.customer }}</div>
                            <div class="col-span-3 font-semibold text-white/85">
                                {{ products[o.product] }} - {{ o.quantity }}
                            </div>
                            <div class="col-span-2 text-right font-semibold text-[#e9c15e]">
                                {{ formatSignedBRL(o.amount_total) }}
                            </div>
                            <div class="col-span-3 text-right">
                                <span
                                    class="ml-2 text-right rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-semibold ring-1 ring-white/10">
                                    {{ o.status }}
                                </span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap justify-end gap-3">
                    <button type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-transparent px-4 py-2.5 text-sm font-semibold text-white/85 hover:bg-white/5"
                        @click="closeSelectOrdersModal()">
                        Cancelar
                    </button>
                    <button type="button"
                        class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/15 hover:bg-white/15"
                        :disabled="selectedOrderIds.length === 0" @click="confirmSelectedOrders()">
                        Confirmar seleção
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
