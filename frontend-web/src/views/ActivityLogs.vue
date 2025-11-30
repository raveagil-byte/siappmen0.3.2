<template>
  <div class="activity-logs">
    <h1>Activity Logs</h1>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Action</th>
          <th>Device</th>
          <th>Transaction</th>
          <th>Metadata</th>
          <th>Timestamp</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="log in logs" :key="log.id">
          <td>{{ log.id }}</td>
          <td>{{ log.user?.name || 'System' }}</td>
          <td>{{ log.action }}</td>
          <td>{{ log.device_name }}</td>
          <td>{{ log.transaction_id || '-' }}</td>
          <td><pre>{{ JSON.stringify(log.metadata, null, 2) }}</pre></td>
          <td>{{ formatDate(log.created_at) }}</td>
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
import api from '@/services/api'

const logs = ref([])
const page = ref(1)
const perPage = 10
const hasMore = ref(false)

const fetchLogs = async () => {
  try {
    const response = await api.get('/activity-logs', {
      params: { page: page.value, per_page: perPage }
    })
    logs.value = response.data.data.data
    hasMore.value = response.data.data.current_page < response.data.data.last_page
  } catch (error) {
    console.error('Error fetching activity logs:', error)
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleString()
}

const nextPage = () => {
  if (hasMore.value) {
    page.value++
    fetchLogs()
  }
}

const prevPage = () => {
  if (page.value > 1) {
    page.value--
    fetchLogs()
  }
}

onMounted(() => {
  fetchLogs()
})
</script>

<style scoped>
.activity-logs {
  max-width: 900px;
  margin: 0 auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

table th, table td {
  border: 1px solid #ddd;
  padding: 8px;
}

table th {
  background-color: #f5f5f5;
  text-align: left;
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
