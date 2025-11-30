# CSSD Mobile App - Ionic + Vue + Capacitor

Mobile application for CSSD medical instrument distribution system with QR code scanning.

## Features

- QR Unit scanning (rear camera enforced)
- QR Transaction scanning for validation
- Offline transaction queue with sync
- Distribusi steril workflow
- Pengambilan kotor workflow
- Transaction history
- Real-time stock updates

## Prerequisites

- Node.js 18+
- npm or yarn
- Android Studio (for Android build)
- Xcode (for iOS build, macOS only)

## Setup

```bash
# Install dependencies
npm install

# Copy environment file
cp .env.example .env

# Edit .env and set API URL
VITE_API_URL=http://your-backend-url/api

# Run in browser (development)
ionic serve

# Add Android platform
ionic cap add android
ionic cap sync android
ionic cap open android

# Add iOS platform (macOS only)
ionic cap add ios
ionic cap sync ios
ionic cap open ios
```

## Camera Configuration

The app is configured to use the rear camera by default for QR scanning:

1. **Primary Scanner**: Uses @zxing/browser with device selection logic
2. **Fallback**: capacitor-mlkit-barcode-scanning for offline scanning
3. **Camera Preference**: Automatically selects rear/back/environment camera

### Camera Permissions

**Android** (`android/app/src/main/AndroidManifest.xml`):
```xml
<uses-permission android:name="android.permission.CAMERA" />
<uses-feature android:name="android.hardware.camera" />
<uses-feature android:name="android.hardware.camera.autofocus" />
```

**iOS** (`ios/App/App/Info.plist`):
```xml
<key>NSCameraUsageDescription</key>
<string>This app needs camera access to scan QR codes</string>
```

## Offline Support

The app includes offline transaction queuing:

1. Transactions created offline are stored in local storage
2. When connection is restored, queued transactions sync automatically
3. User is notified of sync status

## Build for Production

### Android

```bash
# Build web assets
npm run build

# Sync with Capacitor
ionic cap sync android

# Open in Android Studio
ionic cap open android

# Build APK/AAB in Android Studio
```

### iOS

```bash
# Build web assets
npm run build

# Sync with Capacitor
ionic cap sync ios

# Open in Xcode
ionic cap open ios

# Build in Xcode
```

## Project Structure

```
mobile/
├── src/
│   ├── components/
│   │   ├── QRScannerMobile.vue
│   │   └── QRGeneratorMobile.vue
│   ├── views/
│   │   ├── Login.vue
│   │   ├── Home.vue
│   │   ├── ScanUnit.vue
│   │   ├── DistribusiSteril.vue
│   │   ├── ValidasiSteril.vue
│   │   ├── KotorPickup.vue
│   │   ├── ValidasiKotor.vue
│   │   └── History.vue
│   ├── stores/
│   │   └── auth.ts
│   ├── services/
│   │   └── api.ts
│   ├── router/
│   │   └── index.ts
│   └── main.ts
├── android/
├── ios/
├── capacitor.config.ts
└── package.json
```

## Testing

Test on real devices for best camera performance:

```bash
# Run on Android device
ionic cap run android --target=<device-id>

# Run on iOS device
ionic cap run ios --target=<device-id>
```

## Troubleshooting

### Camera not working
- Check permissions in device settings
- Ensure HTTPS or localhost (required for camera access)
- Try different camera selection logic in QRScannerMobile.vue

### QR scanning slow
- Ensure good lighting
- Hold device steady
- Clean camera lens

### Offline sync not working
- Check network connectivity
- Verify API endpoint is reachable
- Check browser console for errors

## License

Proprietary - Hospital CSSD Management System