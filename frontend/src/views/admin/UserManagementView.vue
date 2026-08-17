<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-gray-950">Gestión de Usuarios</h1>
        <p class="text-sm text-gray-600 mt-0.5">Administra miembros e invitaciones del consejo</p>
      </div>
      <button @click="showInviteModal = true" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Invitar usuario
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Buscar por nombre o correo..."
            class="input-field"
            @input="debouncedFetch"
          />
        </div>
        <select v-model="filters.role" @change="fetchUsers" class="input-field sm:w-48">
          <option value="">Todos los roles</option>
          <option value="admin">Admin</option>
          <option value="presidencia">Presidencia</option>
          <option value="secretario">Secretario</option>
          <option value="sumo_consejo">Sumo Consejo</option>
        </select>
        <select v-model="filters.is_active" @change="fetchUsers" class="input-field sm:w-36">
          <option value="">Todos</option>
          <option value="true">Activos</option>
          <option value="false">Inactivos</option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="card p-8 text-center">
      <svg class="animate-spin w-6 h-6 mx-auto text-primary-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      <p class="text-gray-500 text-sm mt-3">Cargando usuarios...</p>
    </div>

    <!-- Users Table -->
    <div v-else class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50">
              <th class="text-left px-4 py-3 font-semibold text-gray-700">Usuario</th>
              <th class="text-left px-4 py-3 font-semibold text-gray-700 hidden sm:table-cell">Correo</th>
              <th class="text-left px-4 py-3 font-semibold text-gray-700">Rol</th>
              <th class="text-center px-4 py-3 font-semibold text-gray-700">Estado</th>
              <th class="text-right px-4 py-3 font-semibold text-gray-700">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 transition-colors" style="transition-duration: 120ms;">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-xs font-bold text-white shrink-0">
                    {{ getInitials(user.name) }}
                  </div>
                  <div class="min-w-0">
                    <p class="font-medium text-gray-950 truncate">{{ user.name }}</p>
                    <p class="text-xs text-gray-500 sm:hidden truncate">{{ user.email }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ user.email }}</td>
              <td class="px-4 py-3">
                <span class="badge" :class="roleBadgeClass(user.role)">{{ roleLabel(user.role) }}</span>
              </td>
              <td class="px-4 py-3 text-center">
                <span class="badge" :class="user.is_active ? 'badge-success' : 'badge-neutral'">
                  {{ user.is_active ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-1">
                  <button
                    @click="openEditModal(user)"
                    class="p-1.5 rounded-md text-gray-400 hover:text-primary-500 hover:bg-primary-50 transition-all interactive"
                    title="Editar"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                  </button>
                  <button
                    v-if="user.id !== currentUserId"
                    @click="toggleActive(user)"
                    class="p-1.5 rounded-md transition-all interactive"
                    :class="user.is_active ? 'text-gray-400 hover:text-danger hover:bg-danger-light' : 'text-gray-400 hover:text-success hover:bg-success-light'"
                    :title="user.is_active ? 'Desactivar' : 'Activar'"
                  >
                    <svg v-if="user.is_active" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="users.length === 0" class="p-8 text-center">
        <div class="w-12 h-12 mx-auto rounded-lg bg-gray-100 flex items-center justify-center mb-3">
          <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
          </svg>
        </div>
        <p class="text-gray-500 text-sm">No se encontraron usuarios.</p>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.lastPage > 1" class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
        <p class="text-xs text-gray-500">
          Mostrando {{ pagination.from }}-{{ pagination.to }} de {{ pagination.total }}
        </p>
        <div class="flex gap-1">
          <button
            v-for="page in pagination.lastPage"
            :key="page"
            @click="goToPage(page)"
            class="px-3 py-1 text-xs rounded-md transition-all interactive"
            :class="page === pagination.currentPage ? 'bg-primary-500 text-white' : 'text-gray-600 hover:bg-gray-100'"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <transition
      enter-active-class="transition-all duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-all duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
      style="transition-timing-function: var(--ease-out);"
    >
      <div v-if="editModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/30" @click.self="editModal.show = false">
        <div class="card-elevated p-6 w-full max-w-sm space-y-4 animate-scale-in">
          <h3 class="text-base font-semibold text-gray-950">Editar Usuario</h3>

          <div>
            <label class="input-label">Nombre</label>
            <input v-model="editModal.form.name" type="text" class="input-field" />
          </div>

          <div>
            <label class="input-label">Teléfono</label>
            <input v-model="editModal.form.phone" type="tel" class="input-field" placeholder="+56 9 1234 5678" />
          </div>

          <div>
            <label class="input-label">Rol</label>
            <select v-model="editModal.form.role" class="input-field">
              <option value="admin">Administrador</option>
              <option value="presidencia">Presidencia</option>
              <option value="secretario">Secretario</option>
              <option value="sumo_consejo">Sumo Consejo</option>
            </select>
          </div>

          <div class="flex gap-3 pt-2">
            <button @click="editModal.show = false" class="btn btn-ghost flex-1">Cancelar</button>
            <button @click="saveEdit" :disabled="editModal.saving" class="btn btn-primary flex-1">
              {{ editModal.saving ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Invite Modal -->
    <transition
      enter-active-class="transition-all duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-all duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="showInviteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/30" @click.self="showInviteModal = false">
        <div class="card-elevated p-6 w-full max-w-sm space-y-4 animate-scale-in">
          <h3 class="text-base font-semibold text-gray-950">Invitar Usuario</h3>

          <div v-if="inviteSuccess" class="alert alert-success">
            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Invitación enviada correctamente.</span>
          </div>

          <div v-if="inviteError" class="alert alert-danger">
            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <span>{{ inviteError }}</span>
          </div>

          <div>
            <label class="input-label">Correo electrónico</label>
            <input v-model="inviteForm.email" type="email" class="input-field" placeholder="usuario@correo.cl" />
          </div>

          <div>
            <label class="input-label">Rol</label>
            <select v-model="inviteForm.role" class="input-field">
              <option value="sumo_consejo">Sumo Consejo</option>
              <option value="secretario">Secretario</option>
              <option value="presidencia">Presidencia</option>
            </select>
          </div>

          <div class="flex gap-3 pt-2">
            <button @click="showInviteModal = false" class="btn btn-ghost flex-1">Cancelar</button>
            <button @click="sendInvite" :disabled="inviteSending" class="btn btn-primary flex-1">
              {{ inviteSending ? 'Enviando...' : 'Enviar invitación' }}
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
const currentUserId = computed(() => auth.user?.id)

const loading = ref(true)
const users = ref([])
const pagination = reactive({ currentPage: 1, lastPage: 1, from: 0, to: 0, total: 0 })
const filters = reactive({ search: '', role: '', is_active: '' })

// Edit modal
const editModal = reactive({ show: false, user: null, saving: false, form: { name: '', phone: '', role: '' } })

// Invite modal
const showInviteModal = ref(false)
const inviteForm = reactive({ email: '', role: 'sumo_consejo' })
const inviteSending = ref(false)
const inviteSuccess = ref(false)
const inviteError = ref('')

let debounceTimer = null
function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchUsers, 300)
}

async function fetchUsers(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 20 }
    if (filters.search) params.search = filters.search
    if (filters.role) params.role = filters.role
    if (filters.is_active) params.is_active = filters.is_active

    const { data } = await api.get('/admin/users', { params })
    users.value = data.data
    pagination.currentPage = data.current_page
    pagination.lastPage = data.last_page
    pagination.from = data.from || 0
    pagination.to = data.to || 0
    pagination.total = data.total
  } catch (err) {
    console.error('Error fetching users:', err)
  } finally {
    loading.value = false
  }
}

