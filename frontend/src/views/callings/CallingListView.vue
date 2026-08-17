<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-gray-950">Llamamientos</h1>
        <p class="text-sm text-gray-600 mt-0.5">Propuestas y votaciones del Sumo Consejo</p>
      </div>
      <button v-if="canPropose" @click="showProposeModal = true" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Proponer llamamiento
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="flex flex-col sm:flex-row gap-3">
        <input v-model="filters.search" type="text" placeholder="Buscar miembro o llamamiento..." class="input-field flex-1" @input="debouncedFetch" />
        <select v-model="filters.status" @change="fetchCallings" class="input-field sm:w-44">
          <option value="">Todos</option>
          <option value="pending">En votación</option>
          <option value="approved">Aprobados</option>
          <option value="rejected">Rechazados</option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="card p-8 text-center">
      <svg class="animate-spin w-6 h-6 mx-auto text-primary-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
    </div>

    <!-- Callings Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 stagger">
      <router-link
        v-for="c in callings" :key="c.id"
        :to="`/callings/${c.id}`"
        class="card p-5 hover:shadow-detached transition-all group animate-fade-in interactive"
      >
        <div class="flex items-start justify-between mb-3">
          <span class="badge" :class="callingStatusClass(c.status)">{{ callingStatusLabel(c.status) }}</span>
          <span v-if="c.is_voting_open" class="text-xs text-gray-500">
            Cierra {{ formatDate(c.voting_deadline) }}
          </span>
        </div>
        <h3 class="font-semibold text-gray-950 group-hover:text-primary-600 transition-colors">{{ c.member_name }}</h3>
        <p class="text-sm text-gray-600 mt-0.5">{{ c.calling_name }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ c.ward }}</p>

        <!-- Vote Progress -->
        <div class="mt-4 pt-3 border-t border-gray-100">
          <div class="flex items-center justify-between text-xs mb-1.5">
            <span class="text-success font-medium">{{ c.approval_count }} a favor</span>
            <span class="text-danger font-medium">{{ c.disapproval_count }} en contra</span>
          </div>
          <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden flex">
            <div class="bg-success rounded-full transition-all" :style="{ width: voteBarWidth(c, 'approve') }" style="transition-duration: 500ms;"></div>
            <div class="bg-danger rounded-full transition-all" :style="{ width: voteBarWidth(c, 'disapprove') }" style="transition-duration: 500ms;"></div>
          </div>
          <div class="flex items-center justify-between mt-1.5">
            <span class="text-xs text-gray-400">{{ c.votes_count }} votos</span>
            <span v-if="c.has_user_voted" class="badge badge-neutral text-[10px]">Ya votaste</span>
          </div>
        </div>
      </router-link>
    </div>

    <!-- Empty -->
    <div v-if="!loading && callings.length === 0" class="card p-8 text-center">
      <div class="w-12 h-12 mx-auto rounded-lg bg-gray-100 flex items-center justify-center mb-3">
        <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
      </div>
      <p class="text-gray-500 text-sm">No hay llamamientos para mostrar.</p>
    </div>

    <!-- Propose Modal -->
    <transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showProposeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/30" @click.self="showProposeModal = false">
        <div class="card-elevated p-6 w-full max-w-md space-y-4 animate-scale-in">
          <h3 class="text-base font-semibold text-gray-950">Proponer Llamamiento</h3>
          <div v-if="proposeError" class="alert alert-danger">{{ proposeError }}</div>
          <div>
            <label class="input-label">Nombre del miembro</label>
            <input v-model="proposeForm.member_name" type="text" class="input-field" placeholder="Ej: Juan Pérez" />
          </div>
          <div>
            <label class="input-label">Llamamiento</label>
            <input v-model="proposeForm.calling_name" type="text" class="input-field" placeholder="Ej: Presidente de Élderes" />
          </div>
          <div>
            <label class="input-label">Barrio</label>
            <input v-model="proposeForm.ward" type="text" class="input-field" placeholder="Ej: Barrio La Serena 1" />
          </div>
          <div>
            <label class="input-label">Notas (opcional)</label>
            <textarea v-model="proposeForm.notes" class="input-field" rows="2" placeholder="Observaciones adicionales..."></textarea>
          </div>
          <div>
            <label class="input-label">Plazo de votación</label>
            <input v-model="proposeForm.voting_deadline" type="datetime-local" class="input-field" />
          </div>
          <div class="flex gap-3 pt-2">
            <button @click="showProposeModal = false" class="btn btn-ghost flex-1">Cancelar</button>
            <button @click="proposeCalling" :disabled="proposing" class="btn btn-primary flex-1">
              {{ proposing ? 'Proponiendo...' : 'Proponer' }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const auth = useAuthStore()
const canPropose = computed(() => ['admin', 'presidencia'].includes(auth.user?.role))

const loading = ref(true)
const callings = ref([])
const filters = reactive({ search: '', status: '' })

const showProposeModal = ref(false)
const proposing = ref(false)
const proposeError = ref('')
const proposeForm = reactive({ member_name: '', calling_name: '', ward: '', notes: '', voting_deadline: '' })

let debounceTimer = null
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(fetchCallings, 300) }

async function fetchCallings() {
  loading.value = true
  try {
    const params = {}
    if (filters.search) params.search = filters.search
    if (filters.status) params.status = filters.status
    const { data } = await api.get('/callings', { params })
    callings.value = data.data
  } catch (err) { console.error(err) }
  finally { loading.value = false }
}

async function proposeCalling() {
  proposing.value = true; proposeError.value = ''
  try {
    await api.post('/callings', proposeForm)
    showProposeModal.value = false
    Object.assign(proposeForm, { member_name: '', calling_name: '', ward: '', notes: '', voting_deadline: '' })
    fetchCallings()
  } catch (err) { proposeError.value = err.response?.data?.message || 'Error al proponer.' }
  finally { proposing.value = false }
}

function callingStatusLabel(s) { return { pending: 'En votación', approved: 'Aprobado', rejected: 'Rechazado', cancelled: 'Cancelado' }[s] || s }
function callingStatusClass(s) { return { pending: 'badge-warning', approved: 'badge-success', rejected: 'badge-danger', cancelled: 'badge-neutral' }[s] || 'badge-neutral' }
function formatDate(d) { return new Date(d).toLocaleDateString('es-CL', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }

function voteBarWidth(calling, type) {
  const total = (calling.approval_count || 0) + (calling.disapproval_count || 0)
  if (!total) return '0%'
  const count = type === 'approve' ? calling.approval_count : calling.disapproval_count
  return `${(count / total) * 100}%`
}

onMounted(fetchCallings)
</script>
