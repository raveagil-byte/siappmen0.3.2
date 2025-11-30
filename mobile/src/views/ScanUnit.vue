<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/home"></ion-back-button>
        </ion-buttons>
        <ion-title>Scan Unit QR</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div class="scan-container">
        <div class="scan-info">
          <ion-card>
            <ion-card-header>
              <ion-card-title>{{ transactionType === 'steril' ? 'Distribusi Steril' : 'Pengambilan Kotor' }}</ion-card-title>
              <ion-card-subtitle>Scan QR Unit untuk memulai transaksi</ion-card-subtitle>
            </ion-card-header>
          </ion-card>
        </div>

        <QRScannerMobile @scan="handleScan" />

        <div v-if="scannedUnit" class="unit-info">
          <ion-card>
            <ion-card-header>
              <ion-card-title>{{ scannedUnit.name }}</ion-card-title>
              <ion-card-subtitle>{{ scannedUnit.location }}</ion-card-subtitle>
            </ion-card-header>
            <ion-card-content>
              <ion-button expand="block" @click="proceedToTransaction">
                Lanjutkan
                <ion-icon :icon="arrowForwardOutline" slot="end"></ion-icon>
              </ion-button>
            </ion-card-content>
          </ion-card>
        </div>
      </div>

      <ion-toast
        :is-open="!!errorMessage"
        :message="errorMessage"
        :duration="3000"
        color="danger"
        @didDismiss="errorMessage = ''"
      ></ion-toast>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
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
  IonButton,
  IonIcon,
  IonToast
} from '@ionic/vue'
import { arrowForwardOutline } from 'ionicons/icons'
import QRScannerMobile from '@/components/QRScannerMobile.vue'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const transactionType = ref<string>('')
const scannedUnit = ref<any>(null)
const errorMessage = ref('')

onMounted(() => {
  transactionType.value = (route.query.type as string) || 'steril'
})

const handleScan = async (qrContent: string) => {
  try {
    const response = await api.post('/transactions/scan-unit', {
      qr_content: qrContent,
      transaction_type: transactionType.value
    })

    scannedUnit.value = response.data.data.unit
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Failed to scan QR code'
  }
}

const proceedToTransaction = () => {
  if (transactionType.value === 'steril') {
    router.push({
      path: '/distribusi-steril',
      query: { unit_id: scannedUnit.value.id }
    })
  } else {
    router.push({
      path: '/kotor-pickup',
      query: { unit_id: scannedUnit.value.id }
    })
  }
}
</script>

<style scoped>
.scan-container {
  padding: 1rem;
}

.scan-info {
  margin-bottom: 1rem;
}

.unit-info {
  margin-top: 1rem;
}
</style>