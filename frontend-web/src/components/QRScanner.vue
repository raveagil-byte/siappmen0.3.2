<template>
  <div class="qr-scanner">
    <div v-if="!isScanning" class="scanner-placeholder">
      <button @click="startScanning" class="btn btn-primary">
        Start QR Scanner
      </button>
    </div>

    <div v-else class="scanner-container">
      <video ref="videoElement" class="scanner-video"></video>
      <div class="scanner-overlay">
        <div class="scanner-frame"></div>
      </div>
      <div class="scanner-controls">
        <button @click="stopScanning" class="btn btn-danger">Stop Scanner</button>
      </div>
      <div v-if="error" class="scanner-error">
        {{ error }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onUnmounted } from 'vue'
import { BrowserMultiFormatReader } from '@zxing/browser'

const emit = defineEmits(['scan'])

const videoElement = ref(null)
const isScanning = ref(false)
const error = ref('')
let codeReader = null

const startScanning = async () => {
  error.value = ''
  
  try {
    codeReader = new BrowserMultiFormatReader()
    
    // Get available video devices
    const videoInputDevices = await codeReader.listVideoInputDevices()
    
    // Try to find rear camera (environment facing)
    let selectedDeviceId = null
    for (const device of videoInputDevices) {
      if (device.label.toLowerCase().includes('back') || 
          device.label.toLowerCase().includes('rear') ||
          device.label.toLowerCase().includes('environment')) {
        selectedDeviceId = device.deviceId
        break
      }
    }
    
    // If no rear camera found, use first available
    if (!selectedDeviceId && videoInputDevices.length > 0) {
      selectedDeviceId = videoInputDevices[0].deviceId
    }
    
    isScanning.value = true
    
    // Start decoding
    await codeReader.decodeFromVideoDevice(
      selectedDeviceId,
      videoElement.value,
      (result, err) => {
        if (result) {
          emit('scan', result.text)
          stopScanning()
        }
        if (err && err.name !== 'NotFoundException') {
          console.error(err)
        }
      }
    )
  } catch (err) {
    error.value = 'Failed to access camera. Please check permissions.'
    console.error('Scanner error:', err)
    isScanning.value = false
  }
}

const stopScanning = () => {
  if (codeReader) {
    codeReader.reset()
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
.qr-scanner {
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
}

.scanner-placeholder {
  text-align: center;
  padding: 2rem;
  background: var(--gray-100);
  border-radius: 0.5rem;
}

.scanner-container {
  position: relative;
  background: black;
  border-radius: 0.5rem;
  overflow: hidden;
}

.scanner-video {
  width: 100%;
  height: auto;
  display: block;
}

.scanner-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.scanner-frame {
  width: 250px;
  height: 250px;
  border: 3px solid var(--primary-color);
  border-radius: 0.5rem;
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
}

.scanner-controls {
  position: absolute;
  bottom: 1rem;
  left: 0;
  right: 0;
  text-align: center;
}

.scanner-error {
  position: absolute;
  top: 1rem;
  left: 1rem;
  right: 1rem;
  background: var(--danger-color);
  color: white;
  padding: 0.75rem;
  border-radius: 0.375rem;
  text-align: center;
}
</style>