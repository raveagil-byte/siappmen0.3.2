<template>
  <div class="reports">
    <h1>Reports</h1>
    <div class="report-buttons">
      <button @click="exportReport('transactions')">Export Transactions Excel</button>
      <button @click="exportReport('stock')">Export Stock Excel</button>
      <button @click="exportReport('activity')">Export Activity Logs Excel</button>
    </div>
  </div>
</template>

<script setup>
import api from '@/services/api'

const exportReport = async (type) => {
  try {
    const res = await api.get(`/report/export-excel?type=${type}`, {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${type}-report.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (e) {
    alert('Failed to export report')
  }
}
</script>

<style scoped>
.reports {
  max-width: 400px;
  margin: 0 auto;
  text-align: center;
}

.report-buttons {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-top: 2rem;
}

button {
  padding: 0.75rem 1rem;
  font-size: 1rem;
  cursor: pointer;
}
</style>
