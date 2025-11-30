import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import { Preferences } from '@capacitor/preferences'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<any>(null)
  const token = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value)

  const login = async (email: string, password: string) => {
    try {
      const response = await api.post('/login', {
        email,
        password,
        device_name: 'mobile'
      })
      
      token.value = response.data.data.token
      user.value = response.data.data.user
      
      // Save to Capacitor Preferences
      await Preferences.set({ key: 'token', value: token.value ?? '' })

      await Preferences.set({ key: 'user', value: JSON.stringify(user.value ?? {}) })

      
      return response.data
    } catch (error) {
      throw error
    }
  }

  const logout = async () => {
    try {
      await api.post('/logout')
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      token.value = null
      user.value = null
      await Preferences.remove({ key: 'token' })
      await Preferences.remove({ key: 'user' })
    }
  }

  const checkAuth = async () => {
    const { value: savedToken } = await Preferences.get({ key: 'token' })
    const { value: savedUser } = await Preferences.get({ key: 'user' })
    
    if (savedToken) {
      token.value = savedToken
      user.value = savedUser ? JSON.parse(savedUser) : null
      
      try {
        const response = await api.get('/user')
        user.value = response.data.data
      } catch (error) {
        // Token invalid
        await logout()
      }
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    logout,
    checkAuth
  }
})