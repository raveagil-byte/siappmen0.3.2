import axios from 'axios';
import { Preferences } from '@capacitor/preferences';
import { Network } from '@capacitor/network';
import { v4 as uuidv4 } from 'uuid';

// Use a simple IndexedDB wrapper for robust offline storage.
const db = {
  get: (key) => JSON.parse(localStorage.getItem(key) || 'null'),
  set: (key, value) => localStorage.setItem(key, JSON.stringify(value)),
  remove: (key) => localStorage.removeItem(key),
};

const OFFLINE_QUEUE_KEY = 'offline_request_queue';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-Device-Type': 'mobile',
  },
  timeout: 15000, // Set a reasonable timeout.
});

// --- Interceptors ---
api.interceptors.request.use(
  async (config) => {
    const { value: token } = await Preferences.get({ key: 'token' });
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    // Handle authentication errors.
    if (error.response?.status === 401) {
      await Preferences.remove({ key: 'token' });
      await Preferences.remove({ key: 'user' });
      window.location.href = '/login';
      return Promise.reject(error);
    }

    // Handle offline scenarios for mutable requests.
    const isNetworkError = !error.response;
    const isMutableMethod = ['post', 'put', 'delete'].includes(originalRequest.method);

    if (isNetworkError && isMutableMethod && !originalRequest._isRetry) {
      await addToOfflineQueue(originalRequest);
      // Return a custom offline response to notify the UI.
      return Promise.resolve({
        data: {
          success: true,
          offline: true,
          message: 'Request saved offline and will be synced later.',
        },
      });
    }

    return Promise.reject(error);
  }
);

// --- Offline Queue Logic ---
const addToOfflineQueue = async (requestConfig) => {
  const queue = (await db.get(OFFLINE_QUEUE_KEY)) || [];
  queue.push({
    id: uuidv4(),
    url: requestConfig.url,
    method: requestConfig.method,
    data: requestConfig.data,
    headers: requestConfig.headers,
  });
  await db.set(OFFLINE_QUEUE_KEY, queue);
};

export const syncOfflineQueue = async () => {
  const queue = (await db.get(OFFLINE_QUEUE_KEY)) || [];
  if (queue.length === 0) return;

  const { connected } = await Network.getStatus();
  if (!connected) return;

  const successfullySynced = [];

  for (const request of queue) {
    try {
      await api.request({
        method: request.method,
        url: request.url,
        data: JSON.parse(request.data), // Data is stringified in axios config
        headers: request.headers,
        _isRetry: true, // Mark as a retry to avoid re-queuing.
      });
      successfullySynced.push(request.id);
    } catch (error) {
      console.error('Failed to sync request:', request, 'Error:', error);
      // If a request fails with a server error (e.g., 4xx), it should be removed from the queue.
      if (axios.isAxiosError(error) && error.response) {
        successfullySynced.push(request.id); // Remove from queue to prevent infinite retries.
      }
    }
  }

  if (successfullySynced.length > 0) {
    const newQueue = queue.filter((req) => !successfullySynced.includes(req.id));
    await db.set(OFFLINE_QUEUE_KEY, newQueue);
  }
};

export const hasPendingSync = async () => {
  const queue = (await db.get(OFFLINE_QUEUE_KEY)) || [];
  return queue.length > 0;
};

// --- Network Status Listener ---
Network.addListener('networkStatusChange', (status) => {
  if (status.connected) {
    // Add a small delay to ensure the network is stable.
    setTimeout(syncOfflineQueue, 3000);
  }
});

// Initial sync attempt on app load.
setTimeout(syncOfflineQueue, 5000);

export default api;
