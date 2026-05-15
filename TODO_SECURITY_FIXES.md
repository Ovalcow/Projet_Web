# Sécurité - TODO

## Étape 1 — CSRF
- [ ] Ajouter des helpers CSRF dans `includes/functions.php` (ou fichier dédié) : génération + vérification
- [ ] Ajouter un champ caché CSRF dans les formulaires POST :
  - [ ] `pages/login.php`
  - [ ] `pages/register.php`
  - [ ] `pages/profile.php`
- [ ] Vérifier le token CSRF côté serveur avant traitement POST dans ces pages

## Étape 2 — Session hardening
- [ ] Durcir `includes/init.php` : paramètres cookies (HttpOnly/SameSite/Secure) + régénération d’ID de session au login

## Étape 3 — QR billet (prévu mais hors-scope pour l’instant)
- [ ] Revoir `pages/billet_verify.php` quand la fonctionnalité de scan QR sera implémentée (auth/usage/signature)

## Étape 4 — Vérification finale
- [ ] Lancer un scan de patterns (GET/POST + header Location) et revalider que les endpoints sensibles ont CSRF

