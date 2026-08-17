<template>
  <div class="space-y-6 animate-fade-in">
    <router-link to="/meetings" class="inline-flex items-center gap-1 text-sm text-primary-500 hover:text-primary-600 transition-colors interactive">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
      Volver a reuniones
    </router-link>

    <div v-if="loading" class="card p-8 text-center">
      <svg class="animate-spin w-6 h-6 mx-auto text-primary-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
    </div>

    <template v-else-if="meeting">
      <!-- Header -->
      <div class="card p-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="flex items-center gap-2 mb-2">
              <span class="badge" :class="statusClass(meeting.status)">{{ statusLabel(meeting.status) }}</span>
              <span class="badge badge-neutral">{{ meeting.type === 'presidencia' ? 'Presidencia' : 'Sumo Consejo' }}</span>
              <span class="badge badge-neutral">{{ modalityLabel(meeting.modality) }}</span>
            </div>
            <h1 class="text-xl font-bold text-gray-950">{{ meeting.name }}</h1>
            <p class="text-sm text-gray-600 mt-1">{{ formatDateTime(meeting.scheduled_at) }}</p>
            <p v-if="meeting.location_or_url" class="text-sm text-gray-500 mt-0.5">{{ meeting.location_or_url }}</p>
          </div>
        </div>
        <div v-if="meeting.agenda" class="mt-4 pt-4 border-t border-gray-100">
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Agenda</h3>
          <p class="text-sm text-gray-700 whitespace-pre-line">{{ meeting.agenda }}</p>
        </div>
      </div>

      <!-- Attendees -->
      <div class="card">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="font-semibold text-gray-950">Invitados ({{ meeting.invitations?.length || 0 }})</h2>
        </div>
        <div class="divide-y divide-gray-100">
          <div v-for="inv in meeting.invitations" :key="inv.id" class="px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-xs font-bold text-primary-600">
                {{ getInitials(inv.user?.name) }}
              </div>
              <div>
                <p class="text-sm font-medium text-gray-950">{{ inv.user?.name }}</p>
                <p class="text-xs text-gray-500">{{ inv.user?.role }}</p>
              </div>
            </div>
            <span class="badge" :class="rsvpClass(inv.response)">{{ rsvpLabel(inv.response) }}</span>
          </div>
        </div>

        <!-- RSVP Actions -->
        <div v-if="myInvitation" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
          <p class="text-sm text-gray-600 mb-3">Tu respuesta:</p>
          <div class="flex gap-2">
            <button @click="rsvp('attending')" :disabled="rsvping" class="btn btn-sm" :class="myInvitation.response === 'attending' ? 'btn-primary' : 'btn-ghost'">Asistiré</button>
            <button @click="rsvp('tentative')" :disabled="rsvping" class="btn btn-sm" :class="myInvitation.response === 'tentative' ? 'btn-primary' : 'btn-ghost'">Quizás</button>
            <button @click="rsvp('declined')" :disabled="rsvping" class="btn btn-sm" :class="myInvitation.response === 'declined' ? 'btn-danger' : 'btn-ghost'">No asistiré</button>
          </div>
        </div>
      </div>

      <!-- Audio Upload & Minute -->
      <div class="card p-6">
        <h2 class="font-semibold text-gray-950 mb-4">Acta de Reunión</h2>

        <!-- Existing minute -->
        <div v-if="meeting.minute?.status === 'completed'" class="prose prose-sm max-w-none text-gray-700">
          <div v-html="renderMarkdown(meeting.minute.generated_minute)"></div>
        </div>

        <div v-else-if="meeting.minute?.status === 'processing'" class="alert alert-info">
          <svg class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
          <span>Procesando audio... El acta se generará automáticamente.</span>
        </div>

        <div v-else-if="meeting.minute?.status === 'error'" class="alert alert-warning">
          <span>Error al procesar el audio. Intente subir nuevamente.</span>
        </div>

        <!-- Upload -->
        <div v-if="!meeting.minute || meeting.minute.status === 'error'" class="mt-4">
          <label class="block">
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-primary-400 transition-colors cursor-pointer" :class="{ 'border-primary-400 bg-primary-50': dragover }" @dragover.prevent="dragover = true" @dragleave="dragover = false" @drop.prevent="handleDrop">
              <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
              <p class="text-sm text-gray-600">Arrastra un archivo de audio o <span class="text-primary-500 font-medium">haz clic para seleccionar</span></p>
              <p class="text-xs text-gray-400 mt-1">MP3, WAV, M4A, OGG — máx. 100MB</p>
              <input type="file" accept=".mp3,.wav,.m4a,.ogg,.webm" class="hidden" @change="handleFileSelect" ref="fileInput" />
            </div>
          </label>
          <div v-if="uploading" class="mt-3 alert alert-info">
            <svg class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
            <span>Subiendo audio...</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const route = useRoute()
const auth = useAuthStore()
const loading = ref(true)
const meeting = ref(null)
const rsvping = ref(false)
const uploading = ref(false)
const dragover = ref(false)
const fileInput = ref(null)

const myInvitation = computed(() => {
  return meeting.value?.invitations?.find(i => i.user?.id === auth.user?.id)
})

async function fetchMeeting() {
  loading.value = true
  try {
    const { data } = await api.get(`/meetings/${route.params.id}`)
    meeting.value = data.data
  } catch (err) { console.error(err) }
  finally { loading.value = false }
}

async function rsvp(response) {
  rsvping.value = true
  try {
    await api.patch(`/meetings/${route.params.id}/rsvp`, { response })
    fetchMeeting()
  } catch (err) { console.error(err) }
  finally { rsvping.value = false }
}

async function uploadAudio(file) {
  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('audio', file)
    await api.post(`/meetings/${route.params.id}/upload-audio`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    fetchMeeting()
  } catch (err) { console.error(err) }
  finally { uploading.value = false }
}

function handleFileSelect(e) { if (e.target.files[0]) uploadAudio(e.target.files[0]) }
function handleDrop(e) { dragover.value = false; if (e.dataTransfer.files[0]) uploadAudio(e.dataTransfer.files[0]) }

function renderMarkdown(md) {
  if (!md) return ''
  return md.replace(/^### (.*$)/gm, '<h3 class="text-base font-semibold text-gray-950 mt-4 mb-2">$1</h3>')
    .replace(/^## (.*$)/gm, '<h2 class="text-lg font-bold text-gray-950 mt-5 mb-2">$1</h2>')
    .replace(/^- (.*$)/gm, '<li class="ml-4">$1</li>')
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\n/g, '<br>')
}

function getInitials(name) { return (name || '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) }
function formatDateTime(d) { return new Date(d).toLocaleDateString('es-CL', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }
function statusLabel(s) { return { scheduled: 'Programada', in_progress: 'En curso', completed: 'Completada', cancelled: 'Cancelada' }[s] || s }
function statusClass(s) { return { scheduled: 'badge-primary', in_progress: 'badge-warning', completed: 'badge-success', cancelled: 'badge-neutral' }[s] || 'badge-neutral' }
function modalityLabel(m) { return { presencial: 'Presencial', virtual: 'Virtual', hibrida: 'Híbrida' }[m] || m }
function rsvpLabel(r) { return { attending: 'Asistirá', declined: 'No asistirá', tentative: 'Quizás', pending: 'Pendiente' }[r] || r }
function rsvpClass(r) { return { attending: 'badge-success', declined: 'badge-danger', tentative: 'badge-warning', pending: 'badge-neutral' }[r] || 'badge-neutral' }

onMounted(fetchMeeting)
</script>
