<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-white">Integraciones</h1>
        <p class="text-surface-200/50 text-sm mt-1">Gestiona las API keys de los servicios externos</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="glass rounded-2xl p-12 text-center">
      <svg class="animate-spin w-8 h-8 mx-auto text-primary-400" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      <p class="text-surface-200/60 mt-3">Cargando integraciones...</p>
    </div>

    <!-- Integrations Grid -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-5 stagger">
      <div
        v-for="integration in integrations"
        :key="integration.id || integration.provider"
        class="glass rounded-2xl overflow-hidden animate-fade-in"
      >
        <!-- Card Header -->
        <div class="px-5 py-4 flex items-center justify-between border-b border-white/5">
          <div class="flex items-center gap-3">
            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center text-lg', providerIcon(integration.provider).bgClass]">
              {{ providerIcon(integration.provider).emoji }}
            </div>
            <div>
              <h3 class="font-semibold text-white">{{ integration.label }}</h3>
              <p class="text-xs text-surface-200/40">{{ providerDescription(integration.provider) }}</p>
            </div>
          </div>

          <!-- Status Badge -->
          <div class="flex items-center gap-2">
            <span :class="statusBadgeClass(integration.status)" class="px-2.5 py-1 rounded-full text-xs font-medium">
              {{ statusLabel(integration.status) }}
            </span>
            <!-- Toggle Active -->
            <button
              @click="toggleIntegration(integration)"
              :class="[
                'relative w-10 h-5 rounded-full transition-colors duration-200',
                integration.is_active ? 'bg-success' : 'bg-surface-700'
              ]"
              :title="integration.is_active ? 'Desactivar' : 'Activar'"
            >
              <span
                :class="[
                  'absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform duration-200',
                  integration.is_active ? 'translate-x-5' : 'translate-x-0'
                ]"
              />
            </button>
          </div>
        </div>

        <!-- Card Body -->
        <div class="px-5 py-4 space-y-4">
          <!-- API Key -->
          <div>
            <label class="block text-xs font-medium text-surface-200/60 mb-1.5">API Key</label>
            <div class="flex gap-2">
              <div class="flex-1 relative">
                <input
                  :type="showKey[integration.provider] ? 'text' : 'password'"
                  v-model="editKeys[integration.provider]"
                  :placeholder="integration.masked_key || 'Ingresa la API key...'"
                  class="w-full px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-sm placeholder-surface-200/25 focus:outline-none focus:ring-2 focus:ring-primary-500/50 transition-all font-mono"
                />
                <button
                  v-if="editKeys[integration.provider]"
                  @click="showKey[integration.provider] = !showKey[integration.provider]"
                  class="absolute right-2.5 top-1/2 -translate-y-1/2 text-surface-200/40 hover:text-white transition-colors"
                >
                  <svg v-if="!showKey[integration.provider]" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                  </svg>
                </button>
              </div>
              <button
                @click="saveKey(integration)"
                :disabled="!editKeys[integration.provider] || saving[integration.provider]"
                class="px-4 py-2 rounded-xl bg-primary-600 text-white text-sm font-medium hover:bg-primary-500 disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center gap-1.5"
              >
                <svg v-if="saving[integration.provider]" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                Guardar
              </button>
            </div>
            <p v-if="integration.masked_key && !editKeys[integration.provider]" class="text-xs text-surface-200/30 mt-1 font-mono">
              Actual: {{ integration.masked_key }}
            </p>
          </div>

          <!-- Config adicional (por proveedor) -->
          <div v-if="providerConfig(integration.provider)?.length" class="space-y-3">
            <label class="block text-xs font-medium text-surface-200/60">Configuración</label>
            <div v-for="field in providerConfig(integration.provider)" :key="field.key" class="flex items-center gap-3">
              <label class="text-xs text-surface-200/50 w-28 shrink-0">{{ field.label }}</label>
              <select
                v-if="field.type === 'select'"
                v-model="editConfig[integration.provider][field.key]"
                class="flex-1 px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/50 transition-all"
              >
                <option v-for="opt in field.options" :key="opt" :value="opt" class="bg-surface-900">{{ opt }}</option>
              </select>
              <input
                v-else
                v-model="editConfig[integration.provider][field.key]"
                :type="field.type"
                :placeholder="field.label"
                class="flex-1 px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-surface-200/25 focus:outline-none focus:ring-2 focus:ring-primary-500/50 transition-all"
              />
            </div>
          </div>

          <!-- Status Message -->
          <div
            v-if="integration.status_message"
            :class="[
              'text-xs px-3 py-2 rounded-lg',
              integration.status === 'valid' ? 'bg-success/10 text-success' :
              integration.status === 'invalid' ? 'bg-danger/10 text-danger' :
              'bg-accent-500/10 text-accent-400'
            ]"
          >
            {{ integration.status_message }}
          </div>

          <!-- Validated At -->
          <p v-if="integration.validated_at" class="text-xs text-surface-200/30">
            Última validación: {{ new Date(integration.validated_at).toLocaleString('es-CL') }}
            <span v-if="integration.updater">· por {{ integration.updater.name }}</span>
          </p>
        </div>

        <!-- Card Footer -->
        <div class="px-5 py-3 border-t border-white/5 flex items-center justify-between">
          <button
            @click="validateKey(integration)"
            :disabled="!integration.masked_key || validating[integration.id]"
            class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-sm font-medium transition-all disabled:opacity-40 disabled:cursor-not-allowed"
            :class="integration.masked_key ? 'text-accent-400 hover:bg-accent-500/10' : 'text-surface-200/30'"
          >
            <svg v-if="validating[integration.id]" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
            Validar conexión
          </button>

          <button
            @click="deleteIntegration(integration)"
            class="p-1.5 rounded-lg text-surface-200/30 hover:text-danger hover:bg-danger/10 transition-all"
            title="Eliminar integración"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <Transition name="toast">
      <div
        v-if="toast.visible"
        :class="[
          'fixed bottom-6 right-6 z-50 px-5 py-3 rounded-xl shadow-xl text-sm font-medium max-w-md animate-slide-in',
          toast.type === 'success' ? 'bg-success text-white' :
          toast.type === 'error' ? 'bg-danger text-white' :
          'bg-surface-700 text-white'
        ]"
      >
        {{ toast.message }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const integrations = ref([])
const providers = ref([])
const editKeys = reactive({})
const editConfig = reactive({})
const showKey = reactive({})
const saving = reactive({})
const validating = reactive({})
const toast = reactive({ visible: false, message: '', type: 'success' })

onMounted(async () => {
  await fetchIntegrations()
})

async function fetchIntegrations() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/integrations')
    integrations.value = data.data
    providers.value = data.providers

    // Inicializar estado de edición para cada integración
    for (const item of data.data) {
      editKeys[item.provider] = ''
      editConfig[item.provider] = { ...(item.config || {}) }
      showKey[item.provider] = false
      saving[item.provider] = false
    }
  } catch (err) {
    showToast('Error al cargar integraciones', 'error')
  } finally {
    loading.value = false
  }
}

