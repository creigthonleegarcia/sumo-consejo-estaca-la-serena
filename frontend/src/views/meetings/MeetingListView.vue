<template>
  <div class="space-y-6 animate-fade-in">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-gray-950">Reuniones</h1>
        <p class="text-sm text-gray-600 mt-0.5">Convocatorias y actas del consejo</p>
      </div>
      <router-link v-if="canCreate" to="/meetings/create" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Nueva reunión
      </router-link>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="flex flex-col sm:flex-row gap-3">
        <select v-model="filters.type" @change="fetchMeetings" class="input-field sm:w-44">
          <option value="">Todos los tipos</option>
          <option value="presidencia">Presidencia</option>
          <option value="sumo_consejo">Sumo Consejo</option>
        </select>
        <select v-model="filters.status" @change="fetchMeetings" class="input-field sm:w-44">
          <option value="">Todos los estados</option>
          <option value="scheduled">Programada</option>
          <option value="in_progress">En curso</option>
          <option value="completed">Completada</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="card p-8 text-center">
      <svg class="animate-spin w-6 h-6 mx-auto text-primary-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
    </div>

    <!-- Meetings List -->
    <div v-else class="space-y-3 stagger">
      <router-link
        v-for="m in meetings" :key="m.id"
        :to="`/meetings/${m.id}`"
        class="card p-5 flex flex-col sm:flex-row sm:items-center gap-4 hover:shadow-detached transition-all group animate-fade-in interactive"
      >
        <!-- Date -->
        <div class="w-14 h-14 rounded-lg bg-primary-50 flex flex-col items-center justify-center shrink-0">
          <span class="text-lg font-bold text-primary-600 leading-none">{{ dayOf(m.scheduled_at) }}</span>
          <span class="text-[10px] font-semibold text-primary-500 uppercase">{{ monthOf(m.scheduled_at) }}</span>
        </div>
        <!-- Info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-0.5">
            <h3 class="font-semibold text-gray-950 group-hover:text-primary-600 transition-colors truncate">{{ m.name }}</h3>
            <span class="badge" :class="meetingStatusClass(m.status)">{{ meetingStatusLabel(m.status) }}</span>
          </div>
          <div class="flex items-center gap-3 text-xs text-gray-500">
            <span class="badge badge-neutral">{{ m.type === 'presidencia' ? 'Presidencia' : 'Sumo Consejo' }}</span>
            <span>{{ m.modality === 'presencial' ? 'Presencial' : m.modality === 'virtual' ? 'Virtual' : 'Híbrida' }}</span>
            <span>{{ timeOf(m.scheduled_at) }}</span>
          </div>
        </div>
        <!-- RSVP -->
        <div class="text-right shrink-0">
          <p class="text-sm font-semibold text-gray-950">{{ m.attending_count }}/{{ m.invitations_count }}</p>
          <p class="text-xs text-gray-500">confirmados</p>
        </div>
      </router-link>
    </div>

    <div v-if="!loading && meetings.length === 0" class="card p-8 text-center">
      <div class="w-12 h-12 mx-auto rounded-lg bg-gray-100 flex items-center justify-center mb-3">
        <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
      </div>
      <p class="text-gray-500 text-sm">No hay reuniones programadas.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const auth = useAuthStore()
const canCreate = computed(() => ['admin', 'presidencia', 'secretario'].includes(auth.user?.role))
const loading = ref(true)
const meetings = ref([])
const filters = reactive({ type: '', status: '' })

async function fetchMeetings() {
  loading.value = true
  try {
    const params = {}
    if (filters.type) params.type = filters.type
    if (filters.status) params.status = filters.status
    const { data } = await api.get('/meetings', { params })
    meetings.value = data.data
  } catch (err) { console.error(err) }
  finally { loading.value = false }
}

function dayOf(d) { return new Date(d).getDate() }
function monthOf(d) { return new Date(d).toLocaleDateString('es-CL', { month: 'short' }) }
function timeOf(d) { return new Date(d).toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' }) }
function meetingStatusLabel(s) { return { scheduled: 'Programada', in_progress: 'En curso', completed: 'Completada', cancelled: 'Cancelada' }[s] || s }
function meetingStatusClass(s) { return { scheduled: 'badge-primary', in_progress: 'badge-warning', completed: 'badge-success', cancelled: 'badge-neutral' }[s] || 'badge-neutral' }

onMounted(fetchMeetings)
</script>
