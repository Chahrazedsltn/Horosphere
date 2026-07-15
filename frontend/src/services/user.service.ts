import api from './api'
import type { User, DashboardStats } from '../types'

export const userService = {
  async liste(): Promise<User[]> {
    const res = await api.get('/users')
    return res.data.data
  },

  async creer(data: {
    email: string
    password: string
    prenom: string
    nom: string
    role: string
    departement?: string
  }): Promise<User> {
    const res = await api.post('/users', data)
    return res.data.data
  },

  async modifier(id: number, data: Partial<{
    email: string
    password: string
    prenom: string
    nom: string
    role: string
    departement: string
  }>): Promise<User> {
    const res = await api.put(`/users/${id}`, data)
    return res.data.data
  },

  async supprimer(id: number): Promise<void> {
    await api.delete(`/users/${id}`)
  },

  async statsDashboard(): Promise<DashboardStats> {
    const res = await api.get('/users/stats/dashboard')
    return res.data.data
  },

  async statsEmployes(mois?: number, annee?: number): Promise<EmployeStats[]> {
    const res = await api.get('/users/stats/employes', { params: { mois, annee } })
    return res.data.data
  },
}

export interface EmployeStats {
  id: number
  prenom: string
  nom: string
  departement: string | null
  initiales: string
  heures_total: number
  minutes_total: number
  jours_presents: number
  anomalies: number
  total_pointages: number
}
