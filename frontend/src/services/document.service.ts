import api from './api'
import type { Document } from '../types'

export const documentService = {
  async mesDocs(): Promise<Document[]> {
    const res = await api.get('/documents')
    return res.data.data
  },

  async exportCsv(params: { date_debut: string; date_fin: string; utilisateur_id?: number }): Promise<Document> {
    const res = await api.post('/exports/csv', params)
    return res.data.data
  },

  async exportPdf(params: { date_debut: string; date_fin: string; utilisateur_id?: number }): Promise<Document> {
    const res = await api.post('/exports/pdf', params)
    return res.data.data
  },

  downloadUrl(doc: Document): string {
    return `${import.meta.env.VITE_API_URL || '/api'}${doc.downloadUrl}`
  },

  async download(doc: Document): Promise<void> {
    const path = doc.downloadUrl.replace(/^\/api/, '')
    const res = await api.get(path, { responseType: 'blob' })
    const mimeType = doc.typeDocument === 'PDF' ? 'application/pdf' : 'text/csv'
    const blob = new Blob([res.data], { type: mimeType })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = doc.fileName
    a.click()
    window.URL.revokeObjectURL(url)
  },
}