async function saveKey(integration) {
  saving[integration.provider] = true
  try {
    const payload = {
      provider: integration.provider,
      label: integration.label,
      api_key: editKeys[integration.provider] || undefined,
      config: editConfig[integration.provider],
      is_active: integration.is_active,
    }
    const { data } = await api.post('/admin/integrations', payload)
    showToast(data.message, 'success')
    editKeys[integration.provider] = ''
    await fetchIntegrations()
  } catch (err) {
    showToast(err.response?.data?.message || 'Error al guardar', 'error')
  } finally {
    saving[integration.provider] = false
  }
}

async function validateKey(integration) {
  validating[integration.id] = true
  try {
    const { data } = await api.post(`/admin/integrations/${integration.id}/validate`)
    showToast(data.message, data.data.status === 'valid' ? 'success' : 'error')
    await fetchIntegrations()
  } catch (err) {
    showToast(err.response?.data?.message || 'Error al validar', 'error')
  } finally {
    validating[integration.id] = false
  }
}

async function toggleIntegration(integration) {
  try {
    const { data } = await api.post(`/admin/integrations/${integration.id}/toggle`)
    showToast(data.message, 'success')
    await fetchIntegrations()
  } catch (err) {
    showToast('Error al cambiar estado', 'error')
  }
}

async function deleteIntegration(integration) {
  if (!confirm(`¿Eliminar la integración "${integration.label}"?`)) return
  try {
    const { data } = await api.delete(`/admin/integrations/${integration.id}`)
    showToast(data.message, 'success')
    await fetchIntegrations()
  } catch (err) {
    showToast('Error al eliminar', 'error')
  }
}

function providerIcon(provider) {
  const icons = {
    openai: { emoji: '🤖', bgClass: 'bg-emerald-500/15' },
    twilio: { emoji: '💬', bgClass: 'bg-red-500/15' },
  }
  return icons[provider] || { emoji: '🔌', bgClass: 'bg-primary-500/15' }
}

function providerDescription(provider) {
  const p = providers.value.find(p => p.provider === provider)
  return p?.description || ''
}

function providerConfig(provider) {
  const p = providers.value.find(p => p.provider === provider)
  if (!p?.config_schema) return []
  return Object.entries(p.config_schema).map(([key, schema]) => ({ key, ...schema }))
}

function statusBadgeClass(status) {
  return {
    valid: 'bg-success/15 text-success',
    invalid: 'bg-danger/15 text-danger',
    expired: 'bg-accent-500/15 text-accent-400',
    pending: 'bg-surface-700/50 text-surface-200/50',
  }[status] || 'bg-surface-700/50 text-surface-200/50'
}

function statusLabel(status) {
  return { valid: 'Válida', invalid: 'Inválida', expired: 'Expirada', pending: 'Pendiente' }[status] || status
}

function showToast(message, type = 'success') {
  toast.message = message
  toast.type = type
  toast.visible = true
  setTimeout(() => { toast.visible = false }, 4000)
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(16px);
}
</style>
