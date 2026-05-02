import { create } from 'zustand'

interface UiState {
  sidebarOpen: boolean
  alertesNonLues: number
  toggleSidebar: () => void
  setSidebarOpen: (open: boolean) => void
  setAlertesNonLues: (count: number) => void
}

export const useUiStore = create<UiState>((set) => ({
  sidebarOpen: true,
  alertesNonLues: 0,

  toggleSidebar: () => set((state) => ({ sidebarOpen: !state.sidebarOpen })),
  setSidebarOpen: (open) => set({ sidebarOpen: open }),
  setAlertesNonLues: (count) => set({ alertesNonLues: count }),
}))
