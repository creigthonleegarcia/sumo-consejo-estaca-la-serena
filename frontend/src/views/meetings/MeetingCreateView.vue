<template>
  <div class="space-y-6 animate-fade-in">
    <router-link to="/meetings" class="inline-flex items-center gap-1 text-sm text-primary-500 hover:text-primary-600 transition-colors interactive">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
      Volver a reuniones
    </router-link>

    <div class="card p-6">
      <h1 class="text-xl font-bold text-gray-950 mb-6">Nueva Reunión</h1>
      <form @submit.prevent="createMeeting" class="space-y-5">
        <div v-if="error" class="alert alert-danger">{{ error }}</div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <label class="input-label">Nombre</label>
            <input v-model="form.name" type="text" class="input-field" placeholder="Ej: Consejo de Estaca - Agosto" required />
          </div>
          <div>
            <label class="input-label">Tipo</label>
            <select v-model="form.type" class="input-field" required>
              <option value="presidencia">Presidencia</option>
              <option value="sumo_consejo">Sumo Consejo</option>
            </select>
          </div>
          <div>
            <label class="input-label">Modalidad</label>
            <select v-model="form.modality" class="input-field" required>
              <option value="presencial">Presencial</option>
              <option value="virtual">Virtual</option>
              <option value="hibrida">Híbrida</option>
            </select>
          </div>
          <div>
            <label class="input-label">{{ form.modality === 'virtual' ? 'URL de reunión' : 'Ubicación' }}</label>
            <input v-model="form.location_or_url" type="text" class="input-field" :placeholder="form.modality === 'virtual' ? 'https://zoom.us/...' : 'Centro de estaca'" />
          </div>
          <div>
            <label class="input-label">Fecha y hora</label>
            <input v-model="form.scheduled_at" type="datetime-local" class="input-field" required />
          </div>
          <div class="sm:col-span-2">
            <label class="input-label">Agenda (opcional)</label>
            <textarea v-model="form.agenda" class="input-field" rows="4" placeholder="1. Oración de apertura&#10;2. Revisión de acuerdos&#10;3. ..."></textarea>
          </div>
        </div>

        <!-- Invitees -->
        <div>
          <label class="input-label">Invitar miembros</label>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 mt-2">
            <label v-for="u in members" :key="u.id" class="flex items-center gap-2 p-2 rounded-md hover:bg-gray-50 cursor-pointer transition-colors">
              <input type="checkbox" v-model="selectedUserIds" :value="u.id" class="w-4 h-4 rounded border-gray-300 text-primary-500 focus:ring-primary-500" />
              <span class="text-sm text-gray-900">{{ u.name }}</span>
              <span class="badge badge-neutral text-[10px] ml-auto">{{ roleLabel(u.role) }}</span>
            </label>
          </div>
        </div>

        <div class="flex gap-3 pt-2">
          <router-link to="/meetings" class="btn btn-ghost flex-1">Cancelar</router-link>
          <button type="submit" :disabled="creating" class="btn btn-primary flex-1">
            {{ creating ? 'Creando...' : 'Crear reunión' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const creating = ref(false)
const error = ref('')
const members = ref([])
const selectedUserIds = ref([])

const form = reactive({
  name: '', type: 'sumo_consejo', modality: 'presencial',
  location_or_url: '', platform: '', agenda: '', scheduled_at: '',
})

async function fetchMembers() {
  try {
    const { data } = await api.get('/admin/users', { params: { per_page: 100, is_active: true } })
    members.value = data.data
  } catch { /* non-admin fallback */ }
}

async function createMeeting() {
  creating.value = true; error.value = ''
  try {
    const { data } = await api.post('/meetings', form)
    const meetingId = data.data.id

    if (selectedUserIds.value.length) {
      await api.post(`/meetings/${meetingId}/invite`, { user_ids: selectedUserIds.value })
    }

    router.push(`/meetings/${meetingId}`)
  } catch (err) { error.value = err.response?.data?.message || 'Error al crear.' }
  finally { creating.value = false }
}

function roleLabel(r) { return { admin: 'Admin', presidencia: 'Pres.', secretario: 'Sec.', sumo_consejo: 'SC' }[r] || r }

onMounted(fetchMembers)
</script>
