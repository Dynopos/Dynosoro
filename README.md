# Dyno SEO

Artikel blog automatik setiap 2 hari untuk website pelanggan (GitHub Pages), dengan gambar AI
dan hook ke bisnes pelanggan di bawah setiap artikel. Website tak mati, sentiasa muncul di Google.

- Spec: [`docs/dynoseo-spec.md`](docs/dynoseo-spec.md)
- Pasang di Forge: [`docs/DEPLOY.md`](docs/DEPLOY.md)
- Arahan untuk Claude Code: [`CLAUDE.md`](CLAUDE.md)
- Keputusan: [`docs/decisions.md`](docs/decisions.md)
- Perubahan: [`docs/CHANGELOG.md`](docs/CHANGELOG.md)

Status: Fasa 0 (enjin + panel admin Bob).

```bash
composer install && npm ci && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan test
```
