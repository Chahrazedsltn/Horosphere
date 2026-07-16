import api from './api'
import type { Pointage } from '../types'

export const pointageService = {
  async arriver(latitude: number, longitude: number): Promise<{ pointage: Pointage; avertissement?: string }> {
    const res = await api.post('/pointages/arriver', { latitude, longitude })
    return { pointage: res.data.data, avertissement: res.data.avertissement }
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

  async liste(params?: { date_debut?: string; date_fin?: string; utilisateur_id?: number }): Promise<Pointage[]> {
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

  async manuel(data: { date_jour: string; heure_arrivee: string; heure_depart: string; motif?: string }): Promise<Pointage> {
    const res = await api.post('/pointages/manuel', data)
    return res.data.data
  },

  async joursFeries(annee: number): Promise<{ date: string; nom: string }[]> {
    const res = await api.get('/pointages/jours-feries', { params: { annee } })
    return res.data.data
  },

  async compteurs(): Promise<{
    conges_total: number; conges_pris: number; conges_restants: number;
    absences_annee: number;
  }> {
    const res = await api.get('/pointages/compteurs')
    return res.data.data
  },
}
