import api from './api'
import type { User } from '../types'

export const authService = {
  async login(email: string, password: string): Promise<{ token: string; user: User }> {
    const res = await api.post('/auth/login', { email, password })
    return res.data
  },

  async me(): Promise<User> {
    const res = await api.get('/auth/me')
    return res.data.data
  },

  async motDePasseOublie(email: string): Promise<void> {
    await api.post('/auth/mot-de-passe-oublie', { email })
  },

  async reinitialiserMotDePasse(token: string, nouveauMotDePasse: string): Promise<void> {
    await api.post('/auth/reinitialiser-mot-de-passe', { token, nouveau_mot_de_passe: nouveauMotDePasse })
  },

  logout(): void {
    localStorage.removeItem('token')
  },
}
