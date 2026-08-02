# Security Policy

## 🇫🇷 Français

Ceci est un projet WordPress (Bedrock) personnel. Il ne suit pas de matrice
de versions supportées — seul le code de la branche `main`, actuellement
déployé en production, est pris en charge.

### Signaler une vulnérabilité

Merci de **ne pas** ouvrir d'issue GitHub publique pour une faille de
sécurité.

Utilise plutôt le signalement privé de GitHub :
[Signaler une vulnérabilité](../../security/advisories/new) (onglet
Security > Advisories > Report a vulnerability). Ça ouvre une alerte privée
visible uniquement par le mainteneur.

Si ce n'est pas possible, envoie un email à
**maximejolivet.pro@gmail.com** avec :
- Une description de la vulnérabilité et de son impact potentiel
- Les étapes pour la reproduire (ou un proof of concept)
- Les logs/captures d'écran pertinents

Une première réponse est à prévoir sous quelques jours. Le projet est
maintenu seul, donc pas de SLA formel, mais les vulnérabilités confirmées
affectant le site en production seront traitées en priorité.

### Périmètre

Dans le périmètre : le thème custom (`web/app/themes/custom/tailwind`), le
bootstrap/config Bedrock (`config/`, `web/index.php`, `web/wp-config.php`),
et le pipeline de déploiement (`.github/workflows/`).

Hors périmètre : le cœur WordPress, les plugins/thèmes tiers installés via
Composer (`wp-plugin/*`, `wp-theme/*`) — à signaler directement à leurs
mainteneurs respectifs. `composer audit` (voir [`CLAUDE.md`](CLAUDE.md))
tourne à chaque push pour détecter les CVE connues dans les dépendances.

---

## 🇬🇧 English

This is a personal WordPress (Bedrock) project. It doesn't follow a
version-support matrix — only the code on the `main` branch, currently
deployed in production, is supported.

### Reporting a vulnerability

Please **do not** open a public GitHub issue for a security vulnerability.

Instead, use GitHub's private reporting:
[Report a vulnerability](../../security/advisories/new) (Security tab >
Advisories > Report a vulnerability). This opens a private advisory only
visible to the maintainer.

If you can't use that, email **maximejolivet.pro@gmail.com** with:
- A description of the vulnerability and its potential impact
- Steps to reproduce (or a proof of concept)
- Any relevant logs/screenshots

Expect an initial response within a few days. This is a solo-maintained
project, so there's no formal SLA, but confirmed vulnerabilities affecting
the live site will be prioritized.

### Scope

In scope: the custom theme (`web/app/themes/custom/tailwind`), Bedrock
bootstrap/config (`config/`, `web/index.php`, `web/wp-config.php`), and the
deployment pipeline (`.github/workflows/`).

Out of scope: WordPress core, third-party plugins/themes pulled via
Composer (`wp-plugin/*`, `wp-theme/*`) — report those upstream to their
respective maintainers. `composer audit` (see [`CLAUDE.md`](CLAUDE.md)) is
run on every push to catch known CVEs in dependencies.
