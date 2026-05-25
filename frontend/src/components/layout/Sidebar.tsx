import React from 'react'
import { NavLink, useNavigate } from 'react-router-dom'
import { useAuthStore } from '../../store/auth.store'
import { useUiStore } from '../../store/ui.store'
import { authService } from '../../services/auth.service'
import {
  House, Clock, ClipboardText, FolderOpen,
  SquaresFour, CheckSquare, ChartBar,
  Users, MapPin, Gear, SignOut,
  type Icon,
} from '@phosphor-icons/react'

interface NavItem {
  icon: Icon
  label: string
  to: string
  roles?: string[]
}

const navItems: NavItem[] = [
  { icon: House,         label: 'Accueil',        to: '/dashboard'      },
  { icon: Clock,         label: 'Mon Historique', to: '/historique'     },
  { icon: ClipboardText, label: 'Mes Demandes',   to: '/demandes'       },
  { icon: FolderOpen,    label: 'Mes Documents',  to: '/documents'      },
  // RH
  { icon: SquaresFour,   label: 'Vue RH',         to: '/rh',            roles: ['RH', 'ADMIN'] },
  { icon: CheckSquare,   label: 'Validation',     to: '/rh/validation', roles: ['RH', 'ADMIN'] },
  { icon: ChartBar,      label: 'Rapports',       to: '/rh/rapports',   roles: ['RH', 'ADMIN'] },
  // Admin
  { icon: Users,  label: 'Utilisateurs',  to: '/admin/users', roles: ['ADMIN'] },
  { icon: MapPin, label: 'Sites & Zones', to: '/admin/sites', roles: ['ADMIN'] },
]

export function Sidebar() {
  const { user, logout } = useAuthStore()
  const { sidebarOpen } = useUiStore()
  const navigate = useNavigate()

  const handleLogout = () => {
    authService.logout()
    logout()
    navigate('/login')
  }

  const visibleItems = navItems.filter((item) =>
    !item.roles || item.roles.includes(user?.role ?? '')
  )

  const agentItems = visibleItems.filter((i) => !['RH', 'ADMIN'].some((r) => i.roles?.includes(r)) || !i.roles)
  const rhItems    = visibleItems.filter((i) => i.roles?.includes('RH') && !i.roles.includes('ADMIN'))
  const adminItems = visibleItems.filter((i) => i.roles?.includes('ADMIN'))

  return (
    <aside
      className={`
        fixed top-0 left-0 bottom-0 z-40
        w-[220px] bg-surface border-r border-border
        flex flex-col overflow-y-auto
        transition-transform duration-200
        ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'}
        md:translate-x-0
      `}
    >
      {/* Logo */}
      <div className="px-5 py-5 border-b border-border">
        <div className="text-[18px] font-bold text-accent tracking-tight">Horosphere</div>
        <div className="text-[11px] text-text3 mt-0.5 font-mono">Gestion des présences</div>
      </div>

      {/* Navigation Agent */}
      <nav className="flex-1 py-2">
        <SidebarSection label="Navigation" />
        {agentItems.map((item) => <SidebarItem key={item.to} {...item} />)}

        {(user?.role === 'RH' || user?.role === 'ADMIN') && (
          <>
            <SidebarSection label="Espace RH" />
            {rhItems.map((item) => <SidebarItem key={item.to} {...item} />)}
          </>
        )}

        {user?.role === 'ADMIN' && (
          <>
            <SidebarSection label="Administration" />
            {adminItems.map((item) => <SidebarItem key={item.to} {...item} />)}
          </>
        )}
      </nav>

      {/* Footer */}
      <div className="py-2 border-t border-border">
        <SidebarItem icon={Gear} label="Paramètres" to="/profil" />
        <button
          onClick={handleLogout}
          className="flex items-center gap-2.5 px-3 py-[9px] mx-2 my-0.5 rounded-[7px] text-[13.5px] font-medium text-red hover:bg-red-bg transition-colors"
        >
          <span className="w-[18px] h-[18px] rounded-[5px] bg-surface2 grid place-items-center text-red">
            <SignOut size={11} />
          </span>
          Déconnexion
        </button>

        {/* User */}
        <div
          onClick={() => navigate('/profil')}
          className="flex items-center gap-2.5 px-2.5 py-2 mt-1 mx-2 rounded-lg cursor-pointer hover:bg-surface2 transition-colors"
        >
          <Avatar initials={user?.initiales ?? user?.prenom?.charAt(0) ?? '?'} />
          <div>
            <div className="text-[13px] font-semibold text-text leading-tight">{user?.nomComplet ?? `${user?.prenom} ${user?.nom}`}</div>
            <div className="text-[11px] text-text3">{user?.departement ?? user?.role}</div>
          </div>
        </div>
      </div>
    </aside>
  )
}

function SidebarSection({ label }: { label: string }) {
  return (
    <div className="px-3 pt-4 pb-1 text-[10px] font-semibold uppercase tracking-[1px] text-text3 font-mono">
      {label}
    </div>
  )
}

function SidebarItem({ icon: Icon, label, to }: NavItem) {
  return (
    <NavLink
      to={to}
      className={({ isActive }) =>
        `flex items-center gap-2.5 px-3 py-[9px] mx-2 my-0.5 rounded-[7px] text-[13.5px] font-medium transition-all duration-150 ${
          isActive
            ? 'bg-accent-light text-accent font-semibold'
            : 'text-text2 hover:bg-surface2 hover:text-text'
        }`
      }
    >
      {({ isActive }) => (
        <>
          <span className={`w-[18px] h-[18px] rounded-[5px] grid place-items-center flex-shrink-0 ${
            isActive ? 'bg-accent text-white' : 'bg-surface2 text-text2'
          }`}>
            <Icon size={12} />
          </span>
          {label}
        </>
      )}
    </NavLink>
  )
}

function Avatar({ initials }: { initials: string }) {
  return (
    <div className="w-8 h-8 rounded-full bg-accent-light border-2 border-accent-mid flex items-center justify-center text-[12px] font-bold text-accent flex-shrink-0">
      {initials}
    </div>
  )
}
