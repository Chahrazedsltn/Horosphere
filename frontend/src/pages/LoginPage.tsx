import React, { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { authService } from '../services/auth.service'
import { useAuthStore } from '../store/auth.store'
import logo from '../assets/logo.png'
import loginBg from '../assets/login.jpg'

export default function LoginPage() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [remember, setRemember] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const { setAuth } = useAuthStore()
  const navigate = useNavigate()

  const slides = [
    {
      title: 'Gérez vos présences,\nsimplement.',
      desc: 'Pointage, absences et équipes\ndans une interface unifiée.',
    },
    {
      title: 'Géofencing GPS\nen temps réel.',
      desc: 'Validez automatiquement les pointages\nselon la localisation de vos agents.',
    },
    {
      title: 'Rapports et exports\nen un clic.',
      desc: 'Générez vos bilans RH en CSV ou PDF\net suivez les indicateurs clés.',
    },
  ]
  const [slide, setSlide] = useState(0)
  const [fading, setFading] = useState(false)

  useEffect(() => {
    const timer = setInterval(() => {
      setFading(true)
      setTimeout(() => {
        setSlide(s => (s + 1) % slides.length)
        setFading(false)
      }, 300)
    }, 4000)
    return () => clearInterval(timer)
  }, [])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setError(null)

    try {
      const res = await authService.login(email, password)
      const user = await authService.me()
      setAuth(user, res.token, remember)

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
    <div className="min-h-screen flex">

      {/* ── Panneau gauche ── */}
      <div className="hidden md:flex md:w-[42%] lg:w-[45%] flex-col justify-between p-10 relative overflow-hidden"
        style={{ background: 'linear-gradient(160deg, #2C3038 0%, #1a2530 100%)' }}>

        {/* Image de fond avec transparence */}
        <div className="absolute inset-0 z-0"
          style={{
            backgroundImage: `url(${loginBg})`,
            backgroundSize: 'cover',
            backgroundPosition: 'center top',
            opacity: 0.32,
          }} />

        {/* Overlay gradient pour lisibilité */}
        <div className="absolute inset-0 z-0"
          style={{ background: 'linear-gradient(160deg, rgba(44,48,56,0.6) 0%, rgba(26,37,48,0.85) 100%)' }} />

        {/* Halos décoratifs */}
        <div className="absolute -top-20 -right-20 w-80 h-80 rounded-full z-0"
          style={{ background: 'radial-gradient(circle, rgba(85,122,149,0.35) 0%, transparent 65%)' }} />
        <div className="absolute -bottom-16 -left-16 w-64 h-64 rounded-full z-0"
          style={{ background: 'radial-gradient(circle, rgba(115,149,174,0.2) 0%, transparent 65%)' }} />

        {/* Logo */}
        <div className="flex items-center gap-3 z-10">
          <img src={logo} alt="Horosphere" className="w-9 h-9 object-contain" />
          <span className="text-white font-bold text-[16px] tracking-tight">Horosphere</span>
        </div>

        {/* Slides */}
        <div className="z-10">
          <div style={{ opacity: fading ? 0 : 1, transition: 'opacity 0.3s ease' }}>
            <h2 className="text-white font-bold text-[26px] leading-snug mb-3" style={{ whiteSpace: 'pre-line' }}>
              {slides[slide].title}
            </h2>
            <p className="text-[13px] leading-relaxed" style={{ color: 'rgba(255,255,255,0.42)', whiteSpace: 'pre-line' }}>
              {slides[slide].desc}
            </p>
          </div>
        </div>

        {/* Dots indicateurs */}
        <div className="flex items-center gap-1.5 z-10">
          {slides.map((_, i) => (
            <button
              key={i}
              onClick={() => { setFading(true); setTimeout(() => { setSlide(i); setFading(false) }, 300) }}
              className="rounded-full transition-all duration-300"
              style={{
                width: i === slide ? '18px' : '6px',
                height: '6px',
                background: i === slide ? 'rgba(255,255,255,0.85)' : 'rgba(255,255,255,0.2)',
              }}
            />
          ))}
        </div>
      </div>

      {/* ── Panneau droit ── */}
      <div className="flex-1 flex items-center justify-center bg-surface px-8">
        <div className="w-full max-w-[360px]">

          {/* Logo mobile */}
          <div className="flex items-center gap-2 mb-8 md:hidden">
            <img src={logo} alt="Horosphere" className="w-7 h-7 object-contain" />
            <span className="font-bold text-text text-[15px]">Horosphere</span>
          </div>

          <h1 className="text-[24px] font-bold text-text tracking-tight mb-1">Connexion</h1>
          <p className="text-[13px] text-text3 mb-8">Accédez à votre espace de travail</p>

          {error && (
            <div className="px-3.5 py-2.5 rounded-lg bg-red-bg border border-red-border text-red text-[13px] mb-5">
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="flex flex-col gap-4">
            <div>
              <label className="block text-[11px] font-semibold text-text2 uppercase tracking-[0.5px] mb-1.5">
                Adresse e-mail
              </label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="vous@entreprise.fr"
                required
                className="w-full h-[42px] bg-bg border-[1.5px] border-border rounded-lg px-3.5 text-[13.5px] text-text outline-none focus:border-accent-mid placeholder:text-text3 transition-colors"
              />
            </div>

            <div>
              <label className="block text-[11px] font-semibold text-text2 uppercase tracking-[0.5px] mb-1.5">
                Mot de passe
              </label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••••"
                required
                className="w-full h-[42px] bg-bg border-[1.5px] border-border rounded-lg px-3.5 text-[13.5px] text-text outline-none focus:border-accent-mid placeholder:text-text3 transition-colors"
              />
              <div className="text-right mt-1.5">
                <a href="/mot-de-passe-oublie" className="text-[12px] text-accent hover:underline">
                  Mot de passe oublié ?
                </a>
              </div>
            </div>

            <label className="flex items-center gap-2.5 cursor-pointer -mt-1">
              <div
                onClick={() => setRemember(!remember)}
                className={`w-4 h-4 rounded border-[1.5px] flex items-center justify-center transition-colors flex-shrink-0 ${
                  remember ? 'bg-accent border-accent' : 'bg-surface border-border2'
                }`}
              >
                {remember && <span className="text-white text-[9px]">✓</span>}
              </div>
              <span className="text-[13px] text-text2">Rester connecté</span>
            </label>

            <button
              type="submit"
              disabled={loading}
              className="w-full h-[46px] rounded-lg bg-accent text-white font-semibold text-[14px] hover:bg-[#3D6480] transition-colors disabled:opacity-70 flex items-center justify-center gap-2 mt-1"
            >
              {loading
                ? <span className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                : 'Se connecter →'
              }
            </button>
          </form>

        </div>
      </div>

    </div>
  )
}
