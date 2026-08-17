<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Back -->
    <router-link to="/assignments" class="inline-flex items-center gap-1 text-sm text-primary-500 hover:text-primary-600 transition-colors interactive">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
      Volver a asignaciones
    </router-link>

    <!-- Loading -->
    <div v-if="loading" class="card p-8 text-center">
      <svg class="animate-spin w-6 h-6 mx-auto text-primary-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
    </div>

    <template v-else-if="assignment">
      <!-- Header Card -->
      <div class="card p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span class="badge" :class="statusBadgeClass(assignment.status)">{{ statusLabel(assignment.status) }}</span>
              <span v-if="assignment.due_date" class="text-xs text-gray-500">Vence: {{ formatDate(assignment.due_date) }}</span>
            </div>
            <h1 class="text-xl font-bold text-gray-950">{{ assignment.title }}</h1>
            <p v-if="assignment.description" class="text-gray-600 text-sm mt-2">{{ assignment.description }}</p>
          </div>
          <div class="flex items-center gap-3 shrink-0">
            <div class="text-right">
              <p class="text-xs text-gray-500">Asignado a</p>
              <p class="text-sm font-medium text-gray-950">{{ assignment.assignee?.name }}</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-primary-500 flex items-center justify-center text-sm font-bold text-white">
              {{ getInitials(assignment.assignee?.name) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Reports Section -->
      <div class="card">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h2 class="font-semibold text-gray-950">Informes de Mayordomía</h2>
          <button @click="showReportForm = !showReportForm" class="btn btn-sm btn-secondary">
            {{ showReportForm ? 'Cancelar' : 'Nuevo informe' }}
          </button>
        </div>

        <!-- Report Form -->
        <transition enter-active-class="transition-all duration-250" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-96" leave-active-class="transition-all duration-150" leave-from-class="opacity-100 max-h-96" leave-to-class="opacity-0 max-h-0">
          <div v-if="showReportForm" class="p-6 border-b border-gray-200 bg-gray-50 overflow-hidden">
            <div class="space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="input-label">Período inicio</label>
                  <input v-model="reportForm.period_start" type="date" class="input-field" />
                </div>
                <div>
                  <label class="input-label">Período fin</label>
                  <input v-model="reportForm.period_end" type="date" class="input-field" />
                </div>
              </div>
              <div>
                <label class="input-label">Contenido del informe</label>
                <textarea v-model="reportForm.content" class="input-field" rows="5" placeholder="Describe las actividades realizadas, logros y desafíos..."></textarea>
              </div>
              <!-- AI Button -->
              <div class="flex items-center gap-3">
                <button @click="improveWithAi" :disabled="improving || !reportForm.content" class="btn btn-sm btn-ghost text-primary-500">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" /></svg>
                  {{ improving ? 'Mejorando...' : 'Mejorar con IA' }}
                </button>
                <button @click="submitReport" :disabled="submittingReport" class="btn btn-sm btn-primary">
                  {{ submittingReport ? 'Enviando...' : 'Enviar informe' }}
                </button>
              </div>
              <div v-if="aiError" class="alert alert-warning">{{ aiError }}</div>
            </div>
          </div>
        </transition>

        <!-- Reports List -->
        <div class="divide-y divide-gray-100">
          <div v-for="report in assignment.reports" :key="report.id" class="px-6 py-4">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-full bg-success-light flex items-center justify-center">
                  <svg class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span class="text-sm font-medium text-gray-950">{{ report.user?.name }}</span>
              </div>
              <span class="text-xs text-gray-500">
                {{ formatDate(report.period_start) }} — {{ formatDate(report.period_end) }}
              </span>
            </div>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ report.content || 'Sin contenido aún.' }}</p>
          </div>
        </div>

        <!-- Empty Reports -->
        <div v-if="assignment.reports?.length === 0" class="p-8 text-center">
          <p class="text-gray-500 text-sm">Aún no hay informes para esta asignación.</p>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const loading = ref(true)
const assignment = ref(null)
const showReportForm = ref(false)
const submittingReport = ref(false)
const improving = ref(false)
const aiError = ref('')
const reportForm = reactive({ content: '', period_start: '', period_end: '' })

async function fetchAssignment() {
  loading.value = true
  try {
    const { data } = await api.get(`/assignments/${route.params.id}`)
    assignment.value = data.data
  } catch (err) { console.error(err) }
  finally { loading.value = false }
}

async function submitReport() {
  submittingReport.value = true
  try {
    await api.post('/reports', { ...reportForm, assignment_id: route.params.id })
    showReportForm.value = false
    Object.assign(reportForm, { content: '', period_start: '', period_end: '' })
    fetchAssignment()
  } catch (err) { console.error(err) }
  finally { submittingReport.value = false }
}

async function improveWithAi() {
  improving.value = true; aiError.value = ''
  try {
    const { data } = await api.post('/ai/improve-text', { text: reportForm.content, context: 'informe de mayordomía' })
    reportForm.content = data.data.improved_text
  } catch (err) { aiError.value = err.response?.data?.message || 'Error al mejorar texto.' }
  finally { improving.value = false }
}

function getInitials(name) { return (name || '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) }
function formatDate(d) { return new Date(d).toLocaleDateString('es-CL', { day: 'numeric', month: 'short', year: 'numeric' }) }
function statusLabel(s) { return { pending: 'Pendiente', in_progress: 'En progreso', completed: 'Completada', cancelled: 'Cancelada' }[s] || s }
function statusBadgeClass(s) { return { pending: 'badge-warning', in_progress: 'badge-primary', completed: 'badge-success', cancelled: 'badge-neutral' }[s] || 'badge-neutral' }

onMounted(fetchAssignment)
</script>
