import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export type Theme = 'slate-blue' | 'navy-emerald' | 'charcoal-orange'

interface UiState {
  sidebarOpen: boolean
  alertesNonLues: number
  theme: Theme
  toggleSidebar: () => void
  setSidebarOpen: (open: boolean) => void
  setAlertesNonLues: (count: number) => void
  setTheme: (theme: Theme) => void
}

export const useUiStore = create<UiState>()(
  persist(
    (set) => ({
      sidebarOpen: true,
      alertesNonLues: 0,
      theme: 'slate-blue',

      toggleSidebar: () => set((state) => ({ sidebarOpen: !state.sidebarOpen })),
      setSidebarOpen: (open) => set({ sidebarOpen: open }),
      setAlertesNonLues: (count) => set({ alertesNonLues: count }),
      setTheme: (theme) => {
        document.documentElement.setAttribute('data-theme', theme)
        set({ theme })
      },
    }),
    {
      name: 'horosphere-ui',
      partialize: (state) => ({ theme: state.theme }),
    },
  ),
)
