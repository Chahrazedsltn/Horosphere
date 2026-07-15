import React, { useEffect, useState } from 'react'
import { DownloadSimple, FilePdf, FileXls } from '@phosphor-icons/react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { documentService } from '../../services/document.service'
import type { Document } from '../../types'
import { format } from 'date-fns'

export default function DocumentsPage() {
  const [docs, setDocs] = useState<Document[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    documentService.mesDocs()
      .then(setDocs)
      .finally(() => setLoading(false))
  }, [])

  if (loading) return <LoadingSpinner />

  return (
    <div className="space-y-4">
      {docs.length === 0 ? (
        <div className="text-center py-20 text-text3">Aucun document disponible.</div>
      ) : (
        docs.map((doc) => (
          <Card key={doc.id}>
            <div className="flex items-center gap-3">
              <div className={`w-10 h-10 rounded-lg flex items-center justify-center ${
                doc.typeDocument === 'PDF' ? 'bg-red-bg text-red' : 'bg-green-bg text-green'
              }`}>
                {doc.typeDocument === 'PDF' ? <FilePdf size={20} /> : <FileXls size={20} />}
              </div>
              <div className="flex-1">
                <div className="text-[13px] font-semibold text-text">{doc.fileName}</div>
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
          </Card>
        ))
      )}
    </div>
  )
}
