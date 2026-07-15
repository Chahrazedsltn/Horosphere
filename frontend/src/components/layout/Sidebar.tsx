import React from 'react'
import { NavLink, useNavigate } from 'react-router-dom'
import { useAuthStore } from '../../store/auth.store'
import { useUiStore } from '../../store/ui.store'
import { authService } from '../../services/auth.service'
import {
  House, Clock, ClipboardText, FolderOpen,
  SquaresFour, CheckSquare, ChartBar,
  Users, MapPin, Gear, SignOut, Globe,
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
  { icon: SquaresFour,   label: 'Vue RH',         to: '/rh',            roles: ['RH', 'ADMIN'] },
  { icon: CheckSquare,   label: 'Validation',     to: '/rh/validation', roles: ['RH', 'ADMIN'] },
  { icon: ChartBar,      label: 'Rapports',       to: '/rh/rapports',   roles: ['RH', 'ADMIN'] },
  { icon: Users,         label: 'Utilisateurs',   to: '/admin/users',   roles: ['ADMIN'] },
  { icon: MapPin,        label: 'Sites & Zones',  to: '/admin/sites',   roles: ['ADMIN'] },
]

export function Sidebar() {
  const { user, logout } = useAuthStore()
  const { sidebarOpen, setSidebarOpen } = useUiStore()
  const navigate = useNavigate()

  const handleLogout = () => {
    authService.logout()
    logout()
    navigate('/login')
  }

  const visibleItems = navItems.filter((item) =>
    !item.roles || item.roles.includes(user?.role ?? '')
  )
  const agentItems = visibleItems.filter((i) => !i.roles)
  const rhItems    = visibleItems.filter((i) => i.roles?.includes('RH') && !i.roles.includes('ADMIN'))
  const adminItems = visibleItems.filter((i) => i.roles?.includes('ADMIN'))

  const initials = user?.initiales ?? user?.prenom?.charAt(0) ?? '?'
  const displayName = user?.nomComplet ?? `${user?.prenom ?? ''} ${user?.nom ?? ''}`.trim()

  return (
    <aside
      className={`
        fixed top-0 left-0 bottom-0 z-40 w-[225px]
        flex flex-col overflow-y-auto
        transition-transform duration-200
        ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'}
        md:translate-x-0
      `}
      style={{ background: 'var(--sidebar-bg)' }}
    >
      {/* Logo */}
      <div
        className="flex items-center gap-3 px-5 py-[18px]"
        style={{ borderBottom: '1px solid rgba(255,255,255,0.07)' }}
      >
        <div
          className="w-8 h-8 rounded-lg flex items-center justify-center text-white flex-shrink-0"
          style={{ background: 'var(--sidebar-active-bg)' }}
        >
          <Globe size={16} weight="bold" />
        </div>
        <div>
          <div className="text-[15px] font-bold text-white tracking-tight leading-none">Horosphere</div>
          <div className="text-[10px] font-mono mt-0.5" style={{ color: 'rgba(255,255,255,0.3)' }}>
            Gestion des présences
          </div>
        </div>
      </div>

      {/* Navigation */}
      <nav className="flex-1 py-3 px-2">
        <SidebarSection label="Navigation" />
        {agentItems.map((item) => <SidebarItem key={item.to} {...item} onNavigate={() => setSidebarOpen(false)} />)}

        {(user?.role === 'RH' || user?.role === 'ADMIN') && (
          <>
            <SidebarSection label="Espace RH" />
            {rhItems.map((item) => <SidebarItem key={item.to} {...item} onNavigate={() => setSidebarOpen(false)} />)}
          </>
        )}

        {user?.role === 'ADMIN' && (
          <>
            <SidebarSection label="Administration" />
            {adminItems.map((item) => <SidebarItem key={item.to} {...item} onNavigate={() => setSidebarOpen(false)} />)}
          </>
        )}
      </nav>

      {/* Footer */}
      <div className="px-2 py-3" style={{ borderTop: '1px solid rgba(255,255,255,0.07)' }}>
        <SidebarItem icon={Gear} label="Paramètres" to="/admin/params" onNavigate={() => setSidebarOpen(false)} />

        <button
          onClick={handleLogout}
          className="flex items-center gap-2.5 w-full px-3 py-2 my-0.5 rounded-lg text-[13px] font-medium transition-all"
          style={{ color: 'rgba(255,255,255,0.35)' }}
          onMouseEnter={(e) => {
            e.currentTarget.style.background = 'rgba(239,68,68,0.12)'
            e.currentTarget.style.color = '#f87171'
          }}
          onMouseLeave={(e) => {
            e.currentTarget.style.background = 'transparent'
            e.currentTarget.style.color = 'rgba(255,255,255,0.35)'
          }}
        >
          <span
            className="w-[18px] h-[18px] rounded flex items-center justify-center flex-shrink-0"
            style={{ background: 'rgba(255,255,255,0.07)' }}
          >
            <SignOut size={11} />
          </span>
          Déconnexion
        </button>

        {/* User card */}
        <div
          onClick={() => navigate('/profil')}
          className="flex items-center gap-2.5 px-2.5 py-2 mt-1 rounded-lg cursor-pointer transition-colors"
          onMouseEnter={(e) => (e.currentTarget.style.background = 'rgba(255,255,255,0.06)')}
          onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
        >
          <div
            className="w-8 h-8 rounded-full flex items-center justify-center text-[12px] font-bold text-white flex-shrink-0"
            style={{ background: 'var(--sidebar-active-bg)' }}
          >
            {initials}
          </div>
          <div className="min-w-0">
            <div className="text-[12px] font-semibold text-white truncate leading-tight">{displayName}</div>
            <div className="text-[11px] truncate" style={{ color: 'rgba(255,255,255,0.3)' }}>
              {user?.departement ?? user?.role}
            </div>
          </div>
        </div>
      </div>
    </aside>
  )
}

function SidebarSection({ label }: { label: string }) {
  return (
    <div
      className="px-3 pt-4 pb-1 text-[9px] font-bold uppercase tracking-[1px] font-mono"
      style={{ color: 'rgba(255,255,255,0.2)' }}
    >
      {label}
    </div>
  )
}

function SidebarItem({ icon: Icon, label, to, onNavigate }: NavItem & { onNavigate?: () => void }) {
  return (
    <NavLink to={to} onClick={onNavigate}>
      {({ isActive }) => (
        <div
          className="flex items-center gap-2.5 px-3 py-2 my-0.5 rounded-lg text-[13px] font-medium transition-all duration-150 cursor-pointer"
          style={
            isActive
              ? { background: 'var(--sidebar-active-bg)', color: '#ffffff' }
              : { color: 'rgba(255,255,255,0.45)' }
          }
          onMouseEnter={(e) => {
            if (!isActive) {
              e.currentTarget.style.background = 'rgba(255,255,255,0.07)'
              e.currentTarget.style.color = 'rgba(255,255,255,0.8)'
            }
          }}
          onMouseLeave={(e) => {
            if (!isActive) {
              e.currentTarget.style.background = 'transparent'
              e.currentTarget.style.color = 'rgba(255,255,255,0.45)'
            }
          }}
        >
          <span
            className="w-[18px] h-[18px] rounded flex items-center justify-center flex-shrink-0"
            style={{ background: isActive ? 'rgba(255,255,255,0.2)' : 'rgba(255,255,255,0.08)' }}
          >
            <Icon size={11} />
          </span>
          {label}
        </div>
      )}
    </NavLink>
  )
}
