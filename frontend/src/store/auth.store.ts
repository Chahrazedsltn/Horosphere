import { create } from 'zustand'
import { devtools } from 'zustand/middleware'
import type { User } from '../types'

// ─── Lecture synchrone du state persisté (sessionStorage uniquement) ──
function loadPersistedAuth() {
  try {
    const raw = sessionStorage.getItem('horosphere-auth')
    if (!raw) return null
    const { state } = JSON.parse(raw)
    if (state?.isAuthenticated && state?.token && state?.user) return state
  } catch { /* ignore */ }
  return null
}

const persisted = loadPersistedAuth()

interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  setAuth: (user: User, token: string) => void
  logout: () => void
  updateUser: (user: User) => void
}

// ─── Persist helper : sessionStorage uniquement (pas de localStorage) ──
function persistState(state: Pick<AuthState, 'user' | 'token' | 'isAuthenticated'>) {
  const value = JSON.stringify({ state, version: 0 })
  sessionStorage.setItem('horosphere-auth', value)
}

export const useAuthStore = create<AuthState>()(
  devtools(
    (set, get) => ({
      user: persisted?.user ?? null,
      token: persisted?.token ?? null,
      isAuthenticated: persisted?.isAuthenticated ?? false,

      setAuth: (user, token) => {
        sessionStorage.setItem('token', token)
        set({ user, token, isAuthenticated: true })
        persistState({ user, token, isAuthenticated: true })
      },

      logout: () => {
        sessionStorage.removeItem('token')
        sessionStorage.removeItem('horosphere-auth')
        // Clean up legacy localStorage keys if they exist
        localStorage.removeItem('token')
        localStorage.removeItem('horosphere-auth')
        set({ user: null, token: null, isAuthenticated: false })
      },

      updateUser: (user) => {
        set({ user })
        const s = get()
        persistState({ user, token: s.token, isAuthenticated: s.isAuthenticated })
      },
    }),
    {
      name: 'auth-store',
      // Exclude token from DevTools serialization for security
      serialize: { replacer: (key: string, value: unknown) => (key === 'token' ? '***' : value) },
    },
  ),
)
