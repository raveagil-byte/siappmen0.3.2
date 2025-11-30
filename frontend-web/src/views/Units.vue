<template>
  <div class="units-container">
    <div class="page-header">
      <h1>Manage Units</h1>
      <ion-button @click="openCreateModal">
        <ion-icon :icon="addOutline" slot="start"></ion-icon>
        Add New Unit
      </ion-button>
    </div>

    <!-- Units Table -->
    <div class="card">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Location</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="4" class="text-center">Loading units...</td>
            </tr>
            <tr v-else-if="units.length === 0">
              <td colspan="4" class="text-center">No units found.</td>
            </tr>
            <tr v-for="unit in units" :key="unit.id">
              <td>{{ unit.name }}</td>
              <td>{{ unit.location }}</td>
              <td>
                <span :class="['badge', unit.is_active ? 'badge-success' : 'badge-secondary']">
                  {{ unit.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td>
                <ion-button fill="clear" @click="openEditModal(unit)">
                  <ion-icon :icon="pencilOutline" slot="icon-only"></ion-icon>
                </ion-button>
                <ion-button fill="clear" @click="viewQrCode(unit)">
                  <ion-icon :icon="qrCodeOutline" slot="icon-only"></ion-icon>
                </ion-button>
                <ion-button fill="clear" color="danger" @click="confirmDelete(unit)">
                  <ion-icon :icon="trashOutline" slot="icon-only"></ion-icon>
                </ion-button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <ion-modal :is-open="isModalOpen">
      <ion-header>
        <ion-toolbar>
          <ion-title>{{ isEditMode ? 'Edit Unit' : 'Create New Unit' }}</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="closeModal">Close</ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>
      <ion-content class="ion-padding">
        <form @submit.prevent="saveUnit">
          <ion-item>
            <ion-label position="floating">Unit Name</ion-label>
            <ion-input v-model="currentUnit.name" required></ion-input>
          </ion-item>
          <ion-item>
            <ion-label position="floating">Location</ion-label>
            <ion-input v-model="currentUnit.location" required></ion-input>
          </ion-item>
          <ion-item>
            <ion-label position="floating">Description</ion-label>
            <ion-textarea v-model="currentUnit.description"></ion-textarea>
          </ion-item>
          <ion-item>
            <ion-label>Active</ion-label>
            <ion-toggle v-model="currentUnit.is_active"></ion-toggle>
          </ion-item>
          <ion-button type="submit" expand="block" class="ion-margin-top" :disabled="saving">
            {{ saving ? 'Saving...' : 'Save' }}
          </ion-button>
        </form>
      </ion-content>
    </ion-modal>

    <!-- QR Code Modal -->
    <ion-modal :is-open="isQrModalOpen">
      <ion-header>
        <ion-toolbar>
          <ion-title>QR Code for {{ currentUnit.name }}</ion-title>
           <ion-buttons slot="end">
            <ion-button @click="closeQrModal">Close</ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>
      <ion-content class="ion-padding text-center">
        <div v-if="qrCodeUrl" class="qr-code-container">
          <img :src="qrCodeUrl" alt="QR Code" />
          <p>UNIT:{{ currentUnit.uuid }}</p>
          <ion-button @click="printQrCode">Print</ion-button>
        </div>
      </ion-content>
    </ion-modal>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButton, IonIcon, IonModal, IonInput, IonTextarea, IonItem, IonLabel, IonToggle, alertController } from '@ionic/vue';
import { addOutline, pencilOutline, trashOutline, qrCodeOutline } from 'ionicons/icons';
import api from '@/services/api';
import QRCode from 'qrcode';

const units = ref([]);
const loading = ref(false);
const saving = ref(false);
const isModalOpen = ref(false);
const isQrModalOpen = ref(false);
const isEditMode = ref(false);
const currentUnit = ref({ name: '', location: '', description: '', is_active: true });
const qrCodeUrl = ref('');

const fetchUnits = async () => {
  loading.value = true;
  try {
    const response = await api.get('/units');
    units.value = response.data.data;
  } catch (error) {
    console.error('Failed to fetch units:', error);
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  isEditMode.value = false;
  currentUnit.value = { name: '', location: '', description: '', is_active: true };
  isModalOpen.value = true;
};

const openEditModal = (unit) => {
  isEditMode.value = true;
  currentUnit.value = { ...unit };
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const saveUnit = async () => {
  saving.value = true;
  try {
    if (isEditMode.value) {
      await api.put(`/units/${currentUnit.value.id}`, currentUnit.value);
    } else {
      await api.post('/units', currentUnit.value);
    }
    closeModal();
    fetchUnits(); // Refresh the list
  } catch (error) {
    console.error('Failed to save unit:', error);
  } finally {
    saving.value = false;
  }
};

const confirmDelete = async (unit) => {
  const alert = await alertController.create({
    header: 'Confirm Delete',
    message: `Are you sure you want to delete the unit "${unit.name}"? This action cannot be undone.`,
    buttons: [
      { text: 'Cancel', role: 'cancel' },
      { text: 'Delete', handler: () => deleteUnit(unit.id) },
    ],
  });
  await alert.present();
};

const deleteUnit = async (id) => {
  try {
    await api.delete(`/units/${id}`);
    fetchUnits();
  } catch (error) {
    console.error('Failed to delete unit:', error);
  }
};

const viewQrCode = async (unit) => {
  currentUnit.value = unit;
  if (unit.uuid) {
    qrCodeUrl.value = await QRCode.toDataURL(`UNIT:${unit.uuid}`, { width: 300 });
    isQrModalOpen.value = true;
  }
};

const closeQrModal = () => {
  isQrModalOpen.value = false;
};

const printQrCode = () => {
  const printWindow = window.open('', '', 'height=600,width=800');
  printWindow.document.write('<html><head><title>Print QR Code</title></head><body>');
  printWindow.document.write(`<img src="${qrCodeUrl.value}" />`);
  printWindow.document.write('</body></html>');
  printWindow.document.close();
  printWindow.print();
};


onMounted(fetchUnits);
</script>

<style scoped>
/* Basic styling for the page, adapt as needed */
.units-container {
  padding: 2rem;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}
.qr-code-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}
</style>
