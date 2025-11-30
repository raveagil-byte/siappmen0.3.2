<template>
  <div class="main-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2>CSSD System</h2>
      </div>
      <nav class="sidebar-nav">
        <router-link to="/" class="nav-item">
          <span>📊</span> Dashboard
        </router-link>
        <router-link to="/units" class="nav-item">
          <span>🏥</span> Units
        </router-link>
        <router-link to="/instruments" class="nav-item">
          <span>🔧</span> Instruments
        </router-link>
        <router-link to="/transactions" class="nav-item">
          <span>📦</span> Transactions
        </router-link>
        <router-link to="/reports" class="nav-item">
          <span>📈</span> Reports
        </router-link>
        <router-link to="/activity-logs" class="nav-item">
          <span>📝</span> Activity Logs
        </router-link>
      </nav>
    </aside>

    <div class="main-content">
      <header class="header">
        <div class="header-content">
          <h1 class="page-title">{{ pageTitle }}</h1>
          <div class="user-menu">
            <span class="user-name">{{ authStore.user?.name }}</span>
            <span class="user-role">{{ authStore.user?.role }}</span>
            <button @click="handleLogout" class="btn btn-sm btn-secondary">Logout</button>
          </div>
        </div>
      </header>

      <main class="content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const pageTitle = computed(() => {
  return route.name || 'Dashboard'
})

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.main-layout {
  display: flex;
  min-height: 100vh;
}

.sidebar {
  width: 250px;
  background: var(--gray-900);
  color: white;
  position: fixed;
  height: 100vh;
  overflow-y: auto;
}

.sidebar-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--gray-700);
}

.sidebar-header h2 {
  font-size: 1.25rem;
  font-weight: 600;
}

.sidebar-nav {
  padding: 1rem 0;
}

.nav-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1.5rem;
  color: var(--gray-300);
  text-decoration: none;
  transition: all 0.2s;
}

.nav-item:hover {
  background: var(--gray-800);
  color: white;
}

.nav-item.router-link-active {
  background: var(--primary-color);
  color: white;
}

.nav-item span {
  margin-right: 0.75rem;
  font-size: 1.25rem;
}

.main-content {
  flex: 1;
  margin-left: 250px;
}

.header {
  background: white;
  border-bottom: 1px solid var(--gray-200);
  position: sticky;
  top: 0;
  z-index: 10;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--gray-900);
}

.user-menu {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-name {
  font-weight: 500;
  color: var(--gray-900);
}

.user-role {
  font-size: 0.875rem;
  color: var(--gray-600);
  text-transform: uppercase;
}

.content {
  padding: 2rem;
}
</style>