<template>
  <div class="qr-generator-mobile">
    <div v-if="qrDataUrl" class="qr-display">
      <img :src="qrDataUrl" alt="QR Code" class="qr-image" />
      <p class="qr-label">{{ label }}</p>
    </div>
    <div v-else class="qr-loading">
      <ion-spinner></ion-spinner>
      <p>Generating QR Code...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { IonSpinner } from '@ionic/vue'
import QRCode from 'qrcode'

const props = defineProps<{
  content: string
  label?: string
}>()

const qrDataUrl = ref('')

const generateQR = async () => {
  try {
    qrDataUrl.value = await QRCode.toDataURL(props.content, {
      width: 300,
      margin: 2,
      errorCorrectionLevel: 'H'
    })
  } catch (error) {
    console.error('QR generation error:', error)
  }
}

watch(() => props.content, () => {
  if (props.content) {
    generateQR()
  }
}, { immediate: true })

onMounted(() => {
  if (props.content) {
    generateQR()
  }
})
</script>

<style scoped>
.qr-generator-mobile {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.qr-display {
  text-align: center;
}

.qr-image {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.qr-label {
  margin-top: 1rem;
  font-size: 0.875rem;
  color: var(--ion-color-medium);
}

.qr-loading {
  text-align: center;
}

.qr-loading p {
  margin-top: 1rem;
  color: var(--ion-color-medium);
}
</style>