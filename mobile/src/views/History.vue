<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/home"></ion-back-button>
        </ion-buttons>
        <ion-title>Riwayat Transaksi</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <ion-refresher slot="fixed" @ionRefresh="handleRefresh">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <div class="content-container">
        <ion-segment v-model="filterType" @ionChange="fetchTransactions">
          <ion-segment-button value="all">
            <ion-label>Semua</ion-label>
          </ion-segment-button>
          <ion-segment-button value="steril">
            <ion-label>Steril</ion-label>
          </ion-segment-button>
          <ion-segment-button value="kotor">
            <ion-label>Kotor</ion-label>
          </ion-segment-button>
        </ion-segment>

        <ion-list v-if="transactions.length > 0">
          <ion-item v-for="transaction in transactions" :key="transaction.id" button>
            <ion-label>
              <h2>
                Transaksi #{{ transaction.id }}
                <ion-badge :color="transaction.type === 'steril' ? 'success' : 'warning'">
                  {{ transaction.type.toUpperCase() }}
                </ion-badge>
              </h2>
              <h3>{{ transaction.unit?.name }}</h3>
              <p>{{ formatDate(transaction.created_at) }}</p>
            </ion-label>
            <ion-badge slot="end" :color="getStatusColor(transaction.status)">
              {{ transaction.status.toUpperCase() }}
            </ion-badge>
          </ion-item>
        </ion-list>

        <div v-else class="empty-state">
          <ion-icon :icon="documentTextOutline" size="large"></ion-icon>
          <p>Belum ada transaksi</p>
        </div>
      </div>

      <ion-infinite-scroll
        v-if="hasMore"
        @ionInfinite="loadMore"
        threshold="100px"
      >
        <ion-infinite-scroll-content></ion-infinite-scroll-content>
      </ion-infinite-scroll>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonButtons,
  IonBackButton,
  IonRefresher,
  IonRefresherContent,
  IonSegment,
  IonSegmentButton,
  IonLabel,
  IonList,
  IonItem,
  IonBadge,
  IonIcon,
  IonInfiniteScroll,
  IonInfiniteScrollContent
} from '@ionic/vue'
import { documentTextOutline } from 'ionicons/icons'
import api from '@/services/api'

const filterType = ref('all')
const transactions = ref<any[]>([])
const page = ref(1)
const hasMore = ref(true)

onMounted(() => {
  fetchTransactions()
})

const fetchTransactions = async () => {
  try {
    page.value = 1
    const params: any = { page: page.value }
    if (filterType.value !== 'all') {
      params.type = filterType.value
    }

    const response = await api.get('/transactions', { params })
    transactions.value = response.data.data.data
    hasMore.value = response.data.data.current_page < response.data.data.last_page
  } catch (error) {
    console.error('Failed to fetch transactions:', error)
  }
}

const loadMore = async (event: any) => {
  page.value++
  try {
    const params: any = { page: page.value }
    if (filterType.value !== 'all') {
      params.type = filterType.value
    }

    const response = await api.get('/transactions', { params })
    transactions.value.push(...response.data.data.data)
    hasMore.value = response.data.data.current_page < response.data.data.last_page
  } catch (error) {
    console.error('Failed to load more:', error)
  } finally {
    event.target.complete()
  }
}

const handleRefresh = async (event: any) => {
  await fetchTransactions()
  event.target.complete()
}

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    pending: 'warning',
    validated: 'success',
    cancelled: 'danger'
  }
  return colors[status] || 'medium'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleString('id-ID')
}
</script>

<style scoped>
.content-container {
  padding: 1rem;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1rem;
  text-align: center;
  color: var(--ion-color-medium);
}

.empty-state ion-icon {
  margin-bottom: 1rem;
}
</style>