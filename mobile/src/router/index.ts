import { createRouter, createWebHistory } from '@ionic/vue-router'
import { RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/home'
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/Login.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/home',
    name: 'Home',
    component: () => import('../views/Home.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/scan-unit',
    name: 'ScanUnit',
    component: () => import('../views/ScanUnit.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/distribusi-steril',
    name: 'DistribusiSteril',
    component: () => import('../views/DistribusiSteril.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/validasi-steril',
    name: 'ValidasiSteril',
    component: () => import('../views/ValidasiSteril.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/kotor-pickup',
    name: 'KotorPickup',
    component: () => import('../views/KotorPickup.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/validasi-kotor',
    name: 'ValidasiKotor',
    component: () => import('../views/ValidasiKotor.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/history',
    name: 'History',
    component: () => import('../views/History.vue'),
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL || ''),
  routes
})

router.beforeEach((to, _from, next) => {

  const authStore = useAuthStore()
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
    next('/home')
  } else {
    next()
  }
})

export default router