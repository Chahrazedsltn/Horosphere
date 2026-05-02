import api from './api'
import type { Pointage } from '../types'

export const pointageService = {
  async arriver(latitude: number, longitude: number): Promise<Pointage> {
    const res = await api.post('/pointages/arriver', { latitude, longitude })
    return res.data.data
  },

  async partir(latitude: number, longitude: number): Promise<Pointage> {
    const res = await api.post('/pointages/partir', { latitude, longitude })
    return res.data.data
  },

  async mesPointages(): Promise<Pointage[]> {
    const res = await api.get('/pointages/mes-pointages')
    return res.data.data
  },

  async enCours(): Promise<Pointage | null> {
    const res = await api.get('/pointages/en-cours')
    return res.data.data
  },

  async liste(params?: { date_debut?: string; date_fin?: string }): Promise<Pointage[]> {
    const res = await api.get('/pointages', { params })
    return res.data.data
  },

  async pause(): Promise<Pointage> {
    const res = await api.post('/pointages/pause')
    return res.data.data
  },

  async reprise(): Promise<Pointage> {
    const res = await api.post('/pointages/reprise')
    return res.data.data
  },

  async stats(): Promise<{ presents_aujourd_hui: number; anomalies_en_cours: number; total_pointages_jour: number }> {
    const res = await api.get('/pointages/stats')
    return res.data.data
  },
}
