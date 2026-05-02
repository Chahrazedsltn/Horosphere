import api from './api'
import type { Alerte } from '../types'

export const alerteService = {
  async mesAlertes(): Promise<{ alertes: Alerte[]; non_lues: number }> {
    const res = await api.get('/alertes')
    return { alertes: res.data.data, non_lues: res.data.non_lues }
  },

  async toutes(): Promise<Alerte[]> {
    const res = await api.get('/alertes/toutes')
    return res.data.data
  },

  async marquerLue(id: number): Promise<Alerte> {
    const res = await api.patch(`/alertes/${id}/lire`)
    return res.data.data
  },

  async toutLire(): Promise<void> {
    await api.patch('/alertes/tout-lire')
  },
}
