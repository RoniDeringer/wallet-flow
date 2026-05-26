<template>
    <section class="flex flex-1 flex-col px-6 pb-10 pt-6">
        <div class="rounded-3xl bg-white/5 p-8 ring-1 ring-white/10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-white">Pedidos</h2>
                    <p class="mt-2 text-sm text-white/70">Criar e visulizar pedidos do arquivo orders.json</p>
                </div>

            </div>

            <!-- Filtros -->
            <div class="mt-6 grid gap-3 rounded-2xl bg-black/20 p-5 ring-1 ring-white/10 sm:grid-cols-5">


                <div class="sm:col-span-2">
                    <div class="text-xs font-semibold text-white/70">Nome</div>
                    <input v-model="filters.customer" type="text"
                        class="mt-2 w-full rounded-xl bg-black/30 px-3 py-2.5 text-sm text-white ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70" />
                </div>

                <div class="sm:col-span-2">
                    <div class="text-xs font-semibold text-white/70">Produto</div>
                    <select v-model="filters.product"
                        class="mt-2 w-full rounded-xl bg-black/30 pl-3 pr-10 py-2.5 text-sm text-white ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70">
                        <option value="">Selecione</option>

                        <option v-for="(label, value) in products" :key="value" :value="value">
                            {{ label }}
                        </option>
                    </select>
                </div>

                <div class="sm:col-span-1">
                    <div class="text-xs font-semibold text-white/70">Quantidade</div>
                    <input v-model="filters.quantity" type="number"
                        class="mt-2 w-full rounded-xl bg-black/30 px-3 py-2.5 text-sm text-white ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70" />
                </div>

                <div class="sm:col-span-5 flex flex-wrap justify-end gap-3">
                    <button type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-transparent px-4 py-2.5 text-sm font-semibold text-white/85 hover:bg-white/5"
                        :disabled="loading" @click="clearFilters()">
                        Reset
                    </button>
                    <button type="button"
                        class="inline-flex cursor-pointer items-center justify-center rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/15 hover:bg-white/15"
                        :disabled="loading" @click="openSelectOrdersModal()">
                        Selecionar pedido
                    </button>
                    <button type="button"
                        class="inline-flex cursor-pointer items-center justify-center rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/15 hover:bg-white/15"
                        :disabled="loading" @click="createOrder()">
                        Realizar pedido
                    </button>
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-2xl ring-1 ring-white/10">
                <div class="grid grid-cols-12 gap-2 bg-black/20 px-5 py-3 text-xs font-semibold text-white/75">
                    <div class="col-span-3">Cliente</div>
                    <div class="col-span-2">Produto/Qtd.</div>
                    <div class="col-span-2 text-right">Valor total</div>
                    <div class="col-span-2 text-right">Status</div>
                    <div class="col-span-2 text-right">Ação</div>
                </div>

                <div v-if="loading" class="px-5 py-6 text-sm text-white/70">Carregando...</div>
                <div v-else-if="orders.length === 0" class="px-5 py-6 text-sm text-white/70">Nenhuma transação
                    encontrada.</div>

                <div v-else class="divide-y divide-white/10">
                    <div v-for="t in orders" :key="t.id" class="grid grid-cols-12 items-center gap-2 px-5 py-4 text-sm">
                        <div class="col-span-3 text-white/80">{{ t.customer }}</div>
                        <div class="col-span-2 font-semibold text-white/85">{{ products[t.product] }} - {{ t.quantity }}
                        </div>
                        <div class="col-span-2 text-right font-semibold text-[#e9c15e]">
                            {{ formatSignedBRL(t.amount_total) }}
                        </div>

                        <div class="col-span-2 text-right">
                            <span
                                class="ml-2 text-right rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-semibold ring-1 ring-white/10">
                                {{ t.status }}
                            </span>
                        </div>

                        <div class="col-span-2 flex justify-end">
                            <button v-if="canDeliver(t)" type="button"
                                class="inline-flex items-center justify-center rounded-xl bg-white/10 px-3 py-2 text-xs font-semibold text-white/80 ring-1 ring-white/15 hover:bg-white/15"
                                title="Entregar pedido" aria-label="Entregar pedido" @click="deliverOrder(t)">

                                <svg class="size-4 text-[#e9c15e]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                            <span v-else class="text-xs text-white/35">—</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="selectOrdersModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="closeSelectOrdersModal()"></div>

            <div
                class="relative w-full max-w-4xl overflow-hidden rounded-3xl bg-[#0b0f14] ring-1 ring-white/10 shadow-2xl">
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
const rows = ref([])
const loading = ref(false)
const error = ref('')
const notice = ref('')
const orders = ref([])

