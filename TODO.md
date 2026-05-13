# TODO — OmnesEvent (Groupe 4)

## Phase 0 — Fondation commune (Semaine 1)
- [ ] Créer l’arborescence projet : `includes/`, `assets/`, `pages/`, `uploads/`.
- [ ] Spécifier schéma MySQL MVP + contraintes (FK, index) : `db/schema.sql`.
- [ ] Mettre `includes/db.php` (connexion DB + helpers).
- [ ] Créer `includes/header.php` + `includes/footer.php` + squelette `pages/index.php`.
- [ ] Initialiser Git (repo + branches) et commit “phase-0”.

## Phase A — Auth & admin
- [ ] Inscription + hash mot de passe.
- [ ] Connexion/déconnexion (sessions) + redirection par rôle.
- [ ] CRUD “Mon profil”.
- [ ] Ajout d’un bootstrap auth/session pour que `$currentUser` soit cohérent partout.

## Phase B — Événements (CRUD)
- [ ] Catalogue accueil + carte événement (affiche/date/lieu/places restantes).
- [ ] Recherche & filtres (date/catégorie/association).
- [ ] Création/édition/suppression événement (organisateur + admin).
- [ ] Upload affiche.

## Phase C — Réservation & billets
- [ ] Inscription événement (blocage si jauge max atteinte).
- [ ] Page “Mes billets” (à venir / passés) + annulation.
- [ ] Dashboard organisateur (liste inscrits + validation présence).

## Phase D — Front & intégration
- [ ] Responsive mobile-first + CSS composants.
- [ ] Interactions JS/jQuery (modales, validations, retours UI).
- [ ] Déploiement final + merges Git + tests globaux.

## Bonus (après MVP)
- [ ] QR code par billet.
- [ ] File d’attente intelligente.
- [ ] Calendrier interactif.
- [ ] Carte interactive des lieux.
- [ ] Paiement en ligne simulé (Stripe test/mock).

