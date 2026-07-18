import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || '/api'

const api = axios.create({
  baseURL: API_URL,
  headers: { 'Content-Type': 'application/json' },
  timeout: 15000,
})

// ─── Intercepteur requête : injecte le JWT ───────────────────────
api.interceptors.request.use(
  (config) => {
    const token = sessionStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error),
)

// ─── Intercepteur réponse : gère le 401 ─────────────────────────
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && !error.config?.url?.includes('/auth/login')) {
      sessionStorage.removeItem('token')
      sessionStorage.removeItem('horosphere-auth')
      localStorage.removeItem('token')
      localStorage.removeItem('horosphere-auth')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  },
)

export default api