const products = reactive({
    coffee: 'Café',
    bread: 'Pão',
    cat_ear: 'Orelha de gato',
})

const filters = reactive({
    customer: '',
    quantity: '',
    product: '',
})

const meta = reactive({
    page: 1,
    per_page: 25,
    total: 0,
})

const resetModalOpen = ref(false)
const resetSaving = ref(false)

const selectOrdersModalOpen = ref(false)
const selectOrdersLoading = ref(false)
const selectableOrders = ref([])
const selectedOrderIds = ref([])

function clearFilters() {
    filters.customer = ''
    filters.quantity = ''
    filters.product = ''
}

async function fetchOrders() {
    const response = await axios.get('/api/orders')
    return response?.data?.data || []
}

async function openSelectOrdersModal() {
    selectOrdersModalOpen.value = true
    selectedOrderIds.value = []
    selectOrdersLoading.value = true

    try {
        selectableOrders.value = await fetchOrders()
    } catch (e) {
        toastError('Erro ao carregar pedidos')
        selectableOrders.value = []
    } finally {
        selectOrdersLoading.value = false
    }
}

function closeSelectOrdersModal() {
    selectOrdersModalOpen.value = false
}

function confirmSelectedOrders() {
    closeSelectOrdersModal()
    toastSuccess(`${selectedOrderIds.value.length} pedido(s) selecionado(s)`)
}


async function createOrder() {
    loading.value = true
    error.value = ''

    try {
        const params = {
            product: filters.product || undefined,
            customer: filters.customer || undefined,
            quantity: filters.quantity ? Number(filters.quantity) : undefined,
        }

        const response = await axios.post('/api/orders', params)

        if (response.data.status == 201) {
            toastSuccess('Pedido realizado')
        } else {
            toastError('Erro ao criar pedido')
        }

    } catch (e) {
        console.log('error')
        console.log(e)

        error.value =
            e?.response?.data?.message ||
            'Erro ao criar pedido.'
    } finally {
        loading.value = false
    }
}

async function listOrders() {
    loading.value = true
    error.value = ''

    try {
        orders.value = await fetchOrders()

    } catch (e) {
        console.log('error')
        console.log(e)

        error.value =
            e?.response?.data?.message ||
            'Erro ao listar pedidos.'
    } finally {
        loading.value = false
    }
}

async function deliverOrder(order) {
    loading.value = true

    try {
        const response = await axios.post(`/api/orders/deliver/${order.id}`)


        orders.value = response?.data?.data || []

        if (response.status == 201) {
            toastSuccess(response.data.message)
        } else {
            toastError('Erro ao entregar pedido')
        }

    } catch (e) {
        console.log('error')
        console.log(e)

        error.value =
            e?.response?.data?.message ||
            'Erro ao criar pedido.'
    } finally {
        loading.value = false
    }
}


function canDeliver(tx) {
    return tx?.status === 'pending'
}

function formatBRL(cents) {
    const value = Number(cents || 0) / 100
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}


function formatSignedBRL(value) {

    const amount = Number(value || 0)

    return amount.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    })
}


function goTo(page) {
    meta.page = page
}


onMounted(async () => {
    await Promise.all([listOrders()])
})
</script>
