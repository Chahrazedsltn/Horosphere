import api from './api'
import type { Demande } from '../types'

export const demandeService = {
  async liste(): Promise<Demande[]> {
    const res = await api.get('/demandes')
    return res.data.data
  },

  async enAttente(): Promise<Demande[]> {
    const res = await api.get('/demandes/en-attente')
    return res.data.data
  },

  async creer(data: {
    type_demande: string
    date_debut: string
    date_fin: string
    motif?: string
  }, file?: File): Promise<Demande> {
    const formData = new FormData()
    formData.append('type_demande', data.type_demande)
    formData.append('date_debut', data.date_debut)
    formData.append('date_fin', data.date_fin)
    if (data.motif) formData.append('motif', data.motif)
    if (file) formData.append('justificatif', file)
    const res = await api.post('/demandes', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    return res.data.data
  },

  async creerParRh(data: {
    utilisateur_id: number
    type_demande: string
    date_debut: string
    date_fin: string
    motif?: string
  }): Promise<Demande> {
    const res = await api.post('/demandes/rh', data)
    return res.data.data
  },

  async downloadJustificatif(id: number, filename: string): Promise<void> {
    const res = await api.get(`/demandes/${id}/justificatif`, { responseType: 'blob' })
    const blob = new Blob([res.data])
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    a.click()
    window.URL.revokeObjectURL(url)
  },

  async traiter(id: number, decision: 'APPROUVEE' | 'REJETEE'): Promise<Demande> {
    const res = await api.put(`/demandes/${id}/traiter`, { decision })
    return res.data.data
  },

  async genererDocument(id: number, typeDocument: string): Promise<{ id: number; fileName: string; downloadUrl: string }> {
    const res = await api.post(`/demandes/${id}/document`, { type_document: typeDocument })
    return res.data.data
  },
}
