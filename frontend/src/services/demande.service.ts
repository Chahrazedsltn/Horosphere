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
  }): Promise<Demande> {
    const res = await api.post('/demandes', data)
    return res.data.data
  },

  async traiter(id: number, decision: 'APPROUVEE' | 'REJETEE'): Promise<Demande> {
    const res = await api.put(`/demandes/${id}/traiter`, { decision })
    return res.data.data
  },
}
