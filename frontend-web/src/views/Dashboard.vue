<template>
  <div class="dashboard-container">
    <h1 class="dashboard-title">CSSD Dashboard</h1>

    <!-- Summary Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <h3>Total Units</h3>
        <p>{{ stats.summary?.total_units || 0 }}</p>
      </div>
      <div class="stat-card">
        <h3>Total Instruments</h3>
        <p>{{ stats.summary?.total_instruments || 0 }}</p>
      </div>
      <div class="stat-card">
        <h3>Pending Transactions</h3>
        <p>{{ stats.summary?.pending_transactions || 0 }}</p>
      </div>
      <div class="stat-card">
        <h3>Completed Transactions</h3>
        <p>{{ stats.summary?.completed_transactions || 0 }}</p>
      </div>
    </div>

    <!-- Stock Levels Grid -->
    <div class="stock-grid">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">CSSD Inventory</h3>
        </div>
        <div class="card-body">
          <div class="stock-item">
            <span>Sterile Stock:</span>
            <strong>{{ stats.stock?.cssd?.steril || 0 }}</strong>
          </div>
          <div class="stock-item">
            <span>Dirty Stock:</span>
            <strong>{{ stats.stock?.cssd?.kotor || 0 }}</strong>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">All Units Inventory</h3>
        </div>
        <div class="card-body">
          <div class="stock-item">
            <span>In-Use Stock:</span>
            <strong>{{ stats.stock?.units?.in_use || 0 }}</strong>
          </div>
          <div class="stock-item">
            <span>Dirty Stock:</span>
            <strong>{{ stats.stock?.units?.kotor || 0 }}</strong>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Recent Activity</h3>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Type</th>
              <th>Unit</th>
              <th>Status</th>
              <th>Created By</th>
              <th>Validated By</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center">Loading...</td>
            </tr>
            <tr v-else-if="stats.recent_transactions?.length === 0">
              <td colspan="7" class="text-center">No recent transactions found.</td>
            </tr>
            <tr v-for="tx in stats.recent_transactions" :key="tx.id">
              <td>#{{ tx.id }}</td>
              <td>
                <span :class="['badge', getTransactionTypeClass(tx.type)]">{{ formatTransactionType(tx.type) }}</span>
              </td>
              <td>{{ tx.unit?.name || 'N/A' }}</td>
              <td>
                <span :class="['badge', getStatusClass(tx.status)]">{{ tx.status }}</span>
              </td>
              <td>{{ tx.creator?.name || 'N/A' }}</td>
              <td>{{ tx.validator?.name || 'N/A' }}</td>
              <td>{{ formatDate(tx.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/services/api';

const stats = ref({});
const loading = ref(false);

const fetchStats = async () => {
  loading.value = true;
  try {
    const response = await api.get('/dashboard/stats');
    stats.value = response.data.data;
  } catch (error) {
    console.error('Failed to fetch dashboard stats:', error);
    // You might want to show a toast or an error message to the user.
  } finally {
    loading.value = false;
  }
};

const getStatusClass = (status) => ({
  pending: 'badge-warning',
  validated: 'badge-success',
  completed: 'badge-success',
  cancelled: 'badge-danger',
}[status] || 'badge-secondary');

const getTransactionTypeClass = (type) => ({
  distribusi_steril: 'badge-info',
  pengambilan_kotor: 'badge-primary',
  pengembalian_cssd: 'badge-dark',
}[type] || 'badge-secondary');

const formatTransactionType = (type) => (type || '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
const formatDate = (date) => new Date(date).toLocaleString();

onMounted(fetchStats);
</script>

<style scoped>
.dashboard-container {
  padding: 2rem;
}
.dashboard-title {
  margin-bottom: 2rem;
}
.stats-grid, .stock-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}
.stat-card, .card {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  padding: 1.5rem;
}
.stat-card h3 {
  font-size: 1rem;
  color: #6c757d;
  margin-bottom: 0.5rem;
}
.stat-card p {
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
}
.card-header {
  border-bottom: 1px solid #dee2e6;
  padding-bottom: 1rem;
  margin-bottom: 1rem;
}
.stock-item {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
}
.table-responsive {
  overflow-x: auto;
}
.table {
  width: 100%;
  border-collapse: collapse;
}
.table th, .table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #dee2e6;
}
.badge {
  padding: 0.4em 0.7em;
  border-radius: 0.25rem;
  color: #fff;
  font-size: 0.8em;
  font-weight: 600;
}
.badge-primary { background: #007bff; }
.badge-secondary { background: #6c757d; }
.badge-success { background: #28a745; }
.badge-danger { background: #dc3545; }
.badge-warning { background: #ffc107; color: #212529; }
.badge-info { background: #17a2b8; }
.badge-dark { background: #343a40; }
</style>
