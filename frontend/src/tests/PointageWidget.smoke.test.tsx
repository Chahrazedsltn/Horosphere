import React from 'react'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { vi, describe, it, expect, beforeEach } from 'vitest'
import { PointageWidget } from '../components/pointage/PointageWidget'

// ── Mocks ──────────────────────────────────────────────────────────────────

vi.mock('../services/pointage.service', () => ({
  pointageService: {
    enCours: vi.fn().mockResolvedValue(null),
    arriver: vi.fn(),
    partir: vi.fn(),
    pause: vi.fn(),
    reprise: vi.fn(),
  },
}))

vi.mock('../services/site.service', () => ({
  siteService: {
    liste: vi.fn().mockResolvedValue([]),
  },
}))

vi.mock('../hooks/useGeolocation', () => ({
  useGeolocation: () => ({
    position: { latitude: 48.8566, longitude: 2.3522, accuracy: 10 },
    loading: false,
    error: null,
    refresh: vi.fn(),
  }),
}))

// ── Tests ──────────────────────────────────────────────────────────────────

describe('PointageWidget — smoke test', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('affiche le bouton "Pointer l\'arrivée" quand aucun pointage en cours', async () => {
    render(<PointageWidget />)

    await waitFor(() => {
      expect(screen.getByText(/Pointer l'arrivée/i)).toBeInTheDocument()
    })
  })

  it('affiche les boutons "Départ" et "Pause" quand un pointage EN_COURS existe', async () => {
    const { pointageService } = await import('../services/pointage.service')
    vi.mocked(pointageService.enCours).mockResolvedValue({
      id: 1,
      dateJour: '2026-05-02',
      heureArrivee: new Date().toISOString(),
      statut: 'EN_COURS',
      estAnomalie: false,
      dureesPauseMinutes: 0,
    })

    render(<PointageWidget />)

    await waitFor(() => {
      expect(screen.getByText(/Pointer le départ/i)).toBeInTheDocument()
      expect(screen.getByText(/Prendre une pause/i)).toBeInTheDocument()
    })
  })

  it('appelle pause() et affiche le bouton "Reprendre" quand en pause', async () => {
    const { pointageService } = await import('../services/pointage.service')

    vi.mocked(pointageService.enCours).mockResolvedValue({
      id: 1,
      dateJour: '2026-05-02',
      heureArrivee: new Date().toISOString(),
      statut: 'EN_COURS',
      estAnomalie: false,
      dureesPauseMinutes: 0,
    })
    vi.mocked(pointageService.pause).mockResolvedValue({
      id: 1,
      dateJour: '2026-05-02',
      heureArrivee: new Date().toISOString(),
      statut: 'EN_PAUSE',
      estAnomalie: false,
      dureesPauseMinutes: 0,
      heurePauseDebut: new Date().toISOString(),
    })

    render(<PointageWidget />)

    const pauseBtn = await screen.findByText(/Prendre une pause/i)
    await userEvent.click(pauseBtn)

    await waitFor(() => {
      expect(pointageService.pause).toHaveBeenCalledTimes(1)
      expect(screen.getByText(/Reprendre le travail/i)).toBeInTheDocument()
    })
  })

  it('n\'affiche pas d\'erreur au chargement initial', async () => {
    render(<PointageWidget />)

    await waitFor(() => {
      expect(screen.queryByRole('alert')).not.toBeInTheDocument()
    })
  })
})
