import React, { useState } from 'react'
import { User, Lock } from '@phosphor-icons/react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { RoleBadge } from '../../components/ui/Badge'
import { useAuthStore } from '../../store/auth.store'
import api from '../../services/api'

export default function ProfilPage() {
  const { user, updateUser } = useAuthStore()
  const [passwords, setPasswords] = useState({ current: '', new: '', confirm: '' })
  const [saving, setSaving] = useState(false)
  const [msg, setMsg] = useState<{ type: 'success' | 'error'; text: string } | null>(null)

  const handlePasswordChange = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!passwords.current) { setMsg({ type: 'error', text: 'Veuillez saisir votre mot de passe actuel.' }); return }
    if (passwords.new !== passwords.confirm) { setMsg({ type: 'error', text: 'Les mots de passe ne correspondent pas.' }); return }
    if (passwords.new.length < 8) { setMsg({ type: 'error', text: 'Le nouveau mot de passe doit contenir au moins 8 caractères.' }); return }
    setSaving(true)
    try {
      await api.put(`/users/${user?.id}/password`, { current_password: passwords.current, new_password: passwords.new })
      setMsg({ type: 'success', text: 'Mot de passe mis à jour.' })
      setPasswords({ current: '', new: '', confirm: '' })
    } catch (err: any) {
      const message = err?.response?.data?.message ?? 'Erreur lors de la mise à jour.'
      setMsg({ type: 'error', text: message })
    }
    finally { setSaving(false) }
  }

  if (!user) return null

  const initials = `${user.prenom?.charAt(0) ?? ''}${user.nom?.charAt(0) ?? ''}`.toUpperCase()

  return (
    <div className="max-w-2xl space-y-5">
      {/* Infos */}
      <Card title="Informations personnelles" icon={<User size={14} />}>
        <div className="flex items-center gap-5 mb-5">
          <div className="w-[52px] h-[52px] rounded-full bg-accent-light border-2 border-accent-mid flex items-center justify-center text-[18px] font-bold text-accent">
            {initials}
          </div>
          <div>
            <div className="text-[18px] font-bold text-text">{user.prenom} {user.nom}</div>
            <div className="flex items-center gap-2 mt-1">
              <RoleBadge role={user.role} />
              {user.departement && <span className="text-[12px] text-text3">{user.departement}</span>}
            </div>
          </div>
        </div>
        <div className="grid grid-cols-2 gap-4 text-[13px]">
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wide mb-1">Email</div>
            <div className="text-text font-medium">{user.email}</div>
          </div>
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wide mb-1">Rôle</div>
            <div className="text-text font-medium">{user.role}</div>
          </div>
        </div>
      </Card>

      {/* Mot de passe */}
      <Card title="Changer le mot de passe" icon={<Lock size={14} />}>
        {msg && (
          <div className={`px-3 py-2 rounded-lg text-[13px] mb-4 border ${
            msg.type === 'success' ? 'bg-green-bg border-green-border text-green' : 'bg-red-bg border-red-border text-red'
          }`}>
            {msg.text}
          </div>
        )}
        <form onSubmit={handlePasswordChange} className="space-y-0">
          <Input label="Mot de passe actuel" type="password" value={passwords.current} onChange={(e) => setPasswords({ ...passwords, current: e.target.value })} required />
          <Input label="Nouveau mot de passe" type="password" value={passwords.new} onChange={(e) => setPasswords({ ...passwords, new: e.target.value })} required />
          <Input label="Confirmer le mot de passe" type="password" value={passwords.confirm} onChange={(e) => setPasswords({ ...passwords, confirm: e.target.value })} required />
          <Button type="submit" loading={saving}>Mettre à jour</Button>
        </form>
      </Card>
    </div>
  )
}
