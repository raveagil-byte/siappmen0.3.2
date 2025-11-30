<template>
  <div class="instruments">
    <h1>Instruments</h1>
    <button @click="showCreateForm = true">+ Add Instrument</button>

    <div v-if="showCreateForm" class="form-popup">
      <form @submit.prevent="createInstrument">
        <label>Name:</label>
        <input v-model="newInstrument.name" required />

        <label>Description:</label>
        <input v-model="newInstrument.description" />

        <button type="submit">Create</button>
        <button type="button" @click="showCreateForm = false">Cancel</button>
      </form>
    </div>

    <table>
      <thead>
        <tr><th>Name</th><th>Description</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <tr v-for="instrument in instruments" :key="instrument.id">
          <td>{{ instrument.name }}</td>
          <td>{{ instrument.description }}</td>
          <td>
            <button @click="editInstrument(instrument)">Edit</button>
            <button @click="deleteInstrument(instrument.id)">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="showEditForm" class="form-popup">
      <form @submit.prevent="updateInstrument">
        <label>Name:</label>
        <input v-model="editInstrumentData.name" required />

        <label>Description:</label>
        <input v-model="editInstrumentData.description" />

        <button type="submit">Update</button>
        <button type="button" @click="showEditForm = false">Cancel</button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const instruments = ref([])
const showCreateForm = ref(false)
const showEditForm = ref(false)
const newInstrument = ref({ name: '', description: '' })
const editInstrumentData = ref({ id: null, name: '', description: '' })

const fetchInstruments = async () => {
  try {
    const res = await api.get('/instruments')
    instruments.value = res.data.data
  } catch (e) {
    alert('Failed to fetch instruments')
  }
}

const createInstrument = async () => {
  try {
    const res = await api.post('/instruments', newInstrument.value)
    instruments.value.push(res.data.data)
    newInstrument.value = { name: '', description: '' }
    showCreateForm.value = false
  } catch (e) {
    alert('Failed to create instrument')
  }
}

const editInstrument = (instrument) => {
  editInstrumentData.value = Object.assign({}, instrument)
  showEditForm.value = true
}

const updateInstrument = async () => {
  try {
    await api.put(`/instruments/${editInstrumentData.value.id}`, {
      name: editInstrumentData.value.name,
      description: editInstrumentData.value.description
    })
    const index = instruments.value.findIndex(i => i.id === editInstrumentData.value.id)
    if (index !== -1) {
      instruments.value[index] = Object.assign({}, editInstrumentData.value)
    }
    showEditForm.value = false
  } catch (e) {
    alert('Failed to update instrument')
  }
}

const deleteInstrument = async (id) => {
  if (!confirm('Are you sure to delete this instrument?')) return

  try {
    await api.delete(`/instruments/${id}`)
    instruments.value = instruments.value.filter(ins => ins.id !== id)
  } catch (e) {
    alert('Failed to delete instrument')
  }
}

onMounted(() => {
  fetchInstruments()
})
</script>

<style scoped>
.instruments {
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
.form-popup {
  background: #f9f9f9;
  padding: 1rem;
  margin-top: 1rem;
  border: 1px solid #ddd;
}
button {
  margin-right: 0.5rem;
}
</style>
