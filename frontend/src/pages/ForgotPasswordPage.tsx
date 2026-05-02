import React, { useState } from 'react'
import { Link } from 'react-router-dom'
import { authService } from '../services/auth.service'

export default function ForgotPasswordPage() {
  const [email, setEmail] = useState('')
  const [loading, setLoading] = useState(false)
  const [sent, setSent] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true); setError(null)
    try {
      await authService.motDePasseOublie(email)
      setSent(true)
    } catch {
      setError('Une erreur est survenue. Veuillez réessayer.')
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
        <div className="text-center mb-8">
          <div className="w-14 h-14 rounded-[14px] bg-accent-light border-2 border-accent-mid flex items-center justify-center text-[22px] mx-auto mb-3">
            ◎
          </div>
          <div className="text-[28px] font-bold text-accent tracking-tight">Horosphere</div>
        </div>

        {sent ? (
          <>
            <div className="px-3 py-3 rounded-lg bg-green-bg border border-green-border text-green text-[13px] mb-5 text-center">
              Si ce compte existe, un email de réinitialisation a été envoyé.
            </div>
            <p className="text-[13px] text-text3 text-center">
              Vérifiez votre boîte de réception et cliquez sur le lien reçu.
            </p>
          </>
        ) : (
          <>
            <h1 className="text-[20px] font-bold text-text mb-1.5">Mot de passe oublié</h1>
            <p className="text-[13px] text-text3 mb-7">
              Entrez votre adresse e-mail pour recevoir un lien de réinitialisation.
            </p>

            {error && (
              <div className="px-3 py-2.5 rounded-lg bg-red-bg border border-red-border text-red text-[13px] mb-5">
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit}>
              <div className="mb-5">
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

              <button
                type="submit"
                disabled={loading}
                className="w-full h-[46px] rounded-lg bg-accent text-white font-semibold text-[14px] hover:bg-[#2E2EAA] transition-colors disabled:opacity-70 flex items-center justify-center gap-2"
              >
                {loading ? (
                  <span className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                ) : null}
                Envoyer le lien →
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
