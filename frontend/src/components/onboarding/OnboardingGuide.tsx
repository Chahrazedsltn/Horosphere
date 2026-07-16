import React, { useState } from 'react'
import { Rocket, MapPin, ClipboardText, CalendarBlank, ArrowLeft, ArrowRight, X } from '@phosphor-icons/react'

const STORAGE_KEY = 'horosphere-onboarding-done'

interface Step {
  icon: React.ReactNode
  title: string
  description: string
}

const steps: Step[] = [
  {
    icon: <Rocket size={40} weight="duotone" />,
    title: 'Bienvenue sur Horosphere !',
    description:
      'Horosphere est votre outil de gestion du temps et des presences. ' +
      'Depuis votre tableau de bord, vous pouvez pointer vos heures, suivre vos compteurs de conges, ' +
      'et consulter votre calendrier mensuel en un coup d\'oeil.',
  },
  {
    icon: <MapPin size={40} weight="duotone" />,
    title: 'Pointage',
    description:
      'Pointez votre arrivee, vos pauses et votre depart grace aux 3 boutons dedies. ' +
      'Le pointage utilise la geolocalisation GPS pour verifier que vous etes bien sur site. ' +
      'Vos heures sont calculees automatiquement.',
  },
  {
    icon: <ClipboardText size={40} weight="duotone" />,
    title: 'Demandes',
    description:
      'Soumettez vos demandes de conges, d\'absence ou de correction directement depuis l\'application. ' +
      'Vous pouvez joindre un justificatif (certificat medical, etc.) et suivre le statut de chaque demande en temps reel.',
  },
  {
    icon: <CalendarBlank size={40} weight="duotone" />,
    title: 'Votre calendrier',
    description:
      'Le calendrier mensuel affiche vos journees avec un code couleur : ' +
      'vert pour present, bleu pour conge, rouge pour absence, orange pour anomalie, et gris pour jour ferie. ' +
      'Survolez un jour pour voir le detail.',
  },
]

export function OnboardingGuide() {
  const [visible, setVisible] = useState(() => {
    return localStorage.getItem(STORAGE_KEY) !== 'true'
  })
  const [current, setCurrent] = useState(0)

  if (!visible) return null

  const step = steps[current]
  const isLast = current === steps.length - 1

  function close() {
    localStorage.setItem(STORAGE_KEY, 'true')
    setVisible(false)
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
      onClick={(e) => { if (e.target === e.currentTarget) close() }}
    >
      <div className="bg-surface border border-border rounded-xl shadow-lg w-full max-w-2xl animate-[fadeIn_0.15s_ease]">
        {/* Header */}
        <div className="flex items-center justify-between px-5 py-4 border-b border-border">
          <h2 className="text-[15px] font-semibold text-text">Guide de demarrage</h2>
          <button
            onClick={close}
            className="w-7 h-7 flex items-center justify-center rounded-md text-text3 hover:bg-surface2 hover:text-text2 transition-colors"
          >
            <X size={16} />
          </button>
        </div>

        {/* Body */}
        <div className="p-8 text-center">
          <div
            className="w-16 h-16 mx-auto mb-5 rounded-2xl flex items-center justify-center"
            style={{ background: 'var(--accent-light)', color: 'var(--accent)' }}
          >
            {step.icon}
          </div>
          <h3 className="text-[20px] font-bold mb-3" style={{ color: 'var(--text)' }}>
            {step.title}
          </h3>
          <p className="text-[14px] leading-relaxed max-w-md mx-auto" style={{ color: 'var(--text2)' }}>
            {step.description}
          </p>

          {/* Step indicators */}
          <div className="flex items-center justify-center gap-2 mt-6">
            {steps.map((_, i) => (
              <button
                key={i}
                onClick={() => setCurrent(i)}
                className="w-2.5 h-2.5 rounded-full transition-all duration-200"
                style={{
                  background: i === current ? 'var(--accent)' : 'var(--border)',
                  transform: i === current ? 'scale(1.3)' : 'scale(1)',
                }}
              />
            ))}
          </div>
        </div>

        {/* Footer */}
        <div className="flex items-center justify-between px-5 py-4 border-t border-border bg-surface2 rounded-b-xl">
          <button
            onClick={close}
            className="text-[13px] font-medium px-3 py-1.5 rounded-lg transition-colors"
            style={{ color: 'var(--text3)' }}
          >
            Passer
          </button>
          <div className="flex items-center gap-2">
            {current > 0 && (
              <button
                onClick={() => setCurrent(current - 1)}
                className="flex items-center gap-1.5 text-[13px] font-semibold px-4 py-2 rounded-lg border transition-colors"
                style={{ borderColor: 'var(--border)', color: 'var(--text2)' }}
              >
                <ArrowLeft size={14} /> Precedent
              </button>
            )}
            {isLast ? (
              <button
                onClick={close}
                className="flex items-center gap-1.5 text-[13px] font-semibold px-5 py-2 rounded-lg transition-colors text-white"
                style={{ background: 'var(--accent)' }}
              >
                <Rocket size={14} weight="fill" /> C&apos;est parti !
              </button>
            ) : (
              <button
                onClick={() => setCurrent(current + 1)}
                className="flex items-center gap-1.5 text-[13px] font-semibold px-4 py-2 rounded-lg transition-colors text-white"
                style={{ background: 'var(--accent)' }}
              >
                Suivant <ArrowRight size={14} />
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
