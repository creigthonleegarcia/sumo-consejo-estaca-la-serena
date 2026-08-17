<template>
  <div class="space-y-6 animate-fade-in">
    <div>
      <h1 class="text-xl font-bold text-gray-950">Informes de Mayordomía</h1>
      <p class="text-sm text-gray-600 mt-0.5">Historial de informes por período</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="card p-8 text-center">
      <svg class="animate-spin w-6 h-6 mx-auto text-primary-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
    </div>

    <!-- Reports List -->
    <div v-else class="space-y-3 stagger">
      <div v-for="report in reports" :key="report.id" class="card p-5 animate-fade-in hover:shadow-detached transition-shadow" style="transition-duration: 200ms;">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
          <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-full bg-primary-100 flex items-center justify-center text-[10px] font-bold text-primary-600">
              {{ getInitials(report.user?.name) }}
            </div>
            <span class="text-sm font-medium text-gray-950">{{ report.user?.name }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="badge badge-primary">{{ report.assignment?.title }}</span>
            <span class="text-xs text-gray-500">{{ formatDate(report.period_start) }} — {{ formatDate(report.period_end) }}</span>
          </div>
        </div>
        <p class="text-sm text-gray-600 whitespace-pre-line">{{ report.content || 'Pendiente de completar.' }}</p>
        <p v-if="report.submitted_at" class="text-xs text-gray-400 mt-2">Enviado {{ formatDateTime(report.submitted_at) }}</p>
      </div>
    </div>

    <!-- Empty -->
    <div v-if="!loading && reports.length === 0" class="card p-8 text-center">
      <div class="w-12 h-12 mx-auto rounded-lg bg-gray-100 flex items-center justify-center mb-3">
        <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75z" /></svg>
      </div>
      <p class="text-gray-500 text-sm">No hay informes disponibles.</p>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.lastPage > 1" class="flex justify-center gap-1">
      <button v-for="page in pagination.lastPage" :key="page" @click="fetchReports(page)"
        class="px-3 py-1.5 text-sm rounded-md transition-all interactive"
        :class="page === pagination.currentPage ? 'bg-primary-500 text-white' : 'text-gray-600 hover:bg-gray-100'">{{ page }}</button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const reports = ref([])
const pagination = reactive({ currentPage: 1, lastPage: 1 })

async function fetchReports(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/reports', { params: { page } })
    reports.value = data.data
    pagination.currentPage = data.current_page
    pagination.lastPage = data.last_page
  } catch (err) { console.error(err) }
  finally { loading.value = false }
}

function getInitials(name) { return (name || '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) }
function formatDate(d) { return new Date(d).toLocaleDateString('es-CL', { day: 'numeric', month: 'short' }) }
function formatDateTime(d) { return new Date(d).toLocaleDateString('es-CL', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }

onMounted(() => fetchReports())
</script>
