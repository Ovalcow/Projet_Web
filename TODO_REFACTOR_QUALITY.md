²# TODO_REFACTOR_QUALITY (blackboxai)

## Phase 1 — low-risk & rentable
- [x] Unifier le cycle CSRF/session: éviter `session_start()` multiple (init.php + functions.php)
- [x] Optimiser `pages/event_detail.php` pour réduire les requêtes (charger `hasReservation` via le SELECT principal)



## Phase 2 — lisibilité maintenabilité
- [ ] Factoriser pattern validation/erreurs/redirect (pages login/register/profile)
- [ ] Déplacer progressivement styles inline vers CSS (classes réutilisables)

## Phase 3 — robustesse & perf
- [ ] Sécuriser davantage la vérification QR (si billets falsifiables via ID)
- [ ] Améliorer maintenabilité de `assets/js/qr-local.js` (factoriser + protéger cases limites)

