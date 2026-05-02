import React, { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { authService } from '../services/auth.service'
import { useAuthStore } from '../store/auth.store'

export default function LoginPage() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [remember, setRemember] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const { setAuth } = useAuthStore()
  const navigate = useNavigate()

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setError(null)

    try {
      const res = await authService.login(email, password)
      localStorage.setItem('token', res.token)
      const user = await authService.me()
      setAuth(user, res.token)

      // Redirection selon le rôle
      if (user.role === 'ADMIN' || user.role === 'RH') {
        navigate('/rh')
      } else {
        navigate('/dashboard')
      }
    } catch {
      setError('Email ou mot de passe incorrect.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div
      className="min-h-screen flex items-center justify-center p-10"
      style={{ background: 'linear-gradient(135deg, #EDEDF8 0%, #E2E2F2 100%)' }}
    >
      <div className="bg-surface border border-border rounded-2xl shadow-lg w-full max-w-[420px] p-10">
        {/* Logo */}
        <div className="text-center mb-8">
          <div className="w-14 h-14 rounded-[14px] bg-accent-light border-2 border-accent-mid flex items-center justify-center text-[22px] mx-auto mb-3">
            ◎
          </div>
          <div className="text-[28px] font-bold text-accent tracking-tight">Horosphere</div>
        </div>

        <h1 className="text-[20px] font-bold text-text mb-1.5">Connexion</h1>
        <p className="text-[13px] text-text3 mb-7">
          Accédez à votre espace de gestion des présences.
        </p>

        {error && (
          <div className="px-3 py-2.5 rounded-lg bg-red-bg border border-red-border text-red text-[13px] mb-5">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit}>
          <div className="mb-3.5">
            <label className="block text-[12px] font-semibold text-text2 mb-1.5">
              Adresse e-mail
            </label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="utilisateur@entreprise.fr"
              required
              className="w-full h-10 bg-surface border-[1.5px] border-border rounded-md px-3.5 text-[13.5px] text-text outline-none focus:border-accent-mid placeholder:text-text3 transition-colors"
            />
          </div>

          <div className="mb-2.5">
            <label className="block text-[12px] font-semibold text-text2 mb-1.5">
              Mot de passe
            </label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••••"
              required
              className="w-full h-10 bg-surface border-[1.5px] border-border rounded-md px-3.5 text-[13.5px] text-text outline-none focus:border-accent-mid placeholder:text-text3 transition-colors"
            />
          </div>

          <a href="/mot-de-passe-oublie" className="block text-right text-[12px] text-accent hover:underline mb-5">
            Mot de passe oublié ?
          </a>

          <label className="flex items-center gap-2 mb-5 cursor-pointer">
            <div
              onClick={() => setRemember(!remember)}
              className={`w-4 h-4 rounded border-[1.5px] flex items-center justify-center transition-colors ${
                remember ? 'bg-accent border-accent' : 'bg-surface2 border-border2'
              }`}
            >
              {remember && <span className="text-white text-[9px]">✓</span>}
            </div>
            <span className="text-[13px] text-text2">Rester connecté</span>
          </label>

          <button
            type="submit"
            disabled={loading}
            className="w-full h-[46px] rounded-lg bg-accent text-white font-semibold text-[14px] hover:bg-[#2E2EAA] transition-colors disabled:opacity-70 flex items-center justify-center gap-2"
          >
            {loading ? (
              <span className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            ) : null}
            Se connecter →
          </button>
        </form>
      </div>
    </div>
  )
}
