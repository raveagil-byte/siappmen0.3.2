<template>
  <ion-page>
    <ion-content :fullscreen="true">
      <div class="login-container">
        <div class="login-card">
          <h1 class="login-title">CSSD Distribution</h1>
          <p class="login-subtitle">Mobile App</p>

          <ion-list>
            <ion-item v-if="error">
              <ion-label color="danger">{{ error }}</ion-label>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Email</ion-label>
              <ion-input
                v-model="email"
                type="email"
                placeholder="admin@cssd.com"
                required
              ></ion-input>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Password</ion-label>
              <ion-input
                v-model="password"
                type="password"
                placeholder="••••••••"
                required
              ></ion-input>
            </ion-item>
          </ion-list>

          <ion-button
            expand="block"
            @click="handleLogin"
            :disabled="loading"
            class="login-button"
          >
            {{ loading ? 'Signing in...' : 'Sign In' }}
          </ion-button>

          <div class="login-footer">
            <p>Default credentials:</p>
            <ion-text color="medium">
              <small>Admin: admin@cssd.com / password</small><br>
              <small>Petugas: petugas@cssd.com / password</small><br>
              <small>Perawat: perawat@unit.com / password</small>
            </ion-text>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  IonPage,
  IonContent,
  IonList,
  IonItem,
  IonLabel,
  IonInput,
  IonButton,
  IonText
} from '@ionic/vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('petugas@cssd.com')
const password = ref('password')
const loading = ref(false)
const error = ref('')

const handleLogin = async () => {
  loading.value = true
  error.value = ''

  try {
    await authStore.login(email.value, password.value)
    router.push('/home')
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.login-title {
  text-align: center;
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: var(--ion-color-primary);
}

.login-subtitle {
  text-align: center;
  color: var(--ion-color-medium);
  margin-bottom: 2rem;
}

.login-button {
  margin-top: 1.5rem;
}

.login-footer {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--ion-color-light);
  text-align: center;
}

.login-footer p {
  margin-bottom: 0.5rem;
  font-weight: 600;
}
</style>