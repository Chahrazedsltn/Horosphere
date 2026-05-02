import api from './api'
import type { Site } from '../types'

export const siteService = {
  async liste(): Promise<Site[]> {
    const res = await api.get('/sites')
    return res.data.data
  },

  async creer(data: Omit<Site, 'id'>): Promise<Site> {
    const res = await api.post('/sites', {
      nom: data.nom,
      adresse: data.adresse,
      latitude: data.latitude,
      longitude: data.longitude,
      rayon_metres: data.rayonMetres,
      geofencing_actif: data.geofencingActif,
    })
    return res.data.data
  },

  async modifier(id: number, data: Partial<Omit<Site, 'id'>>): Promise<Site> {
    const res = await api.put(`/sites/${id}`, {
      nom: data.nom,
      adresse: data.adresse,
      latitude: data.latitude,
      longitude: data.longitude,
      rayon_metres: data.rayonMetres,
      geofencing_actif: data.geofencingActif,
    })
    return res.data.data
  },

  async supprimer(id: number): Promise<void> {
    await api.delete(`/sites/${id}`)
  },
}
