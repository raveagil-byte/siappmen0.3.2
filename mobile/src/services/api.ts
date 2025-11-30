import axios from 'axios'
import { Preferences } from '@capacitor/preferences'
import { Network } from '@capacitor/network'
import offlineSync from './offlineSync'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Device-Type': 'mobile'
  }
})

// Request interceptor
api.interceptors.request.use(
  async (config) => {
    const { value: token } = await Preferences.get({ key: 'token' })
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      await Preferences.remove({ key: 'token' })
      await Preferences.remove({ key: 'user' })
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// Enhanced API methods with offline support
const enhancedApi = {
  ...api,

  async postWithOffline(url: string, data: any, options?: any) {
    const networkStatus = await Network.getStatus()

    if (!networkStatus.connected) {
      // Save to offline storage
      if (url.includes('/transactions/create-')) {
        await offlineSync.savePendingTransaction(data)
        return { data: { success: true, offline: true, message: 'Transaction saved offline' } }
      }
      throw new Error('No internet connection')
    }

    try {
      const response = await api.post(url, data, options)
      return response
    } catch (error) {
      // If network error, try to save offline
      if (!error.response && url.includes('/transactions/create-')) {
        await offlineSync.savePendingTransaction(data)
        return { data: { success: true, offline: true, message: 'Transaction saved offline' } }
      }
      throw error
    }
  },

  async syncOfflineData() {
    const networkStatus = await Network.getStatus()
    if (!networkStatus.connected) {
      throw new Error('No internet connection')
    }

    await offlineSync.syncPendingTransactions()
  },

  async hasPendingSync(): Promise<boolean> {
    return await offlineSync.hasPendingTransactions()
  }
}

export default enhancedApi
