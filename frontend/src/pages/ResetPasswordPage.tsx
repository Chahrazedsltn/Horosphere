import React, { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { authService } from '../services/auth.service'

export default function ResetPasswordPage() {
  const [searchParams] = useSearchParams()
  const token = searchParams.get('token') ?? ''
  const navigate = useNavigate()

  const [password, setPassword] = useState('')
  const [confirm, setConfirm] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [success, setSuccess] = useState(false)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)

    if (password.length < 12) {
      setError('Le mot de passe doit contenir au moins 12 caractères.')
      return
    }
    if (!/[A-Z]/.test(password) || !/[a-z]/.test(password) || !/[0-9]/.test(password) || !/[\W_]/.test(password)) {
      setError('Le mot de passe doit contenir une majuscule, une minuscule, un chiffre et un caractère spécial.')
      return
    }
    if (password !== confirm) {
      setError('Les mots de passe ne correspondent pas.')
      return
    }

    setLoading(true)
    try {
      await authService.reinitialiserMotDePasse(token, password)
      setSuccess(true)
      setTimeout(() => navigate('/login'), 3000)
    } catch {
      setError('Token invalide ou expiré. Demandez un nouveau lien.')
    } finally {
      setLoading(false)
    }
  }

  if (!token) {
    return (
      <div className="min-h-screen flex items-center justify-center p-10" style={{ background: 'linear-gradient(135deg, #EDEDF8 0%, #E2E2F2 100%)' }}>
        <div className="bg-surface border border-border rounded-2xl shadow-lg w-full max-w-[420px] p-10 text-center">
          <p className="text-red text-[14px]">Lien invalide. Veuillez redemander une réinitialisation.</p>
          <Link to="/mot-de-passe-oublie" className="block mt-4 text-[13px] text-accent hover:underline">
            Demander un nouveau lien
          </Link>
        </div>
      </div>
    )
  }

  return (
    <div
      className="min-h-screen flex items-center justify-center p-10"
      style={{ background: 'linear-gradient(135deg, #EDEDF8 0%, #E2E2F2 100%)' }}
    >
      <div className="bg-surface border border-border rounded-2xl shadow-lg w-full max-w-[420px] p-10">
        <div className="text-center mb-8">
          <div className="w-14 h-14 rounded-[14px] bg-accent-light border-2 border-accent-mid flex items-center justify-center text-[22px] mx-auto mb-3">
            ◎
          </div>
          <div className="text-[28px] font-bold text-accent tracking-tight">Horosphere</div>
        </div>

        {success ? (
          <>
            <div className="px-3 py-3 rounded-lg bg-green-bg border border-green-border text-green text-[13px] text-center">
              Mot de passe réinitialisé avec succès. Redirection en cours...
            </div>
          </>
        ) : (
          <>
            <h1 className="text-[20px] font-bold text-text mb-1.5">Nouveau mot de passe</h1>
            <p className="text-[13px] text-text3 mb-7">
              Choisissez un nouveau mot de passe sécurisé (12 caractères minimum, avec majuscule, minuscule, chiffre et caractère spécial).
            </p>

            {error && (
              <div className="px-3 py-2.5 rounded-lg bg-red-bg border border-red-border text-red text-[13px] mb-5">
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit}>
              <div className="mb-3.5">
                <label className="block text-[12px] font-semibold text-text2 mb-1.5">
                  Nouveau mot de passe
                </label>
                <input
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••••"
                  required
                  minLength={8}
                  className="w-full h-10 bg-surface border-[1.5px] border-border rounded-md px-3.5 text-[13.5px] text-text outline-none focus:border-accent-mid placeholder:text-text3 transition-colors"
                />
              </div>

              <div className="mb-6">
                <label className="block text-[12px] font-semibold text-text2 mb-1.5">
                  Confirmer le mot de passe
                </label>
                <input
                  type="password"
                  value={confirm}
                  onChange={(e) => setConfirm(e.target.value)}
                  placeholder="••••••••••"
                  required
                  className="w-full h-10 bg-surface border-[1.5px] border-border rounded-md px-3.5 text-[13.5px] text-text outline-none focus:border-accent-mid placeholder:text-text3 transition-colors"
                />
              </div>

              <button
                type="submit"
                disabled={loading}
                className="w-full h-[46px] rounded-lg bg-accent text-white font-semibold text-[14px] hover:bg-[#3D6480] transition-colors disabled:opacity-70 flex items-center justify-center gap-2"
              >
                {loading ? (
                  <span className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                ) : null}
                Réinitialiser →
              </button>
            </form>
          </>
        )}

        <div className="mt-6 text-center">
          <Link to="/login" className="text-[13px] text-accent hover:underline">
            ← Retour à la connexion
          </Link>
        </div>
      </div>
    </div>
  )
}
