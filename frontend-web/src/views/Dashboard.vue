<template>
  <div class="dashboard">
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">🏥</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.summary?.total_units || 0 }}</div>
          <div class="stat-label">Total Units</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">🔧</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.summary?.total_instruments || 0 }}</div>
          <div class="stat-label">Total Instruments</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.summary?.total_transactions || 0 }}</div>
          <div class="stat-label">Total Transactions</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">⏳</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.summary?.pending_transactions || 0 }}</div>
          <div class="stat-label">Pending Transactions</div>
        </div>
      </div>
    </div>

    <div class="charts-grid">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">CSSD Stock</h3>
        </div>
        <div class="stock-info">
          <div class="stock-item">
            <span class="stock-label">Steril:</span>
            <span class="stock-value">{{ stats.stock?.cssd?.steril || 0 }}</span>
          </div>
          <div class="stock-item">
            <span class="stock-label">Kotor:</span>
            <span class="stock-value">{{ stats.stock?.cssd?.kotor || 0 }}</span>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Units Stock</h3>
        </div>
        <div class="stock-info">
          <div class="stock-item">
            <span class="stock-label">Steril:</span>
            <span class="stock-value">{{ stats.stock?.units?.steril || 0 }}</span>
          </div>
          <div class="stock-item">
            <span class="stock-label">Kotor:</span>
            <span class="stock-value">{{ stats.stock?.units?.kotor || 0 }}</span>
          </div>
          <div class="stock-item">
            <span class="stock-label">In Use:</span>
            <span class="stock-value">{{ stats.stock?.units?.in_use || 0 }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Recent Transactions</h3>
      </div>
      <div class="table-container">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Type</th>
              <th>Unit</th>
              <th>Status</th>
              <th>Created By</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="transaction in stats.recent_transactions" :key="transaction.id">
              <td>#{{ transaction.id }}</td>
              <td>
                <span :class="['badge', transaction.type === 'steril' ? 'badge-success' : 'badge-warning']">
                  {{ transaction.type.toUpperCase() }}
                </span>
              </td>
              <td>{{ transaction.unit?.name }}</td>
              <td>
                <span :class="['badge', getStatusClass(transaction.status)]">
                  {{ transaction.status.toUpperCase() }}
                </span>
              </td>
              <td>{{ transaction.creator?.name }}</td>
              <td>{{ formatDate(transaction.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const stats = ref({})
const loading = ref(false)

const fetchStats = async () => {
  loading.value = true
  try {
    const response = await api.get('/dashboard/stats')
    stats.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch stats:', error)
  } finally {
    loading.value = false
  }
}

const getStatusClass = (status) => {
  const classes = {
    pending: 'badge-warning',
    validated: 'badge-success',
    cancelled: 'badge-danger'
  }
  return classes[status] || 'badge-info'
}

const formatDate = (date) => {
  return new Date(date).toLocaleString()
}

onMounted(() => {
  fetchStats()
})
</script>

<style scoped>
.dashboard {
  max-width: 1400px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 0.5rem;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  font-size: 2.5rem;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--gray-900);
}

.stat-label {
  color: var(--gray-600);
  font-size: 0.875rem;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stock-info {
  padding: 1rem 0;
}

.stock-item {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--gray-200);
}

.stock-item:last-child {
  border-bottom: none;
}

.stock-label {
  font-weight: 500;
  color: var(--gray-700);
}

.stock-value {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--primary-color);
}
</style>