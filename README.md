# 🎉 OmnesEvent

**Plateforme centralisée de billetterie et de gestion d'événements** dédiée aux étudiants et au personnel d'Omnes Education.

> Projet Web Dynamique — ING2

---

## 📋 Présentation

La vie associative d'Omnes est riche (BDE, BDS, Junior Entreprise, conférences, soirées, tournois sportifs), mais les étudiants ont du mal à s'y retrouver. **OmnesEvent** centralise la création, la recherche et la réservation d'événements sur une seule plateforme.

### Rôles utilisateurs

| Rôle | Description |
|------|-------------|
| **Administrateur** | Super-utilisateur. Modère les événements, valide les comptes organisateurs, peut supprimer tout compte ou événement. |
| **Organisateur** | Représentant d'association. Crée, modifie et annule des événements. Accède à la liste des inscrits. |
| **Participant** | Étudiant ou personnel. Consulte le catalogue, s'inscrit aux événements, gère ses billets. |

---

## 🛠️ Stack technique

- **Front-end** : HTML5, CSS3 (mobile first), JavaScript / jQuery
- **Back-end** : PHP 8+
- **Base de données** : MySQL
- **Versioning** : Git

---

## 📁 Structure du projet

```
OmnesEvent/
│
├── index.php                        # Page d'accueil — liste des événements à venir
├── .htaccess                        # Réécriture d'URL, sécurité serveur
│
├── config/
│   ├── db.php                       # Connexion PDO à MySQL (host, user, mdp, dbname)
│   └── init.php                     # session_start(), include db.php, constantes globales
│
├── includes/
│   ├── header.php                   # DOCTYPE, <head>, navbar avec menu adapté au rôle
│   ├── footer.php                   # Pied de page, liens, scripts JS
│   ├── auth_check.php               # Vérifie si connecté, redirige sinon
│   ├── role_check.php               # Vérifie le rôle (admin, organisateur, participant)
│   └── functions.php                # Fonctions utilitaires (sanitize, redirect, flash msg)
│
├── pages/
│   ├── auth/
│   │   ├── login.php                # Formulaire de connexion + traitement POST
│   │   ├── register.php             # Formulaire d'inscription + choix du rôle
│   │   ├── logout.php               # Destruction de session + redirection
│   │   └── profil.php               # Affichage et modification du profil
│   │
│   ├── events/
│   │   ├── liste.php                # Catalogue complet avec recherche et filtres
│   │   ├── detail.php               # Page de détail d'un événement (GET ?id=X)
│   │   ├── creer.php                # Formulaire de création (organisateur only)
│   │   ├── modifier.php             # Modification d'un événement existant
│   │   └── supprimer.php            # Suppression avec confirmation
│   │
│   ├── reservations/
│   │   ├── reserver.php             # Traitement de l'inscription (vérif jauge + INSERT)
│   │   ├── annuler.php              # Annulation d'une réservation (DELETE)
│   │   ├── mes_billets.php          # Liste des billets du participant connecté
│   │   └── inscrits.php             # Liste des inscrits pour l'organisateur
│   │
│   └── admin/
│       ├── dashboard.php            # Tableau de bord admin (stats, actions rapides)
│       ├── utilisateurs.php         # Gestion des comptes, validation organisateurs
│       └── evenements.php           # Modération et suppression d'événements
│
├── assets/
│   ├── css/
│   │   ├── style.css                # Styles globaux, variables, reset, typographie
│   │   └── responsive.css           # Media queries mobile first
│   ├── js/
│   │   ├── main.js                  # Validation formulaires, menu burger, interactions
│   │   └── search.js                # Filtrage dynamique / recherche AJAX (optionnel)
│   └── img/
│       ├── logo.png                 # Logo OmnesEvent
│       └── default_event.jpg        # Image par défaut pour événements sans affiche
│
├── uploads/
│   ├── events/                      # Affiches uploadées par les organisateurs
│   └── avatars/                     # Photos de profil des utilisateurs
│
└── sql/
    └── omnes_event.sql              # Script de création des tables + données de test
```

> **Pattern commun** : chaque page PHP suit le même schéma :
> ```php
> include("config/init.php");
> include("includes/header.php");
> // — contenu de la page —
> include("includes/footer.php");
> ```

---

## 🗄️ Base de données

### Tables principales

| Table | Description |
|-------|-------------|
| `utilisateurs` | id, nom, prenom, email, mot_de_passe, role, avatar, date_creation |
| `evenements` | id, titre, description, date_event, lieu, categorie, jauge_max, affiche, id_organisateur |
| `reservations` | id, id_utilisateur, id_evenement, date_reservation, statut |
| `categories` | id, nom (Soirée, Sport, Culture, Conférence…) |

### Installation de la BDD

```bash
mysql -u root -p < sql/omnes_event.sql
```

---

## 🚀 Installation

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/votre-groupe/omnes-event.git
   ```

2. **Configurer la base de données**
   - Importer `sql/omnes_event.sql` dans MySQL / phpMyAdmin
   - Modifier `config/db.php` avec vos identifiants :
     ```php
     $host = 'localhost';
     $dbname = 'omnes_event';
     $user = 'root';
     $password = '';
     ```

3. **Lancer le serveur**
   - Placer le projet dans `htdocs/` (XAMPP) ou `www/` (WAMP)
   - Accéder à `http://localhost/OmnesEvent/`

---

## ✅ Fonctionnalités

### MVP (Minimum Viable Product)

- [x] Page d'accueil avec flux des événements à venir
- [x] Recherche et filtres (date, catégorie, association)
- [x] Inscription / connexion / déconnexion
- [x] Création d'événement avec upload d'affiche
- [x] Système de réservation avec jauge automatique
- [x] Page « Mes Billets » (participant)
- [x] Dashboard inscrits (organisateur)
- [x] Panel d'administration

### Bonus

- [ ] Génération de QR Code par billet
- [ ] File d'attente intelligente
- [ ] Calendrier interactif (FullCalendar.js)
- [ ] Carte interactive du lieu (Leaflet / Google Maps)
- [ ] Paiement simulé (Stripe mode test)

---

## 👥 Équipe & répartition

| Membre | Responsabilité |
|--------|---------------|
| **P1** | Authentification, gestion des comptes, panel admin |
| **P2** | CRUD événements, catalogue, recherche, filtres |
| **P3** | Système de réservation, billets, dashboard organisateur |
| **P4** | Design responsive (mobile first), intégration, déploiement |

---

## 🌿 Conventions Git

- **Branches** : `main` (production), `dev` (intégration), `auth`, `events`, `reservations`, `frontend`
- **Commits** : messages clairs en français — ex : `Ajout formulaire de création d'événement`
- **Merges** : sur `dev` au minimum 2 fois par semaine, sur `main` en fin de sprint

---

## 📄 Licence

Projet académique — Omnes Education, ING2.
