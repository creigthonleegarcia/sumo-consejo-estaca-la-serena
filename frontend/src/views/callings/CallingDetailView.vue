<template>
  <div class="space-y-6 animate-fade-in">
    <router-link to="/callings" class="inline-flex items-center gap-1 text-sm text-primary-500 hover:text-primary-600 transition-colors interactive">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
      Volver a llamamientos
    </router-link>

    <div v-if="loading" class="card p-8 text-center">
      <svg class="animate-spin w-6 h-6 mx-auto text-primary-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
    </div>

    <template v-else-if="calling">
      <!-- Header -->
      <div class="card p-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="flex items-center gap-2 mb-2">
              <span class="badge" :class="statusClass(calling.status)">{{ statusLabel(calling.status) }}</span>
              <span v-if="calling.is_voting_open" class="text-xs text-gray-500">Cierra {{ formatDate(calling.voting_deadline) }}</span>
            </div>
            <h1 class="text-xl font-bold text-gray-950">{{ calling.member_name }}</h1>
            <p class="text-gray-600 mt-0.5">{{ calling.calling_name }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ calling.ward }}</p>
          </div>
          <div class="text-right shrink-0">
            <p class="text-xs text-gray-500">Propuesto por</p>
            <p class="text-sm font-medium text-gray-950">{{ calling.proposer?.name }}</p>
          </div>
        </div>
        <p v-if="calling.notes" class="text-sm text-gray-600 mt-4 pt-4 border-t border-gray-100">{{ calling.notes }}</p>
      </div>

      <!-- Voting Panel -->
      <div class="card p-6">
        <h2 class="font-semibold text-gray-950 mb-4">Resultado de Votación</h2>

        <!-- Progress -->
        <div class="space-y-3">
          <div>
            <div class="flex justify-between text-sm mb-1">
              <span class="font-medium text-success">A favor: {{ calling.approval_count }}</span>
              <span class="text-gray-500">{{ calling.eligible_voters }} elegibles</span>
            </div>
            <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full bg-success rounded-full transition-all" :style="{ width: progressWidth('approve') }" style="transition-duration: 600ms;"></div>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-sm mb-1">
              <span class="font-medium text-danger">En contra: {{ calling.disapproval_count }}</span>
              <span class="text-gray-500">Quórum: 2/3</span>
            </div>
            <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full bg-danger rounded-full transition-all" :style="{ width: progressWidth('disapprove') }" style="transition-duration: 600ms;"></div>
            </div>
          </div>
        </div>

        <!-- Quorum indicator -->
        <div class="mt-4 p-3 rounded-md" :class="calling.has_quorum ? 'bg-success-light' : 'bg-gray-50'">
          <p class="text-sm font-medium" :class="calling.has_quorum ? 'text-success' : 'text-gray-600'">
            {{ calling.has_quorum ? '✓ Quórum alcanzado (2/3)' : 'Quórum pendiente (requiere 2/3 de aprobación)' }}
          </p>
        </div>

        <!-- Vote Actions -->
        <div v-if="calling.is_voting_open && !calling.has_user_voted" class="mt-6 pt-4 border-t border-gray-200">
          <p class="text-sm text-gray-600 mb-3">Emite tu voto:</p>
          <div v-if="voteError" class="alert alert-danger mb-3">{{ voteError }}</div>
          <div class="flex gap-3">
            <button @click="castVote('approve')" :disabled="voting" class="btn btn-primary flex-1">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.5c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3a.75.75 0 01.75-.75A2.25 2.25 0 0116.5 4.5c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 00-1.423-.23H5.904M14.25 9h2.25M5.904 18.75c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 01-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 10.064 4.258 9.75 5.13 9.75h.224c.424 0 .718.423.55.82a6.97 6.97 0 00-.55 2.725L5.354 18.75z" /></svg>
              Aprobar
            </button>
            <button @click="castVote('disapprove')" :disabled="voting" class="btn btn-danger flex-1">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 15h2.25m8.024-9.75c.011.05.028.1.052.148.591 1.2.924 2.55.924 3.977a8.96 8.96 0 01-.999 4.125m.023-8.25c-.076-.365.183-.75.575-.75h.908c.889 0 1.713.518 1.972 1.368.339 1.11.521 2.287.521 3.507 0 1.553-.295 3.036-.831 4.398-.306.774-1.086 1.227-1.918 1.227h-.37c-.669 0-1.098-.655-.846-1.268a6.97 6.97 0 00.551-2.725v-.846c0-.476-.38-.847-.846-.847h-2.508c-1.074 0-2.014-.756-2.146-1.82a4.998 4.998 0 01.09-1.707 9.05 9.05 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715.276-.69.429-1.436.429-2.195v-.494L14.25 3A2.25 2.25 0 0012 5.25v.894c0 .372-.178.718-.484.945l-4.83 3.576" /></svg>
              Rechazar
            </button>
          </div>
        </div>

        <div v-else-if="calling.has_user_voted" class="mt-4 pt-4 border-t border-gray-200">
          <div class="alert alert-info">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>Tu voto ya fue registrado.</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const loading = ref(true)
const calling = ref(null)
const voting = ref(false)
const voteError = ref('')

async function fetchCalling() {
  loading.value = true
  try {
    const { data } = await api.get(`/callings/${route.params.id}`)
    calling.value = data.data
  } catch (err) { console.error(err) }
  finally { loading.value = false }
}

async function castVote(vote) {
  voting.value = true; voteError.value = ''
  try {
    await api.post(`/callings/${route.params.id}/vote`, { vote })
    fetchCalling()
  } catch (err) { voteError.value = err.response?.data?.message || 'Error al votar.' }
  finally { voting.value = false }
}

function progressWidth(type) {
  if (!calling.value?.eligible_voters) return '0%'
  const count = type === 'approve' ? calling.value.approval_count : calling.value.disapproval_count
  return `${(count / calling.value.eligible_voters) * 100}%`
}

function statusLabel(s) { return { pending: 'En votación', approved: 'Aprobado', rejected: 'Rechazado', cancelled: 'Cancelado' }[s] || s }
function statusClass(s) { return { pending: 'badge-warning', approved: 'badge-success', rejected: 'badge-danger', cancelled: 'badge-neutral' }[s] || 'badge-neutral' }
function formatDate(d) { return new Date(d).toLocaleDateString('es-CL', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }

onMounted(fetchCalling)
</script>
