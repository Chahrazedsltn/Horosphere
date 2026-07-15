import { create } from 'zustand'
import { devtools } from 'zustand/middleware'
import type { User } from '../types'

// ─── Lecture synchrone du state persisté ────────────────────────
function loadPersistedAuth() {
  try {
    const raw = localStorage.getItem('horosphere-auth') ?? sessionStorage.getItem('horosphere-auth')
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
  remember: boolean
  setAuth: (user: User, token: string, remember?: boolean) => void
  logout: () => void
  updateUser: (user: User) => void
}

// ─── Persist helper : écrit dans le bon storage ─────────────────
function persistState(state: Pick<AuthState, 'user' | 'token' | 'isAuthenticated' | 'remember'>) {
  const value = JSON.stringify({ state, version: 0 })
  if (state.remember) {
    localStorage.setItem('horosphere-auth', value)
    sessionStorage.removeItem('horosphere-auth')
  } else {
    sessionStorage.setItem('horosphere-auth', value)
    localStorage.removeItem('horosphere-auth')
  }
}

export const useAuthStore = create<AuthState>()(
  devtools(
    (set, get) => ({
      user: persisted?.user ?? null,
      token: persisted?.token ?? null,
      isAuthenticated: persisted?.isAuthenticated ?? false,
      remember: persisted?.remember ?? false,

      setAuth: (user, token, remember = true) => {
        if (remember) {
          localStorage.setItem('token', token)
          sessionStorage.removeItem('token')
        } else {
          sessionStorage.setItem('token', token)
          localStorage.removeItem('token')
        }
        set({ user, token, isAuthenticated: true, remember })
        persistState({ user, token, isAuthenticated: true, remember })
      },

      logout: () => {
        localStorage.removeItem('token')
        sessionStorage.removeItem('token')
        localStorage.removeItem('horosphere-auth')
        sessionStorage.removeItem('horosphere-auth')
        set({ user: null, token: null, isAuthenticated: false, remember: false })
      },

      updateUser: (user) => {
        set({ user })
        const s = get()
        persistState({ user, token: s.token, isAuthenticated: s.isAuthenticated, remember: s.remember })
      },
    }),
    {
      name: 'auth-store',
      // Exclude token from DevTools serialization for security
      serialize: { replacer: (key: string, value: unknown) => (key === 'token' ? '***' : value) },
    },
  ),
)
