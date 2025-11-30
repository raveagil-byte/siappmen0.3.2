import { Preferences } from '@capacitor/preferences'
import axios from 'axios'

interface PendingTransaction {
  id: string
  data: any
  timestamp: number
  type: string
}

class OfflineSyncService {
  private readonly PENDING_TRANSACTIONS_KEY = 'pending_transactions'

  async savePendingTransaction(data: any): Promise<void> {
    try {
      const pendingTransactions = await this.getPendingTransactions()
      const transaction: PendingTransaction = {
        id: Date.now().toString(),
        data,
        timestamp: Date.now(),
        type: this.getTransactionType(data)
      }

      pendingTransactions.push(transaction)
      await Preferences.set({
        key: this.PENDING_TRANSACTIONS_KEY,
        value: JSON.stringify(pendingTransactions)
      })
    } catch (error) {
      console.error('Error saving pending transaction:', error)
      throw error
    }
  }

  async syncPendingTransactions(): Promise<void> {
    try {
      const pendingTransactions = await this.getPendingTransactions()

      if (pendingTransactions.length === 0) {
        return
      }

      // Create axios instance for syncing
      const syncApi = axios.create({
        baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Device-Type': 'mobile'
        }
      })

      // Add auth header
      const { value: token } = await Preferences.get({ key: 'token' })
      if (token) {
        syncApi.defaults.headers.common['Authorization'] = `Bearer ${token}`
      }

      for (const transaction of pendingTransactions) {
        try {
          await syncApi.post(`/transactions/create-${transaction.type}`, transaction.data)
        } catch (error) {
          console.error(`Failed to sync transaction ${transaction.id}:`, error)
          // Continue with other transactions
        }
      }

      // Clear synced transactions
      await Preferences.set({
        key: this.PENDING_TRANSACTIONS_KEY,
        value: JSON.stringify([])
      })
    } catch (error) {
      console.error('Error syncing pending transactions:', error)
      throw error
    }
  }

  async hasPendingTransactions(): Promise<boolean> {
    const pendingTransactions = await this.getPendingTransactions()
    return pendingTransactions.length > 0
  }

  private async getPendingTransactions(): Promise<PendingTransaction[]> {
    try {
      const { value } = await Preferences.get({ key: this.PENDING_TRANSACTIONS_KEY })
      return value ? JSON.parse(value) : []
    } catch (error) {
      console.error('Error getting pending transactions:', error)
      return []
    }
  }

  private getTransactionType(data: any): string {
    // Determine transaction type based on data structure
    if (data.unit_id && data.status) {
      return 'status-update'
    }
    if (data.pickup_date && data.items) {
      return 'pickup'
    }
    if (data.validation_date && data.items) {
      return 'validation'
    }
    return 'unknown'
  }
}

const offlineSync = new OfflineSyncService()
export default offlineSync
