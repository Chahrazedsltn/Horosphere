import React, { useEffect, useState } from 'react'
import { PlusCircle, Pencil, Trash2 } from 'lucide-react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Modal } from '../../components/ui/Modal'
import { Input, Select } from '../../components/ui/Input'
import { Table } from '../../components/ui/Table'
import { RoleBadge } from '../../components/ui/Badge'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { userService } from '../../services/user.service'
import type { User } from '../../types'
import { format } from 'date-fns'

const EMPTY_FORM = { email: '', password: '', prenom: '', nom: '', role: 'AGENT', departement: '' }

export default function UsersPage() {
  const [users, setUsers] = useState<User[]>([])
  const [loading, setLoading] = useState(true)
  const [modalOpen, setModalOpen] = useState(false)
  const [editing, setEditing] = useState<User | null>(null)
  const [form, setForm] = useState(EMPTY_FORM)
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    userService.liste().then(setUsers).finally(() => setLoading(false))
  }, [])

  const openCreate = () => { setEditing(null); setForm(EMPTY_FORM); setModalOpen(true) }
  const openEdit = (u: User) => {
    setEditing(u)
    setForm({ email: u.email, password: '', prenom: u.prenom, nom: u.nom, role: u.role, departement: u.departement ?? '' })
    setModalOpen(true)
  }

  const handleSave = async () => {
    setSaving(true)
    try {
      if (editing) {
        const updated = await userService.modifier(editing.id, form)
        setUsers((prev) => prev.map((u) => u.id === editing.id ? updated : u))
      } else {
        const created = await userService.creer(form)
        setUsers((prev) => [created, ...prev])
      }
      setModalOpen(false)
    } finally { setSaving(false) }
  }

  const handleDelete = async (u: User) => {
    if (!confirm(`Supprimer ${u.prenom} ${u.nom} ?`)) return
    await userService.supprimer(u.id)
    setUsers((prev) => prev.filter((x) => x.id !== u.id))
  }

  if (loading) return <LoadingSpinner />

  return (
    <div>
      <div className="flex justify-end mb-4">
        <Button icon={<PlusCircle size={16} />} onClick={openCreate}>Nouvel utilisateur</Button>
      </div>

      <Card noPadding>
        <Table
          columns={[
            { key: 'nom', header: 'Nom', render: (u) => (
              <div className="flex items-center gap-2">
                <div className="w-7 h-7 rounded-full bg-accent-light text-accent text-[11px] font-bold flex items-center justify-center">
                  {(u.prenom?.charAt(0) ?? '') + (u.nom?.charAt(0) ?? '')}
                </div>
                <div>
                  <div className="text-[13px] font-medium text-text">{u.prenom} {u.nom}</div>
                  <div className="text-[11px] text-text3">{u.email}</div>
                </div>
              </div>
            )},
            { key: 'role', header: 'Rôle', render: (u) => <RoleBadge role={u.role} /> },
            { key: 'departement', header: 'Département', render: (u) => u.departement ?? '—' },
            { key: 'dateCreation', header: 'Depuis', render: (u) => u.dateCreation ? <span className="font-mono text-[12px]">{format(new Date(u.dateCreation), 'dd/MM/yyyy')}</span> : '—' },
            { key: 'actions', header: '', align: 'right', render: (u) => (
              <div className="flex gap-1.5 justify-end">
                <Button variant="ghost" size="sm" icon={<Pencil size={13} />} onClick={() => openEdit(u)}>Modifier</Button>
                <Button variant="danger" size="sm" icon={<Trash2 size={13} />} onClick={() => handleDelete(u)}>Sup.</Button>
              </div>
            )},
          ]}
          data={users}
          keyExtractor={(u) => u.id}
          emptyMessage="Aucun utilisateur."
        />
      </Card>

      <Modal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editing ? `Modifier ${editing.prenom}` : 'Nouvel utilisateur'}
        footer={
          <>
            <Button variant="ghost" onClick={() => setModalOpen(false)}>Annuler</Button>
            <Button loading={saving} onClick={handleSave}>Enregistrer</Button>
          </>
        }
      >
        <div className="grid grid-cols-2 gap-3">
          <Input label="Prénom" value={form.prenom} onChange={(e) => setForm({ ...form, prenom: e.target.value })} required />
          <Input label="Nom" value={form.nom} onChange={(e) => setForm({ ...form, nom: e.target.value })} required />
        </div>
        <Input label="Email" type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} required />
        <Input label={editing ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe'} type="password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} required={!editing} />
        <div className="grid grid-cols-2 gap-3">
          <Select label="Rôle" value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value })}>
            <option value="AGENT">Agent</option>
            <option value="RH">RH</option>
            <option value="ADMIN">Admin</option>
          </Select>
          <Input label="Département" value={form.departement} onChange={(e) => setForm({ ...form, departement: e.target.value })} />
        </div>
      </Modal>
    </div>
  )
}
