<template>
  <div class="min-h-screen flex bg-gray-50">
    <!-- Sidebar -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-30 flex flex-col w-64 bg-white border-r border-gray-200 transition-transform duration-300 lg:translate-x-0 lg:relative',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full'
      ]"
      style="transition-timing-function: var(--ease-drawer);"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-5 py-5 border-b border-gray-200">
        <div class="w-10 h-10 rounded-lg bg-primary-500 flex items-center justify-center shadow-sm">
          <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21" />
          </svg>
        </div>
        <div>
          <h1 class="text-sm font-semibold text-gray-950 leading-tight">Sumo Consejo</h1>
          <p class="text-xs text-gray-600">Estaca La Serena</p>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <router-link
          v-for="item in filteredNavItems"
          :key="item.name"
          :to="item.to"
          class="nav-link group flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-all"
          active-class="nav-link--active"
          @click="sidebarOpen = false"
          style="transition-duration: 160ms; transition-timing-function: var(--ease-out);"
        >
          <span class="nav-icon w-5 h-5 text-gray-500 group-hover:text-primary-500 transition-colors" v-html="item.icon"></span>
          <span class="text-gray-700 group-hover:text-gray-950 transition-colors">{{ item.label }}</span>
        </router-link>
      </nav>

      <!-- User Info -->
      <div class="px-4 py-4 border-t border-gray-200">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-full bg-primary-500 flex items-center justify-center text-sm font-bold text-white shadow-sm shrink-0">
            {{ userInitials }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-950 truncate">{{ auth.user?.name }}</p>
            <p class="text-xs text-primary-600 truncate">{{ auth.roleName }}</p>
          </div>
          <button
            @click="handleLogout"
            class="p-1.5 rounded-md text-gray-400 hover:text-danger hover:bg-danger-light transition-all interactive"
            title="Cerrar sesión"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
            </svg>
          </button>
        </div>
      </div>
    </aside>

    <!-- Overlay mobile -->
    <transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 bg-black/30 z-20 lg:hidden"
        @click="sidebarOpen = false"
      />
    </transition>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-h-screen min-w-0">
      <!-- Top Bar -->
      <header class="sticky top-0 z-10 bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-4 lg:px-6 shadow-raised">
        <button
          @click="sidebarOpen = !sidebarOpen"
          class="p-2 rounded-md text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-all lg:hidden interactive"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
          </svg>
        </button>
        <div class="flex-1">
          <h2 class="text-lg font-semibold text-gray-950">{{ currentPageTitle }}</h2>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 p-4 lg:p-6">
        <router-view v-slot="{ Component }">
          <transition name="page" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const sidebarOpen = ref(false)

const userInitials = computed(() => {
  const name = auth.user?.name || ''
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

const navItems = [
  {
    name: 'dashboard',
    label: 'Panel',
    to: '/',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>',
    roles: ['admin', 'presidencia', 'secretario', 'sumo_consejo'],
  },
  {
    name: 'assignments',
    label: 'Asignaciones',
    to: '/assignments',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>',
    roles: ['presidencia', 'secretario', 'sumo_consejo'],
  },
  {
    name: 'reports',
    label: 'Informes',
    to: '/reports',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>',
    roles: ['presidencia', 'secretario', 'sumo_consejo'],
  },
  {
    name: 'callings',
    label: 'Llamamientos',
    to: '/callings',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>',
    roles: ['presidencia', 'secretario', 'sumo_consejo'],
  },
  {
    name: 'meetings',
    label: 'Reuniones',
    to: '/meetings',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>',
    roles: ['presidencia', 'secretario', 'sumo_consejo'],
  },
  {
    name: 'admin-users',
    label: 'Usuarios',
    to: '/admin/users',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
    roles: ['admin', 'presidencia'],
  },
  {
    name: 'admin-integrations',
    label: 'Integraciones',
    to: '/admin/integrations',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>',
    roles: ['admin'],
  },
]

const filteredNavItems = computed(() => {
  const role = auth.user?.role
  return navItems.filter(item => item.roles.includes(role))
})

const currentPageTitle = computed(() => {
  const titles = {
    dashboard: 'Panel Principal',
    assignments: 'Asignaciones',
    'assignment-detail': 'Detalle de Asignación',
    reports: 'Informes de Mayordomía',
    callings: 'Llamamientos',
    'calling-detail': 'Detalle de Llamamiento',
    meetings: 'Reuniones',
    'meeting-create': 'Nueva Reunión',
    'meeting-detail': 'Detalle de Reunión',
    'admin-users': 'Gestión de Usuarios',
    'admin-integrations': 'Integraciones API',
  }
  return titles[route.name] || 'Sumo Consejo'
})

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<style scoped>
.nav-link {
  position: relative;
}

.nav-link:hover {
  background: var(--color-gray-100);
}

.nav-link:active {
  transform: scale(0.98);
}

.nav-link--active {
  background: var(--color-primary-50) !important;
}

.nav-link--active .nav-icon,
.nav-link--active span:last-child {
  color: var(--color-primary-600) !important;
}

.nav-link--active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 60%;
  background: var(--color-primary-500);
  border-radius: 0 3px 3px 0;
}

/* Page transition */
.page-enter-active,
.page-leave-active {
  transition: opacity 0.2s var(--ease-out), transform 0.2s var(--ease-out);
}

.page-enter-from {
  opacity: 0;
  transform: translateY(6px);
}

.page-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
