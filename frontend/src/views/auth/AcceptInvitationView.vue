<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4 py-8">
    <div class="w-full max-w-sm animate-fade-in">
      <div class="text-center mb-8">
        <div class="w-14 h-14 mx-auto rounded-lg bg-primary-500 flex items-center justify-center shadow-sm mb-4">
          <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
          </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-950">Invitación</h1>
        <p class="text-gray-600 text-sm mt-0.5">Has sido invitado al Sumo Consejo</p>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="card p-8 text-center">
        <svg class="animate-spin w-8 h-8 mx-auto text-primary-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <p class="text-gray-500 mt-3 text-sm">Verificando invitación...</p>
      </div>

      <!-- Error -->
      <div v-else-if="invalidToken" class="card p-8 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-danger-light flex items-center justify-center mb-3">
          <svg class="w-6 h-6 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <p class="text-gray-950 font-semibold">Invitación inválida</p>
        <p class="text-gray-500 text-sm mt-1">Este enlace ha expirado o ya fue utilizado.</p>
        <router-link to="/login" class="inline-block mt-4 text-primary-500 hover:text-primary-600 text-sm font-medium">
          Ir al inicio de sesión →
        </router-link>
      </div>

      <!-- Accept Form -->
      <form v-else @submit.prevent="handleAccept" class="card p-6 space-y-5">
        <h2 class="text-base font-semibold text-gray-950 text-center">Crear tu cuenta</h2>

        <div v-if="invitation" class="alert alert-info">
          <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
          </svg>
          <span>Invitado como <strong class="font-semibold">{{ invitation.role_label }}</strong></span>
        </div>

        <div v-if="error" class="alert alert-danger animate-fade-in">
          <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
          </svg>
          <span>{{ error }}</span>
        </div>

        <div>
          <label for="accept-name" class="input-label">Nombre completo</label>
          <input id="accept-name" v-model="form.name" type="text" required class="input-field" placeholder="Juan Pérez" />
        </div>

        <div>
          <label for="accept-phone" class="input-label">Teléfono (opcional)</label>
          <input id="accept-phone" v-model="form.phone" type="tel" class="input-field" placeholder="+56 9 1234 5678" />
        </div>

        <div>
          <label for="accept-password" class="input-label">Contraseña</label>
          <input id="accept-password" v-model="form.password" type="password" required minlength="8" class="input-field" placeholder="Mínimo 8 caracteres" />
        </div>

        <div>
          <label for="accept-password-confirm" class="input-label">Confirmar contraseña</label>
          <input id="accept-password-confirm" v-model="form.password_confirmation" type="password" required class="input-field" placeholder="Repite la contraseña" />
        </div>

        <button type="submit" :disabled="submitting" class="btn btn-primary w-full btn-lg">
          <span v-if="!submitting">Crear cuenta</span>
          <span v-else class="flex items-center justify-center gap-2">
            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            Creando...
          </span>
        </button>
      </form>

      <p class="text-center text-xs text-gray-500 mt-6">
        La Iglesia de Jesucristo de los Santos de los Últimos Días
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const invalidToken = ref(false)
const invitation = ref(null)
const submitting = ref(false)
const error = ref('')

const form = reactive({
  name: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

onMounted(async () => {
  try {
    const { data } = await api.get(`/invitations/${route.params.token}`)
    invitation.value = data.data
  } catch {
    invalidToken.value = true
  } finally {
    loading.value = false
  }
})

async function handleAccept() {
  try {
    submitting.value = true
    error.value = ''
    await api.post(`/invitations/${route.params.token}/accept`, form)
    router.push({ name: 'login', query: { registered: '1' } })
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al crear la cuenta.'
  } finally {
    submitting.value = false
  }
}
</script>
