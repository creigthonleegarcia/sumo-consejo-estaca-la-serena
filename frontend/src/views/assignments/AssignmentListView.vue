<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-gray-950">Asignaciones</h1>
        <p class="text-sm text-gray-600 mt-0.5">Tareas y mayordomías asignadas</p>
      </div>
      <button v-if="canCreate" @click="showCreateModal = true" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Nueva asignación
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="flex flex-col sm:flex-row gap-3">
        <input v-model="filters.search" type="text" placeholder="Buscar asignación..." class="input-field flex-1" @input="debouncedFetch" />
        <select v-model="filters.status" @change="fetchAssignments" class="input-field sm:w-44">
          <option value="">Todos los estados</option>
          <option value="pending">Pendiente</option>
          <option value="in_progress">En progreso</option>
          <option value="completed">Completada</option>
          <option value="cancelled">Cancelada</option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="card p-8 text-center">
      <svg class="animate-spin w-6 h-6 mx-auto text-primary-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
    </div>

    <!-- Assignments Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 stagger">
      <router-link
        v-for="a in assignments"
        :key="a.id"
        :to="`/assignments/${a.id}`"
        class="card p-5 hover:shadow-detached transition-all group animate-fade-in interactive"
      >
        <div class="flex items-start justify-between mb-3">
          <span class="badge" :class="statusBadgeClass(a.status)">{{ statusLabel(a.status) }}</span>
          <span v-if="a.due_date" class="text-xs text-gray-500">{{ formatDate(a.due_date) }}</span>
        </div>
        <h3 class="font-semibold text-gray-950 group-hover:text-primary-600 transition-colors line-clamp-2">{{ a.title }}</h3>
        <p v-if="a.description" class="text-sm text-gray-500 mt-1 line-clamp-2">{{ a.description }}</p>
        <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100">
          <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center text-[10px] font-bold text-primary-600">
              {{ getInitials(a.assignee?.name) }}
            </div>
            <span class="text-xs text-gray-600">{{ a.assignee?.name }}</span>
          </div>
          <span class="text-xs text-gray-400">{{ a.reports_count }} informes</span>
        </div>
      </router-link>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && assignments.length === 0" class="card p-8 text-center">
      <div class="w-12 h-12 mx-auto rounded-lg bg-gray-100 flex items-center justify-center mb-3">
        <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586" />
        </svg>
      </div>
      <p class="text-gray-500 text-sm">No hay asignaciones para mostrar.</p>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.lastPage > 1" class="flex justify-center gap-1">
      <button v-for="page in pagination.lastPage" :key="page" @click="fetchAssignments(page)"
        class="px-3 py-1.5 text-sm rounded-md transition-all interactive"
        :class="page === pagination.currentPage ? 'bg-primary-500 text-white' : 'text-gray-600 hover:bg-gray-100'">
        {{ page }}
      </button>
    </div>

    <!-- Create Modal -->
    <transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/30" @click.self="showCreateModal = false">
        <div class="card-elevated p-6 w-full max-w-md space-y-4 animate-scale-in">
          <h3 class="text-base font-semibold text-gray-950">Nueva Asignación</h3>
          <div v-if="createError" class="alert alert-danger">{{ createError }}</div>
          <div>
            <label class="input-label">Título</label>
            <input v-model="createForm.title" type="text" class="input-field" placeholder="Ej: Visitar barrio norte" />
          </div>
          <div>
            <label class="input-label">Descripción</label>
            <textarea v-model="createForm.description" class="input-field" rows="3" placeholder="Detalles de la asignación..."></textarea>
          </div>
          <div>
            <label class="input-label">Asignar a</label>
            <select v-model="createForm.assigned_to" class="input-field">
              <option value="">Seleccionar miembro...</option>
              <option v-for="u in memberUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
          </div>
          <div>
            <label class="input-label">Fecha límite</label>
            <input v-model="createForm.due_date" type="date" class="input-field" />
          </div>
          <div class="flex gap-3 pt-2">
            <button @click="showCreateModal = false" class="btn btn-ghost flex-1">Cancelar</button>
            <button @click="createAssignment" :disabled="creating" class="btn btn-primary flex-1">
              {{ creating ? 'Creando...' : 'Crear' }}
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
const canCreate = computed(() => ['admin', 'presidencia', 'secretario'].includes(auth.user?.role))

const loading = ref(true)
const assignments = ref([])
const pagination = reactive({ currentPage: 1, lastPage: 1 })
const filters = reactive({ search: '', status: '' })
const memberUsers = ref([])

// Create modal
const showCreateModal = ref(false)
const creating = ref(false)
const createError = ref('')
const createForm = reactive({ title: '', description: '', assigned_to: '', due_date: '' })

let debounceTimer = null
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(fetchAssignments, 300) }

async function fetchAssignments(page = 1) {
  loading.value = true
  try {
    const params = { page }
    if (filters.search) params.search = filters.search
    if (filters.status) params.status = filters.status
    const { data } = await api.get('/assignments', { params })
    assignments.value = data.data
    pagination.currentPage = data.current_page
    pagination.lastPage = data.last_page
  } catch (err) { console.error(err) }
  finally { loading.value = false }
}

async function fetchMembers() {
  try {
    const { data } = await api.get('/admin/users', { params: { per_page: 100, is_active: true } })
    memberUsers.value = data.data
  } catch { /* non-admin won't be able to fetch */ }
}

async function createAssignment() {
  creating.value = true; createError.value = ''
  try {
    await api.post('/assignments', createForm)
    showCreateModal.value = false
    Object.assign(createForm, { title: '', description: '', assigned_to: '', due_date: '' })
    fetchAssignments()
  } catch (err) { createError.value = err.response?.data?.message || 'Error al crear.' }
  finally { creating.value = false }
}

function getInitials(name) { return (name || '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) }
function formatDate(d) { return new Date(d).toLocaleDateString('es-CL', { day: 'numeric', month: 'short' }) }

function statusLabel(s) { return { pending: 'Pendiente', in_progress: 'En progreso', completed: 'Completada', cancelled: 'Cancelada' }[s] || s }
function statusBadgeClass(s) { return { pending: 'badge-warning', in_progress: 'badge-primary', completed: 'badge-success', cancelled: 'badge-neutral' }[s] || 'badge-neutral' }

onMounted(() => { fetchAssignments(); fetchMembers() })
</script>
