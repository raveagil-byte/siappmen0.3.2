<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/home"></ion-back-button>
        </ion-buttons>
        <ion-title>Pengambilan Kotor</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div class="content-container">
        <ion-card v-if="unit">
          <ion-card-header>
            <ion-card-title>{{ unit.name }}</ion-card-title>
            <ion-card-subtitle>{{ unit.location }}</ion-card-subtitle>
          </ion-card-header>
        </ion-card>

        <ion-card>
          <ion-card-header>
            <ion-card-title>Instrumen Kotor di Unit</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <ion-list>
              <ion-item v-for="instrument in dirtyInstruments" :key="instrument.id">
                <ion-label>
                  <h3>{{ instrument.name }}</h3>
                  <p>{{ instrument.code }} - Kotor: {{ instrument.dirty_stock }}</p>
                </ion-label>
                <ion-input
                  v-model.number="selectedItems[instrument.id]"
                  type="number"
                  :min="0"
                  :max="instrument.dirty_stock"
                  placeholder="0"
                ></ion-input>
              </ion-item>
            </ion-list>

            <ion-item>
              <ion-label position="stacked">Catatan (Opsional)</ion-label>
              <ion-textarea v-model="notes" placeholder="Tambahkan catatan..."></ion-textarea>
            </ion-item>

            <ion-button
              expand="block"
              color="warning"
              @click="createTransaction"
              :disabled="!hasSelectedItems || loading"
              class="submit-button"
            >
              {{ loading ? 'Membuat Transaksi...' : 'Buat Transaksi Kotor' }}
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
import { ref, computed, onMounted } from 'vue'
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
  IonList,
  IonItem,
  IonLabel,
  IonInput,
  IonTextarea,
  IonButton,
  IonToast,
  IonLoading,
  alertController
} from '@ionic/vue'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const unit = ref<any>(null)
const dirtyInstruments = ref<any[]>([])
const selectedItems = ref<Record<number, number>>({})
const notes = ref('')
const loading = ref(false)
const errorMessage = ref('')

const hasSelectedItems = computed(() => {
  return Object.values(selectedItems.value).some(qty => qty > 0)
})

onMounted(async () => {
  const unitId = route.query.unit_id
  if (!unitId) {
    router.push('/home')
    return
  }

  await fetchUnitAndInstruments(unitId as string)
})

const fetchUnitAndInstruments = async (unitId: string) => {
  try {
    loading.value = true
    const response = await api.get(`/units/${unitId}`)
    unit.value = response.data.data

    const instrumentsResponse = await api.get(`/units/${unitId}/dirty-instruments`)
    dirtyInstruments.value = instrumentsResponse.data.data
  } catch (error: any) {
    errorMessage.value = 'Failed to load data'
  } finally {
    loading.value = false
  }
}

const createTransaction = async () => {
  const items = Object.entries(selectedItems.value)
    .filter(([_, qty]) => qty > 0)
    .map(([instrumentId, quantity]) => ({
      instrument_id: parseInt(instrumentId),
      quantity
    }))

  if (items.length === 0) {
    errorMessage.value = 'Pilih minimal 1 instrumen'
    return
  }

  try {
    loading.value = true
    const response = await api.post('/transactions/create-kotor', {
      unit_id: unit.value.id,
      items,
      notes: notes.value || null
    })

    const alert = await alertController.create({
      header: 'Sukses',
      message: 'Transaksi kotor berhasil dibuat. QR Code telah digenerate.',
      buttons: [
        {
          text: 'Lihat QR',
          handler: () => {
            router.push(`/transaction-qr/${response.data.data.transaction.id}`)
          }
        }
      ]
    })
    await alert.present()
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Failed to create transaction'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.content-container {
  padding: 1rem;
}

.submit-button {
  margin-top: 1rem;
}
</style>