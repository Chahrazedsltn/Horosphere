import React from 'react'
import { Outlet, useLocation } from 'react-router-dom'
import { Sidebar } from './Sidebar'
import { Topbar } from './Topbar'
import { useNotifications } from '../../hooks/useNotifications'

const pageTitles: Record<string, string> = {
  '/dashboard':       'Tableau de bord',
  '/historique':      'Mon Historique',
  '/demandes':        'Mes Demandes',
  '/documents':       'Mes Documents',
  '/profil':          'Mon Profil',
  '/alertes':         'Mes Alertes',
  '/rh':              'Vue RH — Dashboard',
  '/rh/validation':   'Validation des demandes',
  '/rh/rapports':     'Rapports & Exports',
  '/admin/users':     'Gestion des utilisateurs',
  '/admin/sites':     'Sites & Géofencing',
  '/admin/params':    'Paramètres système',
}

export function Layout() {
  useNotifications()
  const location = useLocation()
  const title = pageTitles[location.pathname] ?? 'Horosphere'

  return (
    <div className="min-h-screen bg-bg">
      <Sidebar />
      <div className="md:ml-[220px] flex flex-col min-h-screen">
        <Topbar title={title} />
        <main className="flex-1 p-7">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
