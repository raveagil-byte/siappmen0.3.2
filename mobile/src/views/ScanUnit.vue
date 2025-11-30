<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/home"></ion-back-button>
        </ion-buttons>
        <ion-title>Scan Unit QR Code</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="scan-container">
        <ion-card class="info-card">
          <ion-card-header>
            <ion-card-title>{{ pageTitle }}</ion-card-title>
            <ion-card-subtitle>Scan the unit's QR code to begin.</ion-card-subtitle>
          </ion-card-header>
        </ion-card>

        <!-- The refactored QR Scanner Component -->
        <QRScannerMobile
          ref="qrScanner"
          @scanSuccess="handleScanSuccess"
          @scanFail="handleScanFail"
        />

        <div v-if="scannedUnit" class="unit-info-card">
          <ion-card>
            <ion-card-header>
              <ion-card-title>Unit Found</ion-card-title>
              <ion-card-subtitle>{{ scannedUnit.name }} - {{ scannedUnit.location }}</ion-card-subtitle>
            </ion-card-header>
            <ion-card-content>
              <ion-button expand="block" @click="proceedToTransaction">
                Continue to Transaction
                <ion-icon :icon="arrowForwardOutline" slot="end"></ion-icon>
              </ion-button>
            </ion-card-content>
          </ion-card>
        </div>
      </div>

      <ion-loading :is-open="isLoading" message="Verifying QR code..."></ion-loading>

      <ion-toast
        :is-open="!!errorMessage"
        :message="errorMessage"
        :duration="3500"
        color="danger"
        @didDismiss="errorMessage = ''"
      ></ion-toast>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons,
  IonBackButton, IonCard, IonCardHeader, IonCardTitle, IonCardSubtitle,
  IonCardContent, IonButton, IonIcon, IonToast, IonLoading
} from '@ionic/vue';
import { arrowForwardOutline } from 'ionicons/icons';
import QRScannerMobile from '@/components/QRScannerMobile.vue';
import api from '@/services/api';

const route = useRoute();
const router = useRouter();

const transactionType = ref<string>('distribusi_steril');
const scannedUnit = ref<any>(null);
const errorMessage = ref('');
const isLoading = ref(false);

const pageTitle = computed(() => {
  const titles: { [key: string]: string } = {
    distribusi_steril: 'Sterile Distribution',
    pengambilan_kotor: 'Dirty Instrument Pickup',
    pengembalian_cssd: 'Return to CSSD'
  };
  return titles[transactionType.value] || 'Scan Unit';
});

onMounted(() => {
  const typeFromQuery = route.query.type as string;
  if (typeFromQuery) {
    transactionType.value = typeFromQuery;
  }
});

const handleScanSuccess = async (qrContent: string) => {
  isLoading.value = true;
  errorMessage.value = '';
  scannedUnit.value = null;

  try {
    const response = await api.post('/transactions/scan-unit', { qr_content: qrContent });
    scannedUnit.value = response.data.data.unit;
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Failed to verify unit QR code.';
  } finally {
    isLoading.value = false;
  }
};

const handleScanFail = (message: string) => {
  errorMessage.value = message;
};

const proceedToTransaction = () => {
  if (!scannedUnit.value) return;

  const routes: { [key: string]: string } = {
    distribusi_steril: '/distribusi-steril',
    pengambilan_kotor: '/kotor-pickup',
    pengembalian_cssd: '/cssd-return'
  };

  const path = routes[transactionType.value];

  if (path) {
    router.push({
      path: path,
      query: { unit_id: scannedUnit.value.id }
    });
  } else {
    errorMessage.value = "Invalid transaction type specified.";
  }
};
</script>

<style scoped>
.scan-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
  padding: 1rem;
}
.info-card, .unit-info-card {
  width: 100%;
  max-width: 500px;
}
</style>
