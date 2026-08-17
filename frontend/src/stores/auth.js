import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const checked = ref(false)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!user.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isPresidencia = computed(() => user.value?.role === 'presidencia')
  const isSecretario = computed(() => user.value?.role === 'secretario')
  const isSumoConsejo = computed(() => user.value?.role === 'sumo_consejo')

  const roleName = computed(() => {
    const roles = {
      admin: 'Administrador General',
      presidencia: 'Presidencia de Estaca',
      secretario: 'Secretario de Estaca',
      sumo_consejo: 'Sumo Consejo',
    }
    return roles[user.value?.role] || ''
  })

  async function fetchUser() {
    try {
      loading.value = true
      const { data } = await api.get('/user')
      user.value = data.data
    } catch {
      user.value = null
    } finally {
      checked.value = true
      loading.value = false
    }
  }

  async function login(credentials) {
    // Obtener CSRF cookie primero (Sanctum SPA)
    await api.get('/sanctum/csrf-cookie', { baseURL: '' })
    await api.post('/login', credentials)
    await fetchUser()
  }

  async function logout() {
    await api.post('/logout')
    user.value = null
  }

  function $reset() {
    user.value = null
    checked.value = false
    loading.value = false
  }

  return {
    user,
    checked,
    loading,
    isAuthenticated,
    isAdmin,
    isPresidencia,
    isSecretario,
    isSumoConsejo,
    roleName,
    fetchUser,
    login,
    logout,
    $reset,
  }
})
