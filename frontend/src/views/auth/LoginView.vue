<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4 py-8">
    <div class="w-full max-w-sm animate-fade-in">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="w-14 h-14 mx-auto rounded-lg bg-primary-500 flex items-center justify-center shadow-sm mb-4">
          <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21" />
          </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-950">Sumo Consejo</h1>
        <p class="text-gray-600 text-sm mt-0.5">Estaca La Serena</p>
      </div>

      <!-- Login Form -->
      <form @submit.prevent="handleLogin" class="card p-6 space-y-5">
        <!-- Header con botón de credenciales -->
        <div class="flex items-center justify-center relative">
          <h2 class="text-base font-semibold text-gray-950">Iniciar Sesión</h2>
          <button
            type="button"
            @click="showCredentials = !showCredentials"
            class="absolute right-0 p-2 rounded-md text-gray-400 hover:text-primary-500 hover:bg-primary-50 transition-all interactive"
            :class="{ 'text-primary-500 bg-primary-50': showCredentials }"
            title="Ver credenciales de prueba"
          >
            <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
            </svg>
          </button>
        </div>

        <!-- Panel de credenciales -->
        <transition
          enter-active-class="transition-all duration-250"
          enter-from-class="opacity-0 -translate-y-1 scale-[0.98]"
          enter-to-class="opacity-100 translate-y-0 scale-100"
          leave-active-class="transition-all duration-150"
          leave-from-class="opacity-100 translate-y-0 scale-100"
          leave-to-class="opacity-0 -translate-y-1 scale-[0.98]"
          style="transition-timing-function: var(--ease-out);"
        >
          <div v-if="showCredentials" class="rounded-md border border-gray-200 overflow-hidden">
            <div class="px-3 py-2 bg-info-light border-b border-gray-200">
              <p class="text-xs font-medium text-primary-600 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                Clic para autocompletar
              </p>
            </div>
            <div class="divide-y divide-gray-100">
              <button
                v-for="cred in testCredentials"
                :key="cred.email"
                type="button"
                @click="fillCredentials(cred)"
                class="w-full px-3 py-2.5 flex items-center gap-3 hover:bg-gray-50 transition-all group interactive"
              >
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0" :class="cred.avatarClass">
                  {{ cred.initials }}
                </div>
                <div class="flex-1 text-left min-w-0">
                  <p class="text-sm font-medium text-gray-900 group-hover:text-primary-600 transition-colors truncate">{{ cred.name }}</p>
                  <p class="text-xs text-gray-500 truncate">{{ cred.email }}</p>
                </div>
                <span class="badge shrink-0" :class="cred.badgeClass">{{ cred.roleLabel }}</span>
              </button>
            </div>
          </div>
        </transition>

        <!-- Error Message -->
        <div v-if="error" class="alert alert-danger animate-fade-in">
          <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
          </svg>
          <span>{{ error }}</span>
        </div>

        <!-- Email -->
        <div>
          <label for="login-email" class="input-label">Correo electrónico</label>
          <input
            id="login-email"
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            placeholder="tu@correo.cl"
            class="input-field"
          />
        </div>

        <!-- Password -->
        <div>
          <label for="login-password" class="input-label">Contraseña</label>
          <input
            id="login-password"
            v-model="form.password"
            type="password"
            required
            autocomplete="current-password"
            placeholder="••••••••"
            class="input-field"
          />
        </div>

        <!-- Submit -->
        <button
          type="submit"
          :disabled="loading"
          class="btn btn-primary w-full btn-lg"
        >
          <span v-if="!loading">Ingresar</span>
          <span v-else class="flex items-center justify-center gap-2">
            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            Ingresando...
          </span>
        </button>
      </form>

      <!-- Footer -->
      <p class="text-center text-xs text-gray-500 mt-6">
        La Iglesia de Jesucristo de los Santos de los Últimos Días
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const loading = ref(false)
const error = ref('')
const showCredentials = ref(false)
const form = reactive({ email: '', password: '' })

const testCredentials = [
  {
    initials: 'AD',
    name: 'Administrador',
    email: 'admin@estaca.cl',
    password: 'password',
    roleLabel: 'Admin',
    avatarClass: 'bg-danger',
    badgeClass: 'badge-danger',
  },
  {
    initials: 'PG',
    name: 'Presidente García',
    email: 'presidente@estaca.cl',
    password: 'password',
    roleLabel: 'Presidencia',
    avatarClass: 'bg-primary-500',
    badgeClass: 'badge-primary',
  },
  {
    initials: 'SM',
    name: 'Secretario Muñoz',
    email: 'secretario@estaca.cl',
    password: 'password',
    roleLabel: 'Secretario',
    avatarClass: 'bg-info',
    badgeClass: 'badge-primary',
  },
  {
    initials: 'CR',
    name: 'Carlos Rojas',
    email: 'sc1@estaca.cl',
    password: 'password',
    roleLabel: 'Sumo Consejo',
    avatarClass: 'bg-success',
    badgeClass: 'badge-success',
  },
]

function fillCredentials(cred) {
  form.email = cred.email
  form.password = cred.password
  showCredentials.value = false
}

async function handleLogin() {
  try {
    loading.value = true
    error.value = ''
    await auth.login(form)
    const redirect = route.query.redirect || '/'
    router.push(redirect)
  } catch (err) {
    error.value = err.response?.data?.message || 'Credenciales incorrectas. Intenta nuevamente.'
  } finally {
    loading.value = false
  }
}
</script>
