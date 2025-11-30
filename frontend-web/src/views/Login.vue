<template>
  <div class="login-container">
    <div class="login-card">
      <h1 class="login-title">CSSD Medical Distribution</h1>
      <p class="login-subtitle">Sign in to your account</p>
      
      <form @submit.prevent="handleLogin" class="login-form">
        <div v-if="error" class="alert alert-danger">
          {{ error }}
        </div>

        <div class="form-group">
          <label class="form-label">Email</label>
          <input 
            v-model="email" 
            type="email" 
            class="form-input" 
            required 
            placeholder="admin@cssd.com"
          />
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <input 
            v-model="password" 
            type="password" 
            class="form-input" 
            required 
            placeholder="••••••••"
          />
        </div>

        <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
          {{ loading ? 'Signing in...' : 'Sign In' }}
        </button>
      </form>

      <div class="login-footer">
        <p>Default credentials:</p>
        <ul>
          <li>Admin: admin@cssd.com / password</li>
          <li>Petugas: petugas@cssd.com / password</li>
          <li>Perawat: perawat@unit.com / password</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('admin@cssd.com')
const password = ref('password')
const loading = ref(false)
const error = ref('')

const handleLogin = async () => {
  loading.value = true
  error.value = ''

  try {
    await authStore.login(email.value, password.value)
    router.push('/')
  } catch (err) {
    error.value = err.response?.data?.message || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1rem;
}

.login-card {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  padding: 2rem;
  width: 100%;
  max-width: 400px;
}

.login-title {
  font-size: 1.5rem;
  font-weight: 700;
  text-align: center;
  margin-bottom: 0.5rem;
  color: var(--gray-900);
}

.login-subtitle {
  text-align: center;
  color: var(--gray-600);
  margin-bottom: 2rem;
}

.login-form {
  margin-bottom: 1.5rem;
}

.btn-block {
  width: 100%;
  margin-top: 1rem;
}

.login-footer {
  text-align: center;
  font-size: 0.875rem;
  color: var(--gray-600);
  border-top: 1px solid var(--gray-200);
  padding-top: 1rem;
}

.login-footer ul {
  list-style: none;
  margin-top: 0.5rem;
}

.login-footer li {
  margin: 0.25rem 0;
  font-family: monospace;
}
</style>