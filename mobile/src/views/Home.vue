<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>CSSD Distribution</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="handleLogout">
            <ion-icon :icon="logOutOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div class="home-container">
        <div class="user-info">
          <h2>Welcome, {{ authStore.user?.name }}</h2>
          <p class="user-role">{{ authStore.user?.role }}</p>
        </div>

        <div class="menu-grid">
          <!-- Distribusi Steril -->
          <ion-card button @click="router.push('/scan-unit?type=steril')">
            <ion-card-content>
              <div class="menu-icon">📦</div>
              <h3>Distribusi Steril</h3>
              <p>CSSD → Unit</p>
            </ion-card-content>
          </ion-card>

          <!-- Pengambilan Kotor -->
          <ion-card button @click="router.push('/scan-unit?type=kotor')">
            <ion-card-content>
              <div class="menu-icon">🔄</div>
              <h3>Pengambilan Kotor</h3>
              <p>Unit → CSSD</p>
            </ion-card-content>
          </ion-card>

          <!-- Validasi Transaksi -->
          <ion-card button @click="router.push('/validasi-steril')">
            <ion-card-content>
              <div class="menu-icon">✅</div>
              <h3>Validasi Transaksi</h3>
              <p>Scan QR Transaksi</p>
            </ion-card-content>
          </ion-card>

          <!-- History -->
          <ion-card button @click="router.push('/history')">
            <ion-card-content>
              <div class="menu-icon">📋</div>
              <h3>History</h3>
              <p>Riwayat Transaksi</p>
            </ion-card-content>
          </ion-card>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonCard,
  IonCardContent,
  IonButtons,
  IonButton,
  IonIcon
} from '@ionic/vue'
import { logOutOutline } from 'ionicons/icons'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.home-container {
  padding: 1rem;
}

.user-info {
  text-align: center;
  margin-bottom: 2rem;
  padding: 1rem;
}

.user-info h2 {
  margin: 0;
  font-size: 1.5rem;
}

.user-role {
  color: var(--ion-color-medium);
  text-transform: uppercase;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.menu-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

ion-card {
  margin: 0;
  text-align: center;
}

.menu-icon {
  font-size: 3rem;
  margin-bottom: 0.5rem;
}

ion-card h3 {
  margin: 0.5rem 0;
  font-size: 1rem;
}

ion-card p {
  margin: 0;
  font-size: 0.875rem;
  color: var(--ion-color-medium);
}
</style>