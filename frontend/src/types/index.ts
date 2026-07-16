export interface User {
  id: number
  email: string
  prenom: string
  nom: string
  nomComplet?: string
  initiales?: string
  role: 'AGENT' | 'RH' | 'ADMIN'
  departement?: string
  dateCreation?: string
}

export interface Site {
  id: number
  nom: string
  adresse: string
  latitude: number
  longitude: number
  rayonMetres: number
  geofencingActif: boolean
}

export interface Pointage {
  id: number
  dateJour: string
  heureArrivee: string
  heureDepart?: string
  statut: 'EN_COURS' | 'EN_PAUSE' | 'VALIDE' | 'HORS_ZONE' | 'ANOMALIE'
  coordonneesGps?: string
  estAnomalie: boolean
  dureeMinutes?: number
  dureesPauseMinutes: number
  heurePauseDebut?: string
  site?: Pick<Site, 'id' | 'nom'>
  utilisateur?: Pick<User, 'id' | 'prenom' | 'nom'>
}

export interface Demande {
  id: number
  typeDemande: 'CONGE' | 'CORRECTION' | 'ABSENCE' | 'AUTRE'
  statut: 'EN_ATTENTE' | 'APPROUVEE' | 'REJETEE'
  dateDebut: string
  dateFin: string
  dureeJours?: number
  motif?: string
  justificatif?: string
  justificatifUrl?: string
  dateCreation: string
  utilisateur?: Pick<User, 'id' | 'prenom' | 'nom' | 'email'>
}

export interface Alerte {
  id: number
  typeAlerte: 'OUBLI_DEPART' | 'HORS_ZONE' | 'ECART_HORAIRE'
  message: string
  dateCreation: string
  estLue: boolean
  recente?: boolean
  pointage?: Pick<Pointage, 'id' | 'dateJour'>
  utilisateur?: Pick<User, 'id' | 'prenom' | 'nom'>
}

export interface Document {
  id: number
  typeDocument: 'CSV' | 'PDF'
  fileName: string
  dateCreation: string
  downloadUrl: string
}

export interface ApiResponse<T> {
  data: T
  message: string
}

export interface DashboardStats {
  total_employes: number
  presents_aujourd_hui: number
  anomalies_en_cours: number
  demandes_en_attente: number
  taux_presence: number
}

export interface GeoPosition {
  latitude: number
  longitude: number
  accuracy?: number
}