function goToPage(page) {
  fetchUsers(page)
}

function getInitials(name) {
  return (name || '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function roleLabel(role) {
  const labels = { admin: 'Admin', presidencia: 'Presidencia', secretario: 'Secretario', sumo_consejo: 'Sumo Consejo' }
  return labels[role] || role
}

function roleBadgeClass(role) {
  const classes = { admin: 'badge-danger', presidencia: 'badge-primary', secretario: 'badge-primary', sumo_consejo: 'badge-success' }
  return classes[role] || 'badge-neutral'
}

function openEditModal(user) {
  editModal.user = user
  editModal.form.name = user.name
  editModal.form.phone = user.phone || ''
  editModal.form.role = user.role
  editModal.show = true
}

async function saveEdit() {
  editModal.saving = true
  try {
    await api.patch(`/admin/users/${editModal.user.id}`, editModal.form)
    editModal.show = false
    fetchUsers(pagination.currentPage)
  } catch (err) {
    console.error('Error saving user:', err)
  } finally {
    editModal.saving = false
  }
}

async function toggleActive(user) {
  try {
    await api.patch(`/admin/users/${user.id}`, { is_active: !user.is_active })
    fetchUsers(pagination.currentPage)
  } catch (err) {
    console.error('Error toggling user:', err)
  }
}

async function sendInvite() {
  inviteSending.value = true
  inviteError.value = ''
  inviteSuccess.value = false
  try {
    await api.post('/invitations', inviteForm)
    inviteSuccess.value = true
    inviteForm.email = ''
    inviteForm.role = 'sumo_consejo'
    setTimeout(() => { showInviteModal.value = false; inviteSuccess.value = false }, 1500)
  } catch (err) {
    inviteError.value = err.response?.data?.message || 'Error al enviar la invitación.'
  } finally {
    inviteSending.value = false
  }
}

onMounted(() => fetchUsers())
</script>
