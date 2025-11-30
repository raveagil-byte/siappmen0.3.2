<template>
  <div class="transaction-detail">
    <h1>Transaction Detail #{{ transaction.id }}</h1>

    <div>
      <p><strong>Type:</strong> {{ transaction.type.toUpperCase() }}</p>
      <p><strong>Status:</strong> {{ transaction.status.toUpperCase() }}</p>
      <p><strong>Unit:</strong> {{ transaction.unit?.name }}</p>
      <p><strong>Created By:</strong> {{ transaction.creator?.name }}</p>
      <p><strong>Validated By:</strong> {{ transaction.validator?.name || '-' }}</p>
      <p><strong>Date:</strong> {{ formatDate(transaction.created_at) }}</p>
      <p><strong>Notes:</strong> {{ transaction.notes || '-' }}</p>
    </div>

    <div class="qr-code">
      <h2>Transaction QR Code</h2>
      <img :src="transaction.qr_base64" alt="Transaction QR" />
      <p><a :href="transaction.qr_content" target="_blank">QR Content: {{ transaction.qr_content }}</a></p>
    </div>

    <div>
      <h2>Instruments</h2>
      <table>
        <thead>
          <tr><th>Name</th><th>Quantity</th></tr>
        </thead>
        <tbody>
          <tr v-for="item in transaction.items" :key="item.id">
            <td>{{ item.instrument.name }}</td>
            <td>{{ item.quantity }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <button @click="goBack">Back to Transactions</button>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const transaction = ref(null)

const fetchTransaction = async () => {
  try {
    const res = await api.get(`/transactions/${route.params.id}`)
    transaction.value = res.data.data.transaction
    // attach QR content and image
    transaction.value.qr_content = res.data.data.qr_content
    transaction.value.qr_base64 = res.data.data.qr_base64
  } catch (e) {
    alert('Failed to fetch transaction detail')
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleString()
}

const goBack = () => {
  router.back()
}

onMounted(() => {
  fetchTransaction()
})
</script>

<style scoped>
.transaction-detail {
  max-width: 800px;
  margin: 0 auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}

table, th, td {
  border: 1px solid #ccc;
}

th, td {
  padding: 0.5rem;
  text-align: left;
}

.qr-code img {
  max-width: 200px;
  margin-top: 1rem;
  display: block;
}
</style>
