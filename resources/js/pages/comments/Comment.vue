<template>
    <section class="flex flex-1 flex-col px-6 pb-10 pt-6">
        <div class="rounded-3xl bg-white/5 p-8 ring-1 ring-white/10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-white">Mural</h2>
                    <p class="mt-2 text-sm text-white/70">Mural de comentários</p>
                </div>

            </div>

            <div class="mt-6 grid gap-3 rounded-2xl bg-black/20 p-5 ring-1 ring-white/10 sm:grid-cols-5">


                <div class="sm:col-span-5">
                    <div class="text-xs font-semibold text-white/70">Nome</div>
                    <input v-model="comment.name" type="text"
                        class="mt-2 w-full rounded-xl bg-black/30 px-3 py-2.5 text-sm text-white ring-1 ring-white/10 outline-none focus:ring-2 focus:ring-[#e9c15e]/70" />
                </div>

                <div class="sm:col-span-5">
                    <div class="text-xs font-semibold text-white/70">Comentário</div>
                    <textarea v-model="comment.message" type="text" rows="5"
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
                        :disabled="loading" @click="createComment()">
                        Salvar
                    </button>
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-2xl ring-1 ring-white/10">
                <div class="grid grid-cols-12 gap-2 bg-black/20 px-5 py-3 text-xs font-semibold text-white/75">
                    <div class="col-span-3">Nome</div>
                    <div class="col-span-2">Messagem</div>
                </div>

                <div v-if="loading" class="px-5 py-6 text-sm text-white/70">Carregando...</div>
                <div v-else-if="comments.length === 0" class="px-5 py-6 text-sm text-white/70">Nenhum comentário
                    encontrada.</div>

                <div v-else class="divide-y divide-white/10">
                    <div v-for="t in comments" :key="t.id" class="grid grid-cols-12 items-center gap-2 px-5 py-4 text-sm">
                        <div class="col-span-3 text-white/80">
                            {{ t.name }}
                        </div>
                        <div class="col-span-2 font-semibold text-white/85" :title="t.message">
                            {{ t.message_formatted }}
                        </div>
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

const loading = ref(false)
const error = ref('')
const comments = ref([])


const comment = reactive({
    name:   '',
    message: '',
})


function clearFilters() {
    comment.name = ''
    comment.message = ''
}

async function fetchComments() {
    const response = await axios.get('/api/comments')
    return response?.data?.data || []
}

async function createComment() {
    loading.value = true
    error.value = ''

    try {
        const params = {
            name: comment.name || undefined,
            message: comment.message || undefined,
        }

        const response = await axios.post('/api/comments', params)

        if (response.status == 201) {
            toastSuccess('Comentário salvo')
            comments.value.unshift(response.data.new_comment)
        } else {
            toastError('Erro ao criar comentário')
        }

    } catch (e) {
        const backendMessage = e?.response?.data?.message
        const backendErrors = e?.response?.data?.errors

        const firstValidationError = backendErrors
            ? Object.values(backendErrors)?.flat()?.[0]
            : null

        const message =
            firstValidationError ||
            backendMessage ||
            'Erro ao criar comentario. Verifique os campos.'

        error.value = message
        toastError('Erro ao criar comentario', message)
    } finally {
        loading.value = false
    }
}

async function getComments() {
    loading.value = true
    error.value = ''

    try {
        comments.value = await fetchComments()

    } catch (e) {
        error.value =
            e?.response?.data?.message ||
            'Erro.'
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    await Promise.all([getComments()])
})
</script>
