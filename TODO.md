# TODO - Intégration idées Lucas

## Étape 1 — Admin
- [ ] Créer/ajouter une zone admin sous `pages/admin/` (dashboard, gestion événements, gestion utilisateurs)
- [ ] Ajouter `check_role('admin')` (ou equivalent) et garde-fous (404/403 selon votre style)

## Étape 2 — Inscription (double mot de passe)
- [ ] Modifier `pages/register.php` : ajouter champ `confirm_mdp` (ou `confirm_password`) + validation correspondance
- [ ] Conserver vos validations existantes (CSRF, longueur, unicitè email, hash BCRYPT)

## Étape 3 — Connexion (sécurité)
- [x] Modifier `pages/login.php` : intégrer compteur de tentatives + sleep après 3 erreurs

- [ ] S’assurer que `session_regenerate_id(true)` est appelé correctement
- [ ] Conserver votre gestion CSRF/erreurs/flash

## Étape 4 — QA
- [x] Rechercher dans le projet les routes/liens admin et vérifier les includes

- [x] Tester login/register (erreurs attendues)


- [ ] Tester accès admin protégé par le rôle

