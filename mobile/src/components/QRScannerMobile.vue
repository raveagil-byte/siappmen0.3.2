<template>
  <div class="qr-scanner-mobile">
    <div v-if="!isScanning" class="scanner-start">
      <ion-button @click="startScanning" expand="block" size="large">
        <ion-icon :icon="qrCodeOutline" slot="start"></ion-icon>
        Start QR Scanner
      </ion-button>
    </div>

    <div v-else class="scanner-active">
      <video ref="videoElement" class="scanner-video" playsinline></video>
      <div class="scanner-overlay">
        <div class="scanner-frame"></div>
        <p class="scanner-hint">Align QR code within frame</p>
      </div>
      <div class="scanner-controls">
        <ion-button @click="stopScanning" color="danger" expand="block">
          Stop Scanner
        </ion-button>
      </div>
    </div>

    <ion-toast
      :is-open="!!error"
      :message="error"
      :duration="3000"
      color="danger"
      @didDismiss="error = ''"
    ></ion-toast>
  </div>
</template>

<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import { IonButton, IonIcon, IonToast } from '@ionic/vue'
import { qrCodeOutline } from 'ionicons/icons'
import { BrowserMultiFormatReader } from '@zxing/browser'
import { BarcodeScanner } from '@capacitor-mlkit/barcode-scanning'

const emit = defineEmits(['scan'])

const videoElement = ref<HTMLVideoElement | null>(null)
const isScanning = ref(false)
const error = ref('')
let codeReader: BrowserMultiFormatReader | null = null

const startScanning = async () => {
  error.value = ''
  
  try {
    codeReader = new BrowserMultiFormatReader()
    
    // Get video devices
    const videoInputDevices = await BrowserMultiFormatReader.listVideoInputDevices()
    
    // Find rear camera (environment facing)
    let selectedDeviceId: string | undefined
    for (const device of videoInputDevices) {
      const label = device.label.toLowerCase()
      if (label.includes('back') || label.includes('rear') || label.includes('environment')) {
        selectedDeviceId = device.deviceId
        break
      }
    }
    
    // Fallback to first camera if rear not found
    if (!selectedDeviceId && videoInputDevices.length > 0) {
      // Try last device (usually rear on mobile)
      selectedDeviceId = videoInputDevices[videoInputDevices.length - 1].deviceId
    }
    
    if (!selectedDeviceId) {
      throw new Error('No camera found')
    }
    
    isScanning.value = true
    
    // Start decoding with rear camera
    await codeReader.decodeFromVideoDevice(
      selectedDeviceId,
      videoElement.value!,
      (result, err) => {
        if (result) {
          emit('scan', result.getText())
          stopScanning()
        }
        if (err && err.name !== 'NotFoundException') {
          console.error('Scan error:', err)
        }
      }
    )
  } catch (err: any) {
    error.value = err.message || 'Failed to start camera'
    console.error('Scanner error:', err)
    isScanning.value = false
  }
}

const stopScanning = async () => {
  try {
    // Stop MLKit scanner if running
    await BarcodeScanner.stopScan()
  } catch (error) {
    // MLKit might not be running, ignore
  }

  // Stop ZXing scanner if running
  if (codeReader) {
    codeReader = null
  }

  isScanning.value = false
}

onUnmounted(() => {
  stopScanning()
})

defineExpose({
  startScanning,
  stopScanning
})
</script>

<style scoped>
.qr-scanner-mobile {
  width: 100%;
  height: 100%;
}

.scanner-start {
  padding: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 300px;
}

.scanner-active {
  position: relative;
  width: 100%;
  height: 100%;
  background: black;
}

.scanner-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.scanner-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.scanner-frame {
  width: 250px;
  height: 250px;
  border: 3px solid #3880ff;
  border-radius: 12px;
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.6);
}

.scanner-hint {
  color: white;
  margin-top: 1rem;
  text-align: center;
  font-size: 1rem;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
}

.scanner-controls {
  position: absolute;
  bottom: 2rem;
  left: 1rem;
  right: 1rem;
}
</style>