import React, { useEffect, useState } from 'react'
import { FilePdf, FileXls, DownloadSimple, ChartBar, Folder } from '@phosphor-icons/react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input, Select } from '../../components/ui/Input'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { documentService } from '../../services/document.service'
import { userService } from '../../services/user.service'
import type { Document, User } from '../../types'
import { format } from 'date-fns'

export default function RapportsPage() {
  const [users, setUsers] = useState<User[]>([])
  const [docs, setDocs] = useState<Document[]>([])
  const [loading, setLoading] = useState(false)
  const [form, setForm] = useState({
    date_debut: format(new Date(new Date().getFullYear(), new Date().getMonth(), 1), 'yyyy-MM-dd'),
    date_fin: format(new Date(), 'yyyy-MM-dd'),
    utilisateur_id: '',
  })

  useEffect(() => {
    userService.liste().then(setUsers).catch(console.error)
    documentService.mesDocs().then(setDocs).catch(console.error)
  }, [])

  const handleExport = async (type: 'csv' | 'pdf') => {
    setLoading(true)
    try {
      const params = {
        date_debut: form.date_debut,
        date_fin: form.date_fin,
        ...(form.utilisateur_id ? { utilisateur_id: parseInt(form.utilisateur_id) } : {}),
      }
      const doc = type === 'csv'
        ? await documentService.exportCsv(params)
        : await documentService.exportPdf(params)
      setDocs((prev) => [doc, ...prev])
    } catch (e) {
      console.error(e)
    } finally { setLoading(false) }
  }

  return (
    <div className="space-y-5">
      {/* Formulaire */}
      <Card title="Générer un rapport" icon={<ChartBar size={14} />}>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <Input
            label="Date début"
            type="date"
            value={form.date_debut}
            onChange={(e) => setForm({ ...form, date_debut: e.target.value })}
          />
          <Input
            label="Date fin"
            type="date"
            value={form.date_fin}
            onChange={(e) => setForm({ ...form, date_fin: e.target.value })}
          />
          <Select
            label="Employé (tous si vide)"
            value={form.utilisateur_id}
            onChange={(e) => setForm({ ...form, utilisateur_id: e.target.value })}
          >
            <option value="">Tous les employés</option>
            {users.map((u) => (
              <option key={u.id} value={u.id}>{u.prenom} {u.nom}</option>
            ))}
          </Select>
        </div>
        <div className="flex gap-3">
          <Button
            variant="success"
            icon={<FileXls size={16} />}
            loading={loading}
            onClick={() => handleExport('csv')}
          >
            Exporter CSV
          </Button>
          <Button
            variant="danger"
            icon={<FilePdf size={16} />}
            loading={loading}
            onClick={() => handleExport('pdf')}
          >
            Exporter PDF
          </Button>
        </div>
      </Card>

      {/* Documents générés */}
      <Card title="Documents générés" icon={<Folder size={14} />}>
        {docs.length === 0 ? (
          <div className="text-center py-8 text-text3 text-[13px]">Aucun document généré.</div>
        ) : (
          <div className="space-y-2">
            {docs.map((doc) => (
              <div key={doc.id} className="flex items-center gap-3 p-3 rounded-lg bg-surface2">
                <div className={`w-8 h-8 rounded flex items-center justify-center flex-shrink-0 ${
                  doc.typeDocument === 'PDF' ? 'bg-red-bg text-red' : 'bg-green-bg text-green'
                }`}>
                  {doc.typeDocument === 'PDF' ? <FilePdf size={16} /> : <FileXls size={16} />}
                </div>
                <div className="flex-1 min-w-0">
                  <div className="text-[13px] font-medium text-text truncate">{doc.fileName}</div>
                  <div className="text-[11px] text-text3 font-mono">{format(new Date(doc.dateCreation), 'dd/MM/yyyy à HH:mm')}</div>
                </div>
                <Button
                  variant="ghost"
                  size="sm"
                  icon={<DownloadSimple size={14} />}
                  onClick={() => documentService.download(doc)}
                >
                  Télécharger
                </Button>
              </div>
            ))}
          </div>
        )}
      </Card>
    </div>
  )
}
