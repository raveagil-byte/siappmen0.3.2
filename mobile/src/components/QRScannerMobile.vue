<template>
  <div class="qr-scanner-container">
    <div v-if="!isScanning" class="scanner-prompt">
      <ion-button @click="startScan" expand="block" size="large">
        <ion-icon :icon="qrCodeOutline" slot="start"></ion-icon>
        Start Camera Scan
      </ion-button>
    </div>

    <!-- This div is used by the Capacitor Barcode Scanner to attach the camera view -->
    <div v-show="isScanning" class="camera-live-preview"></div>

    <div v-if="isScanning" class="scanner-ui">
      <div class="scanner-frame"></div>
      <p class="scanner-hint">Align QR code within the frame</p>
      <ion-button @click="stopScan" color="danger" expand="block">
        Cancel Scan
      </ion-button>
    </div>

    <ion-toast
      :is-open="!!errorMessage"
      :message="errorMessage"
      :duration="3000"
      color="danger"
      @didDismiss="errorMessage = ''"
    ></ion-toast>
  </div>
</template>

<script setup lang="ts">
import { ref, onUnmounted } from 'vue';
import { IonButton, IonIcon, IonToast } from '@ionic/vue';
import { qrCodeOutline } from 'ionicons/icons';
import { BarcodeScanner, SupportedFormat } from '@capacitor-mlkit/barcode-scanning';
import { Capacitor } from '@capacitor/core';

const emit = defineEmits(['scanSuccess', 'scanFail']);

const isScanning = ref(false);
const errorMessage = ref('');

const checkPermissions = async () => {
  if (Capacitor.getPlatform() === 'web') return true; // Permissions not needed for web fallback (if any).
  try {
    const { camera } = await BarcodeScanner.requestPermissions();
    return camera === 'granted' || camera === 'limited';
  } catch (error) {
    console.error('Permission request error:', error);
    return false;
  }
};

const startScan = async () => {
  if (!await checkPermissions()) {
    errorMessage.value = 'Camera permission is required to scan QR codes.';
    emit('scanFail', errorMessage.value);
    return;
  }

  try {
    // Hide the webview background to make the camera feed visible.
    document.body.classList.add('barcode-scanner-active');
    isScanning.value = true;

    const result = await BarcodeScanner.startScan({
      formats: [SupportedFormat.QR_CODE],
    });

    if (result.hasContent) {
      emit('scanSuccess', result.content);
    } else {
      emit('scanFail', 'No QR code found.');
    }
  } catch (error: any) {
    console.error('Barcode scan error:', error);
    errorMessage.value = 'Failed to start scanner. Is the camera busy?';
    emit('scanFail', errorMessage.value);
  } finally {
    stopScan();
  }
};

const stopScan = async () => {
  if (isScanning.value) {
    document.body.classList.remove('barcode-scanner-active');
    try {
      await BarcodeScanner.stopScan();
    } catch (error) {
      // Ignore errors if the scanner is already stopped.
      console.warn('Error stopping scanner:', error);
    }
  }
  isScanning.value = false;
};

onUnmounted(() => {
  stopScan();
});

defineExpose({
  startScan,
  stopScan,
});
</script>

<style>
/* This global style is required to make the camera feed visible behind the webview */
body.barcode-scanner-active {
  background-color: transparent;
}
</style>

<style scoped>
.qr-scanner-container {
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

.scanner-prompt {
  padding: 2rem;
}

.camera-live-preview {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: -1; /* Place it behind the webview content */
}

.scanner-ui {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.6); /* Semi-transparent overlay */
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
  margin-top: 1.5rem;
  margin-bottom: 2rem;
  text-align: center;
  font-size: 1rem;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}
</style>
