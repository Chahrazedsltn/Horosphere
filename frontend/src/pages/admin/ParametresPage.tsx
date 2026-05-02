import React from 'react'
import { Card } from '../../components/ui/Card'
import ProfilPage from '../agent/ProfilPage'

export default function ParametresPage() {
  return (
    <div className="space-y-5">
      <ProfilPage />
      <Card title="Informations système" icon="⚙">
        <div className="grid grid-cols-2 gap-4 text-[13px]">
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wide mb-1">Version</div>
            <div className="font-mono text-text">Horosphere v1.0.0</div>
          </div>
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wide mb-1">Stack</div>
            <div className="font-mono text-text">Symfony 7 + React 18</div>
          </div>
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wide mb-1">Base de données</div>
            <div className="font-mono text-text">MariaDB 10.11</div>
          </div>
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wide mb-1">Environnement</div>
            <div className={`font-mono ${import.meta.env.VITE_APP_ENV === 'prod' ? 'text-green' : 'text-amber'}`}>
              {import.meta.env.VITE_APP_ENV ?? import.meta.env.MODE ?? 'development'}
            </div>
          </div>
        </div>
      </Card>
    </div>
  )
}
