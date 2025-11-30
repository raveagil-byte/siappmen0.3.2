<template>
  <div class="transactions">
    <h1>Transactions</h1>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Type</th>
          <th>Unit</th>
          <th>Status</th>
          <th>Created By</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="transaction in transactions" :key="transaction.id">
          <td>#{{ transaction.id }}</td>
          <td>{{ transaction.type.toUpperCase() }}</td>
          <td>{{ transaction.unit?.name }}</td>
          <td>{{ transaction.status.toUpperCase() }}</td>
          <td>{{ transaction.creator?.name }}</td>
          <td>{{ formatDate(transaction.created_at) }}</td>
          <td>
            <QRDisplay :content="`TRANS:${transaction.uuid}`" :size="50" />
          </td>
          <td>
            <button @click="viewTransaction(transaction.id)">View</button>
            <button @click="cancelTransaction(transaction.id)" :disabled="transaction.status !== 'pending'">Cancel</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="pagination">
      <button @click="prevPage" :disabled="page <= 1">Prev</button>
      <span>Page {{ page }}</span>
      <button @click="nextPage" :disabled="!hasMore">Next</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const transactions = ref([])
const page = ref(1)
const perPage = 10
const hasMore = ref(false)
const router = useRouter()

const fetchTransactions = async () => {
  try {
    const res = await api.get('/transactions', {
      params: {
        page: page.value,
        per_page: perPage,
      },
    })
    transactions.value = res.data.data.data
    hasMore.value = res.data.data.current_page < res.data.data.last_page
  } catch (e) {
    alert('Failed to fetch transactions')
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleString()
}

const viewTransaction = (id) => {
  router.push({ name: 'TransactionDetail', params: { id } })
}

const cancelTransaction = async (id) => {
  if (!confirm('Are you sure you want to cancel this transaction?')) return

  try {
    await api.post(`/transactions/${id}/cancel`, {
      reason: 'Cancelled by user',
    })
    alert('Transaction cancelled')
    fetchTransactions()
  } catch (e) {
    alert('Failed to cancel transaction')
  }
}

const nextPage = () => {
  if (hasMore.value) {
    page.value++
    fetchTransactions()
  }
}

const prevPage = () => {
  if (page.value > 1) {
    page.value--
    fetchTransactions()
  }
}

onMounted(() => {
  fetchTransactions()
})
</script>

<style scoped>
.transactions {
  max-width: 900px;
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

button {
  margin-right: 0.5rem;
}

.pagination {
  margin-top: 1rem;
  display: flex;
  justify-content: center;
  gap: 1rem;
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
