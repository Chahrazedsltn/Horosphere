import React, { Suspense, lazy } from 'react'
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { Layout } from './components/layout/Layout'
import { LoadingSpinner } from './components/ui/LoadingSpinner'
import { useAuthStore } from './store/auth.store'

// Pages — lazy loading
const LoginPage            = lazy(() => import('./pages/LoginPage'))
const ForgotPasswordPage   = lazy(() => import('./pages/ForgotPasswordPage'))
const ResetPasswordPage    = lazy(() => import('./pages/ResetPasswordPage'))
const DashboardPage    = lazy(() => import('./pages/agent/DashboardPage'))
const HistoriquePage   = lazy(() => import('./pages/agent/HistoriquePage'))
const DemandesPage     = lazy(() => import('./pages/agent/DemandesPage'))
const DocumentsPage    = lazy(() => import('./pages/agent/DocumentsPage'))
const ProfilPage       = lazy(() => import('./pages/agent/ProfilPage'))
const RhDashboardPage  = lazy(() => import('./pages/rh/RhDashboardPage'))
const ValidationPage   = lazy(() => import('./pages/rh/ValidationPage'))
const RapportsPage     = lazy(() => import('./pages/rh/RapportsPage'))
const UsersPage        = lazy(() => import('./pages/admin/UsersPage'))
const SitesPage        = lazy(() => import('./pages/admin/SitesPage'))
const ParametresPage   = lazy(() => import('./pages/admin/ParametresPage'))
const AlertesPage      = lazy(() => import('./pages/AlertesPage'))

function RequireAuth({ children }: { children: React.ReactNode }) {
  const { isAuthenticated } = useAuthStore()
  return isAuthenticated ? <>{children}</> : <Navigate to="/login" replace />
}

function RequireRole({ roles, children }: { roles: string[]; children: React.ReactNode }) {
  const { user } = useAuthStore()
  if (!user || !roles.includes(user.role)) return <Navigate to="/dashboard" replace />
  return <>{children}</>
}

export default function App() {
  return (
    <BrowserRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
      <Suspense fallback={<LoadingSpinner fullPage text="Chargement..." />}>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/mot-de-passe-oublie" element={<ForgotPasswordPage />} />
          <Route path="/reinitialiser-mot-de-passe" element={<ResetPasswordPage />} />

          <Route element={<RequireAuth><Layout /></RequireAuth>}>
            {/* Agent */}
            <Route index element={<Navigate to="/dashboard" replace />} />
            <Route path="/dashboard" element={<DashboardPage />} />
            <Route path="/historique" element={<HistoriquePage />} />
            <Route path="/demandes" element={<DemandesPage />} />
            <Route path="/documents" element={<DocumentsPage />} />
            <Route path="/profil" element={<ProfilPage />} />
            <Route path="/alertes" element={<AlertesPage />} />

            {/* RH */}
            <Route path="/rh" element={
              <RequireRole roles={['RH', 'ADMIN']}><RhDashboardPage /></RequireRole>
            } />
            <Route path="/rh/validation" element={
              <RequireRole roles={['RH', 'ADMIN']}><ValidationPage /></RequireRole>
            } />
            <Route path="/rh/rapports" element={
              <RequireRole roles={['RH', 'ADMIN']}><RapportsPage /></RequireRole>
            } />

            {/* Admin */}
            <Route path="/admin/users" element={
              <RequireRole roles={['ADMIN']}><UsersPage /></RequireRole>
            } />
            <Route path="/admin/sites" element={
              <RequireRole roles={['ADMIN']}><SitesPage /></RequireRole>
            } />
            <Route path="/admin/params" element={
              <RequireRole roles={['ADMIN']}><ParametresPage /></RequireRole>
            } />
          </Route>

          <Route path="*" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </Suspense>
    </BrowserRouter>
  )
}
