import React, { useEffect, useRef, useState } from 'react'
import { PlusCircle, Pencil, Trash2, MapPin } from 'lucide-react'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { siteService } from '../../services/site.service'
import type { Site } from '../../types'
import { Loader } from '@googlemaps/js-api-loader'

const EMPTY_FORM = { nom: '', adresse: '', latitude: '', longitude: '', rayonMetres: '200', geofencingActif: true }

export default function SitesPage() {
  const [sites, setSites] = useState<Site[]>([])
  const [loading, setLoading] = useState(true)
  const [modalOpen, setModalOpen] = useState(false)
  const [editing, setEditing] = useState<Site | null>(null)
  const [form, setForm] = useState(EMPTY_FORM)
  const [saving, setSaving] = useState(false)
  const mapRef = useRef<HTMLDivElement>(null)
  const googleMapRef = useRef<google.maps.Map | null>(null)
  const circlesRef = useRef<google.maps.Circle[]>([])

  useEffect(() => {
    siteService.liste().then(setSites).finally(() => setLoading(false))
  }, [])

  useEffect(() => {
    const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY
    if (!apiKey || !mapRef.current || sites.length === 0) return

    const loader = new Loader({ apiKey, version: 'weekly', libraries: ['maps'] })
    loader.load().then(() => {
      if (!mapRef.current) return
      const center = { lat: sites[0].latitude, lng: sites[0].longitude }
      const map = new google.maps.Map(mapRef.current, {
        center, zoom: 14,
        styles: [{ featureType: 'poi', stylers: [{ visibility: 'off' }] }],
      })
      googleMapRef.current = map

      circlesRef.current.forEach((c) => c.setMap(null))
      circlesRef.current = []

      sites.forEach((site) => {
        new google.maps.Marker({
          position: { lat: site.latitude, lng: site.longitude },
          map,
          title: site.nom,
          label: { text: site.nom, color: '#3B3BCC', fontWeight: '600', fontSize: '11px' },
        })
        const circle = new google.maps.Circle({
          map,
          center: { lat: site.latitude, lng: site.longitude },
          radius: site.rayonMetres,
          strokeColor: site.geofencingActif ? '#3B3BCC' : '#9090B4',
          strokeOpacity: 0.8,
          strokeWeight: 2,
          fillColor: site.geofencingActif ? '#EBEBFF' : '#EEEEF2',
          fillOpacity: 0.35,
        })
        circlesRef.current.push(circle)
      })
    }).catch(console.error)
  }, [sites])

  const openCreate = () => { setEditing(null); setForm(EMPTY_FORM); setModalOpen(true) }
  const openEdit = (s: Site) => {
    setEditing(s)
    setForm({
      nom: s.nom, adresse: s.adresse,
      latitude: String(s.latitude), longitude: String(s.longitude),
      rayonMetres: String(s.rayonMetres), geofencingActif: s.geofencingActif,
    })
    setModalOpen(true)
  }

  const handleSave = async () => {
    setSaving(true)
    const data = {
      nom: form.nom, adresse: form.adresse,
      latitude: parseFloat(form.latitude), longitude: parseFloat(form.longitude),
      rayonMetres: parseInt(form.rayonMetres), geofencingActif: form.geofencingActif,
    }
    try {
      if (editing) {
        const updated = await siteService.modifier(editing.id, data)
        setSites((prev) => prev.map((s) => s.id === editing.id ? updated : s))
      } else {
        const created = await siteService.creer(data as Omit<Site, 'id'>)
        setSites((prev) => [...prev, created])
      }
      setModalOpen(false)
    } finally { setSaving(false) }
  }

  const handleDelete = async (s: Site) => {
    if (!confirm(`Supprimer le site "${s.nom}" ?`)) return
    await siteService.supprimer(s.id)
    setSites((prev) => prev.filter((x) => x.id !== s.id))
  }

  if (loading) return <LoadingSpinner />

  return (
    <div className="space-y-5">
      <div className="flex justify-end">
        <Button icon={<PlusCircle size={16} />} onClick={openCreate}>Nouveau site</Button>
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-2 gap-5">
        {/* Carte */}
        <Card title="Carte des sites" icon="🗺">
          {import.meta.env.VITE_GOOGLE_MAPS_API_KEY ? (
            <div ref={mapRef} className="w-full h-[350px] rounded-md" />
          ) : (
            <div className="w-full h-[350px] rounded-md bg-surface2 border-2 border-dashed border-border2 flex flex-col items-center justify-center text-text3 gap-2">
              <MapPin size={32} className="opacity-30" />
              <div className="text-[11px] font-mono font-bold uppercase">GOOGLE MAPS</div>
              <div className="text-[11px]">Configurez VITE_GOOGLE_MAPS_API_KEY</div>
            </div>
          )}
        </Card>

        {/* Liste */}
        <div className="space-y-3">
          {sites.map((s) => (
            <div key={s.id} className={`bg-surface border rounded-lg p-4 shadow ${s.geofencingActif ? 'border-border' : 'border-border opacity-75'}`}>
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 rounded-lg bg-accent-light text-accent flex items-center justify-center flex-shrink-0">
                  <MapPin size={16} />
                </div>
                <div className="flex-1">
                  <div className="flex items-center gap-2">
                    <span className="text-[13px] font-semibold text-text">{s.nom}</span>
                    <span className={`text-[10px] px-1.5 py-0.5 rounded font-mono font-bold ${
                      s.geofencingActif ? 'bg-green-bg text-green' : 'bg-surface2 text-text3'
                    }`}>
                      {s.geofencingActif ? '◉ Actif' : '○ Inactif'}
                    </span>
                  </div>
                  <div className="text-[12px] text-text3 mt-0.5">{s.adresse}</div>
                  <div className="text-[11px] font-mono text-text3 mt-1">
                    {s.latitude.toFixed(6)}, {s.longitude.toFixed(6)} — rayon {s.rayonMetres}m
                  </div>
                </div>
                <div className="flex gap-1.5">
                  <Button variant="ghost" size="sm" icon={<Pencil size={13} />} onClick={() => openEdit(s)} />
                  <Button variant="danger" size="sm" icon={<Trash2 size={13} />} onClick={() => handleDelete(s)} />
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      <Modal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editing ? `Modifier ${editing.nom}` : 'Nouveau site'}
        footer={
          <>
            <Button variant="ghost" onClick={() => setModalOpen(false)}>Annuler</Button>
            <Button loading={saving} onClick={handleSave}>Enregistrer</Button>
          </>
        }
      >
        <Input label="Nom du site" value={form.nom} onChange={(e) => setForm({ ...form, nom: e.target.value })} required />
        <Input label="Adresse" value={form.adresse} onChange={(e) => setForm({ ...form, adresse: e.target.value })} required />
        <div className="grid grid-cols-2 gap-3">
          <Input label="Latitude" type="number" step="any" value={form.latitude} onChange={(e) => setForm({ ...form, latitude: e.target.value })} required />
          <Input label="Longitude" type="number" step="any" value={form.longitude} onChange={(e) => setForm({ ...form, longitude: e.target.value })} required />
        </div>
        <Input label="Rayon géofencing (mètres)" type="number" value={form.rayonMetres} onChange={(e) => setForm({ ...form, rayonMetres: e.target.value })} />
        <label className="flex items-center gap-2.5 cursor-pointer mt-1">
          <div
            onClick={() => setForm({ ...form, geofencingActif: !form.geofencingActif })}
            className={`w-9 h-[22px] rounded-full relative cursor-pointer transition-colors ${form.geofencingActif ? 'bg-accent' : 'bg-border2'}`}
          >
            <div className={`absolute top-[3px] w-4 h-4 rounded-full bg-white transition-all ${form.geofencingActif ? 'left-[19px]' : 'left-[3px]'}`} />
          </div>
          <span className="text-[13px] text-text2">Géofencing actif</span>
        </label>
      </Modal>
    </div>
  )
}
