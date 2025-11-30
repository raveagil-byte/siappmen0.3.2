<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/home"></ion-back-button>
        </ion-buttons>
        <ion-title>Validasi Transaksi</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div class="content-container">
        <ion-card>
          <ion-card-header>
            <ion-card-title>Scan QR Transaksi</ion-card-title>
            <ion-card-subtitle>Scan QR code untuk validasi transaksi</ion-card-subtitle>
          </ion-card-header>
        </ion-card>

        <QRScannerMobile @scan="handleScan" />

        <ion-card v-if="transaction">
          <ion-card-header>
            <ion-card-title>Transaksi #{{ transaction.id }}</ion-card-title>
            <ion-card-subtitle>
              <ion-badge :color="transaction.type === 'steril' ? 'success' : 'warning'">
                {{ transaction.type.toUpperCase() }}
              </ion-badge>
            </ion-card-subtitle>
          </ion-card-header>
          <ion-card-content>
            <ion-list>
              <ion-item>
                <ion-label>
                  <p>Unit</p>
                  <h3>{{ transaction.unit?.name }}</h3>
                </ion-label>
              </ion-item>
              <ion-item>
                <ion-label>
                  <p>Dibuat oleh</p>
                  <h3>{{ transaction.creator?.name }}</h3>
                </ion-label>
              </ion-item>
              <ion-item>
                <ion-label>
                  <p>Total Items</p>
                  <h3>{{ transaction.total_items }}</h3>
                </ion-label>
              </ion-item>
            </ion-list>

            <ion-button
              expand="block"
              color="success"
              @click="validateTransaction"
              :disabled="loading"
              class="validate-button"
            >
              {{ loading ? 'Memvalidasi...' : 'Validasi Transaksi' }}
            </ion-button>
          </ion-card-content>
        </ion-card>
      </div>

      <ion-toast
        :is-open="!!errorMessage"
        :message="errorMessage"
        :duration="3000"
        color="danger"
        @didDismiss="errorMessage = ''"
      ></ion-toast>

      <ion-loading :is-open="loading" message="Memproses..."></ion-loading>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonButtons,
  IonBackButton,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardSubtitle,
  IonCardContent,
  IonList,
  IonItem,
  IonLabel,
  IonButton,
  IonBadge,
  IonToast,
  IonLoading,
  alertController
} from '@ionic/vue'
import QRScannerMobile from '@/components/QRScannerMobile.vue'
import api from '@/services/api'

const router = useRouter()

const transaction = ref<any>(null)
const loading = ref(false)
const errorMessage = ref('')

const handleScan = async (qrContent: string) => {
  try {
    loading.value = true
    const response = await api.post('/transactions/scan-transaction', {
      qr_content: qrContent
    })

    transaction.value = response.data.data.transaction
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Failed to scan QR code'
  } finally {
    loading.value = false
  }
}

const validateTransaction = async () => {
  try {
    loading.value = true
    await api.post('/transactions/validate', {
      qr_content: `TRANS:${transaction.value.uuid}`
    })

    const alert = await alertController.create({
      header: 'Sukses',
      message: 'Transaksi berhasil divalidasi',
      buttons: [
        {
          text: 'OK',
          handler: () => {
            router.push('/home')
          }
        }
      ]
    })
    await alert.present()
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Failed to validate transaction'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.content-container {
  padding: 1rem;
}

.validate-button {
  margin-top: 1rem;
}
</style>