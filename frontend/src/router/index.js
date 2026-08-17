import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  // --- Auth ---
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: '/invitation/:token',
    name: 'accept-invitation',
    component: () => import('@/views/auth/AcceptInvitationView.vue'),
    meta: { guest: true },
  },

  // --- App (protegida) ---
  {
    path: '/',
    component: () => import('@/components/layout/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('@/views/dashboard/DashboardView.vue'),
      },

      // Mayordomías y Asignaciones
      {
        path: 'assignments',
        name: 'assignments',
        component: () => import('@/views/assignments/AssignmentListView.vue'),
      },
      {
        path: 'assignments/:id',
        name: 'assignment-detail',
        component: () => import('@/views/assignments/AssignmentDetailView.vue'),
      },
      {
        path: 'reports',
        name: 'reports',
        component: () => import('@/views/reports/ReportListView.vue'),
      },

      // Llamamientos
      {
        path: 'callings',
        name: 'callings',
        component: () => import('@/views/callings/CallingListView.vue'),
      },
      {
        path: 'callings/:id',
        name: 'calling-detail',
        component: () => import('@/views/callings/CallingDetailView.vue'),
      },

      // Reuniones
      {
        path: 'meetings',
        name: 'meetings',
        component: () => import('@/views/meetings/MeetingListView.vue'),
      },
      {
        path: 'meetings/create',
        name: 'meeting-create',
        component: () => import('@/views/meetings/MeetingCreateView.vue'),
      },
      {
        path: 'meetings/:id',
        name: 'meeting-detail',
        component: () => import('@/views/meetings/MeetingDetailView.vue'),
      },

      // Administración (solo Admin/Presidencia)
      {
        path: 'admin/users',
        name: 'admin-users',
        component: () => import('@/views/admin/UserManagementView.vue'),
        meta: { roles: ['admin', 'presidencia'] },
      },
      {
        path: 'admin/integrations',
        name: 'admin-integrations',
        component: () => import('@/views/admin/IntegrationsView.vue'),
        meta: { roles: ['admin'] },
      },
    ],
  },

  // 404
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(_to, _from, savedPosition) {
    return savedPosition || { top: 0 }
  },
})

// Navigation guards
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // Intentar cargar usuario si hay sesión activa (solo en rutas que requieren auth)
  if (!auth.user && !auth.checked && !to.meta.guest) {
    await auth.fetchUser()
  }

  // Rutas que requieren autenticación
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  // Rutas solo para invitados (login, registro)
  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  // Verificación de rol (soporta string o array)
  if (to.meta.roles) {
    const userRole = auth.user?.role
    const allowed = Array.isArray(to.meta.roles) ? to.meta.roles : [to.meta.roles]
    if (!allowed.includes(userRole)) {
      return { name: 'dashboard' }
    }
  }

  if (to.meta.role && auth.user?.role !== to.meta.role) {
    return { name: 'dashboard' }
  }
})

export default router
