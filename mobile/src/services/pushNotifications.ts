import { PushNotifications } from '@capacitor/push-notifications'
import { Capacitor } from '@capacitor/core'

export class PushNotificationService {
  private static instance: PushNotificationService
  private isInitialized = false

  private constructor() {}

  static getInstance(): PushNotificationService {
    if (!PushNotificationService.instance) {
      PushNotificationService.instance = new PushNotificationService()
    }
    return PushNotificationService.instance
  }

  async initialize() {
    if (!Capacitor.isNativePlatform() || this.isInitialized) {
      return
    }

    try {
      // Request permission
      const permission = await PushNotifications.requestPermissions()
      if (permission.receive !== 'granted') {
        console.warn('Push notification permission denied')
        return
      }

      // Register for push notifications
      await PushNotifications.register()

      // Set up listeners
      this.setupListeners()

      this.isInitialized = true
      console.log('Push notifications initialized')
    } catch (error) {
      console.error('Failed to initialize push notifications:', error)
    }
  }

  private setupListeners() {
    // On registration success
    PushNotifications.addListener('registration', (token) => {
      console.log('Push registration success, token: ' + token.value)
      this.sendTokenToServer(token.value)
    })

    // On registration error
    PushNotifications.addListener('registrationError', (error) => {
      console.error('Push registration failed:', error)
    })

    // On push notification received when app is in foreground
    PushNotifications.addListener('pushNotificationReceived', (notification) => {
      console.log('Push notification received:', notification)
      this.handleNotificationReceived(notification)
    })

    // On push notification action performed (when app is in background)
    PushNotifications.addListener('pushNotificationActionPerformed', (action) => {
      console.log('Push notification action performed:', action)
      this.handleNotificationAction(action)
    })
  }

  private async sendTokenToServer(token: string) {
    try {
      // Send token to backend for storage
      const response = await fetch('/api/user/device-token', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        },
        body: JSON.stringify({ device_token: token, platform: Capacitor.getPlatform() })
      })

      if (!response.ok) {
        throw new Error('Failed to send device token')
      }
    } catch (error) {
      console.error('Failed to send device token to server:', error)
    }
  }

  private handleNotificationReceived(notification: any) {
    // Show local notification or update UI
    if (notification.data?.type === 'stock_alert') {
      this.showStockAlert(notification)
    } else if (notification.data?.type === 'transaction_update') {
      this.showTransactionUpdate(notification)
    }
  }

  private handleNotificationAction(action: any) {
    // Handle user interaction with notification
    const { notification } = action

    if (notification.data?.action_url) {
      // Navigate to specific page
      window.location.href = notification.data.action_url
    }
  }

  private showStockAlert(notification: any) {
    // Show in-app alert for stock warnings
    const event = new CustomEvent('stockAlert', {
      detail: {
        title: notification.title,
        body: notification.body,
        data: notification.data
      }
    })
    window.dispatchEvent(event)
  }

  private showTransactionUpdate(notification: any) {
    // Show in-app alert for transaction updates
    const event = new CustomEvent('transactionUpdate', {
      detail: {
        title: notification.title,
        body: notification.body,
        data: notification.data
      }
    })
    window.dispatchEvent(event)
  }

  // Send local notification
  async showLocalNotification(title: string, body: string, data?: any) {
    if (!Capacitor.isNativePlatform()) {
      // Fallback for web
      if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(title, { body, data })
      }
      return
    }

    try {
      await PushNotifications.createChannel({
        id: 'local_notifications',
        name: 'Local Notifications',
        description: 'Local notifications for the app',
        importance: 3,
        visibility: 1,
        sound: 'default',
        vibration: true
      })

      // Note: Capacitor doesn't have a direct local notification API
      // You might need to use a plugin like @capacitor/local-notifications
      console.log('Local notification:', { title, body, data })
    } catch (error) {
      console.error('Failed to show local notification:', error)
    }
  }

  // Unregister push notifications
  async unregister() {
    if (!Capacitor.isNativePlatform()) {
      return
    }

    try {
      await PushNotifications.unregister()
      this.isInitialized = false
      console.log('Push notifications unregistered')
    } catch (error) {
      console.error('Failed to unregister push notifications:', error)
    }
  }
}

export const pushNotificationService = PushNotificationService.getInstance()
