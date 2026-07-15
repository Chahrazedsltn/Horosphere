import React from 'react'
import { Palette, Check } from '@phosphor-icons/react'
import { Card } from '../../components/ui/Card'
import ProfilPage from '../agent/ProfilPage'
import { useUiStore, type Theme } from '../../store/ui.store'

const THEMES: { id: Theme; label: string; description: string; sidebar: string; accent: string }[] = [
  {
    id: 'slate-blue',
    label: 'Ardoise & Bleu',
    description: 'Corporate moderne — sobre et professionnel',
    sidebar: '#0f172a',
    accent: '#2563eb',
  },
  {
    id: 'navy-emerald',
    label: 'Navy & Émeraude',
    description: 'Frais et fiable — rappelle Stripe ou Monzo',
    sidebar: '#0c1829',
    accent: '#059669',
  },
  {
    id: 'charcoal-orange',
    label: 'Charbon & Orange',
    description: 'Chaleureux et distinct — unique sur le marché RH',
    sidebar: '#1c1917',
    accent: '#ea580c',
  },
]

export default function ParametresPage() {
  const { theme, setTheme } = useUiStore()

  return (
    <div className="space-y-5">
      {/* Theme picker */}
      <Card title="Apparence" icon={<Palette size={14} />}>
        <div>
          <p className="text-[13px] mb-4" style={{ color: 'var(--text3)' }}>
            Choisissez la couleur de l'interface. Le changement est immédiat et sauvegardé automatiquement.
          </p>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            {THEMES.map((t) => {
              const isActive = theme === t.id
              return (
                <button
                  key={t.id}
                  onClick={() => setTheme(t.id)}
                  className="text-left rounded-xl border-2 p-4 transition-all duration-150 hover:-translate-y-0.5"
                  style={{
                    borderColor: isActive ? 'var(--accent)' : 'var(--border)',
                    background: isActive ? 'var(--accent-light)' : 'var(--surface)',
                    boxShadow: isActive ? '0 0 0 1px var(--accent)' : undefined,
                  }}
                >
                  {/* Color swatches */}
                  <div className="flex items-center gap-2 mb-3">
                    <div className="flex gap-1.5">
                      <div
                        className="w-8 h-8 rounded-lg shadow-sm"
                        style={{ background: t.sidebar }}
                        title="Couleur sidebar"
                      />
                      <div
                        className="w-8 h-8 rounded-lg shadow-sm"
                        style={{ background: t.accent }}
                        title="Couleur accent"
                      />
                    </div>
                    {isActive && (
                      <div
                        className="ml-auto w-5 h-5 rounded-full flex items-center justify-center text-white"
                        style={{ background: 'var(--accent)' }}
                      >
                        <Check size={10} weight="bold" />
                      </div>
                    )}
                  </div>
                  <div
                    className="text-[13px] font-semibold leading-tight"
                    style={{ color: isActive ? 'var(--accent)' : 'var(--text)' }}
                  >
                    {t.label}
                  </div>
                  <div className="text-[11px] mt-0.5" style={{ color: 'var(--text3)' }}>
                    {t.description}
                  </div>
                </button>
              )
            })}
          </div>
        </div>
      </Card>

      <ProfilPage />
    </div>
  )
}
