# HeySentinel — Local Dev Cheatsheet

Stack dockerizado vía **Laravel Sail**. Todo (PHP, MySQL 8, Redis, Mailpit) corre en contenedores. Tu Windows solo necesita Docker Desktop.

---

## 0. Requisitos

| Requisito | Verificar con | Si falta |
|-----------|---------------|----------|
| Docker Desktop | `docker --version` | Instalar desde docker.com y **arrancarlo** antes de cualquier comando `sail` |
| WSL2 backend habilitado en Docker Desktop | Settings → General → "Use WSL 2 based engine" | Activarlo y reiniciar |
| Composer (solo para bootstrap inicial / instalar packages nuevos) | `composer --version` | Ya tienes Composer 2.8 (Herd) — no toca nada |

> Si Docker Desktop no está corriendo verás un error tipo `error during connect: open //./pipe/dockerDesktop...`. **Arranca Docker Desktop primero.**

---

## 1. La forma fácil — ejecutables `.bat`

Doble click en la raíz del proyecto (`C:\Kraft\HeySentinel\`):

| Ejecutable | Qué hace |
|-----------|----------|
| **`start.bat`** | Levanta toda la stack (Laravel + MySQL + Redis + Mailpit), espera healthchecks, imprime URLs |
| **`stop.bat`** | Detiene los contenedores (preserva data) |
| **`status.bat`** | Muestra estado de servicios + URLs de acceso |
| **`shell.bat`** | Abre bash dentro del contenedor (para artisan, composer, mysql, etc.) |
| **`artisan.bat`** | Atajo: `artisan migrate`, `artisan make:filament-user`, `artisan tinker`, etc. |

Primera vez:
```cmd
start.bat
```

Eso es todo. Para los detalles técnicos sigue leyendo.

### Credenciales de prueba (solo dev local)

Ya está creado un admin de Filament para que entres sin más pasos:

| Campo | Valor |
|-------|-------|
| URL | http://localhost:8080/admin |
| Email | `admin@heysentinel.test` |
| Password | `password` |
| Nombre | Greg Altuve |

> ⚠ **Solo para dev local.** Este usuario está en el `.env` con dominio `.test` y password trivial. **Nunca** uses estas credenciales en staging/producción. En producción se crea con `artisan make:filament-user` y password fuerte, o se elimina y se reemplaza por el flujo de signup real.

Para crear más usuarios:
```cmd
.\artisan.bat make:filament-user
```
(interactivo: te pide nombre, email, password)

O no-interactivo:
```cmd
.\artisan.bat make:filament-user --name="Foo" --email="foo@bar.test" --password="secret"
```

Para resetear el password del admin de prueba si lo olvidas:
```cmd
.\artisan.bat tinker
>>> User::where('email','admin@heysentinel.test')->update(['password' => bcrypt('password')]);
>>> exit
```

---

## 2. Accesos (URLs)

> **Nota:** los puertos por defecto de Sail (80, 3306, 6379, 8025, 5173) estaban ocupados en tu máquina por otros stacks (Talos, Docker WSL relay). El `.env` los movió a estos:

| Servicio | URL | Notas |
|----------|-----|-------|
| App Laravel | http://localhost:8080 | Página de bienvenida por defecto |
| **Filament Admin Panel** | http://localhost:8080/admin | Login con el usuario que creaste con `artisan make:filament-user` |
| **Horizon (queues)** | http://localhost:8080/horizon | Dashboard de jobs, throughput, failed jobs |
| **Mailpit (correos capturados)** | http://localhost:8026 | Todos los emails que envíe la app aterrizan acá (no salen a internet) |
| MySQL | localhost:**3307** | user=`sail`, pass=`password`, db=`heysentinel` — abrir con HeidiSQL/DBeaver/TablePlus |
| Redis | localhost:**6380** | Sin password |
| Vite (dev server) | localhost:5174 | Solo cuando estés trabajando en frontend (Fase 7) |

---

## 3. Comandos del día a día

> **Importante:** el wrapper `vendor\bin\sail` de Laravel no funciona en este Windows (depende de WSL bash con un PATH que falla). Usa los `.bat` de la raíz o `docker compose` directo.

### Arranque / parada
```powershell
.\start.bat              # arranca todo
.\stop.bat               # detiene (preserva datos)
.\status.bat             # estado + URLs

# O directo con docker compose:
docker compose up -d
docker compose stop
docker compose down              # elimina contenedores (preserva volúmenes)
docker compose down -v           # ⚠ elimina TODO incluyendo data
docker compose restart
docker compose ps
docker compose logs -f laravel.test
docker compose logs mysql
```

### Artisan / Composer / Tinker
```powershell
.\artisan.bat migrate
.\artisan.bat migrate:fresh --seed
.\artisan.bat make:model ShopifyApp -mf
.\artisan.bat make:filament-resource ShopifyApp --generate
.\artisan.bat make:filament-user
.\artisan.bat tinker

# Composer dentro del contenedor:
docker compose exec laravel.test composer require <package>
docker compose exec laravel.test composer update
```

### Queues / Horizon
```powershell
.\artisan.bat queue:work
.\artisan.bat horizon
.\artisan.bat horizon:status
.\artisan.bat queue:failed
.\artisan.bat queue:retry all
```

### Tests (Pest)
```powershell
docker compose exec laravel.test ./vendor/bin/pest
docker compose exec laravel.test ./vendor/bin/pest --filter=Account
docker compose exec laravel.test ./vendor/bin/pest --parallel
docker compose exec laravel.test ./vendor/bin/pest --coverage
```

### Base de datos
```powershell
docker compose exec mysql mysql -usail -ppassword heysentinel
.\artisan.bat db:show
.\artisan.bat db:table users
```

### Frontend (cuando llegues a Fase 7)
```powershell
docker compose exec laravel.test npm install
docker compose exec laravel.test npm run dev
docker compose exec laravel.test npm run build
```

### Shell dentro del contenedor
```powershell
.\shell.bat                                    # bash como user sail
docker compose exec -u root laravel.test bash  # como root (apt-get, etc.)
```

---

## 4. Troubleshooting rápido

| Síntoma | Causa probable | Fix |
|---------|----------------|-----|
| `error during connect: open //./pipe/dockerDesktop...` | Docker Desktop no está corriendo | Arrancar Docker Desktop y esperar 30s |
| `bash: WSL ERROR / Failed to translate` al usar `vendor\bin\sail` | Wrapper Sail usa WSL bash que falla en este Windows | Usa los `.bat` de la raíz o `docker compose` directo |
| `port XXXX is already allocated` al hacer up | Otro stack (Talos, XAMPP) ocupa ese puerto | Cambiar el `FORWARD_*_PORT` correspondiente en `.env`, luego `docker compose down && start.bat` |
| Filament Login: "These credentials do not match" | Aún no creaste user | `.\artisan.bat make:filament-user` |
| Horizon dashboard "Forbidden" fuera de local | `APP_ENV != local` | Editar `app/Providers/HorizonServiceProvider.php` → método `gate()` |
| Cambios en `.env` no surten efecto | Caché de config | `.\artisan.bat config:clear` o `docker compose restart laravel.test` |
| Stack colgada en healthcheck de MySQL | Volumen MySQL corrupto | `docker compose down -v && start.bat` (⚠ borra datos) |
| Cambiaste código PHP y no se refleja | Container no recargó | PHP-FPM detecta cambios automáticamente; si no, `docker compose restart laravel.test` |
| Permisos extraños en `storage/` o `bootstrap/cache/` | UID/GID mismatch host↔contenedor | `docker compose exec -u root laravel.test chown -R sail:sail storage bootstrap/cache` |

---

## 5. Estructura mental del proyecto

```
C:\Kraft\HeySentinel\
├── architecture/                ← Documentos source-of-truth (no tocar sin actualizar el plan)
│   ├── 01-context.md
│   ├── 02-database.md
│   └── 03-ai-pipeline.md.txt
├── app/
│   ├── Models/                  ← Plan, Account, User, ShopifyApp... (Fase 1-2)
│   ├── Filament/Resources/      ← UI admin (Fase 5)
│   ├── Jobs/                    ← Scraping + AI jobs (Fase 3-4)
│   ├── Services/                ← WebshareProxyManager, AnthropicBatchClient, etc.
│   └── Providers/Filament/AdminPanelProvider.php
├── database/
│   ├── migrations/              ← Esquema (Fase 1-2)
│   ├── factories/
│   └── seeders/
├── tests/
│   ├── Feature/
│   └── Unit/
├── compose.yaml                 ← Sail/Docker setup
├── .env                         ← Config local (NO subir a git)
├── .env.example                 ← Plantilla para nuevos devs
└── CHEATSHEET.md                ← Este archivo
```

---

## 6. Próximas fases (según `architecture/Master Plan`)

| Fase | Estado | Comando para iniciar |
|------|--------|----------------------|
| **Fase 0** — Bootstrap | ✅ Hecho | — |
| Fase 1 — SaaS Core (plans, accounts, users) | ✅ Hecho | — |
| Fase 2 — Core Data (apps, stores, reviews) | En curso | "procede con Fase 2" |
| Fase 3 — Motor de Scraping | En curso | (requiere `WEBSHARE_API_TOKEN` en `.env`) |
| Fase 4 — Pipeline de IA | Pendiente | (requiere `ANTHROPIC_API_KEY` en `.env`) |
| Fase 5 — Filament Admin Resources | Slice hecho (Plan/Account/User) | |
| Fase 6 — Customer Portal Filament | Pendiente | |
| Fase 7 — Frontend React + Inertia | Pendiente | |
| Fase 8 — Hardening + Observabilidad | Continuo | |

---

## 7. Cheats varios

```powershell
# Ver qué versión de PHP corre el contenedor
docker compose exec laravel.test php -v

# Limpiar TODAS las cachés de Laravel
.\artisan.bat optimize:clear

# Generar nuevo APP_KEY
.\artisan.bat key:generate

# Hacer dump de DB
docker compose exec mysql mysqldump -usail -ppassword heysentinel > backup.sql

# Restaurar dump
Get-Content backup.sql | docker compose exec -T mysql mysql -usail -ppassword heysentinel

# Ver rutas registradas
.\artisan.bat route:list

# Ver schedule programado
.\artisan.bat schedule:list

# Disparar el cron manualmente (sin esperar al minuto)
.\artisan.bat schedule:run
```

---

**Si algo no anda, primero**: `docker compose logs -f laravel.test`. La mayoría de problemas son `.env` desactualizado → `docker compose restart laravel.test`.

---

## 8. Verificación Fase 1

Tras un `migrate:fresh --seed` deberías ver:

| Check | Comando | Esperado |
|---|---|---|
| 15 tablas | `.\artisan.bat db:show` | `Tables = 15` (9 de Fase 0 + 6 nuevas) |
| 3 planes | `.\artisan.bat tinker --execute="echo App\Models\Plan::count();"` | `3` |
| 5 accounts demo | `.\artisan.bat tinker --execute="echo App\Models\Account::count();"` | `5` |
| 13 users | `.\artisan.bat tinker --execute="echo App\Models\User::count();"` | `13` |
| admin rol owner | `.\artisan.bat tinker --execute="echo App\Models\User::where('email','admin@heysentinel.test')->first()->roleIn(App\Models\Account::where('slug','heysentinel-internal')->first())->value;"` | `owner` |
| Test suite | `docker compose exec laravel.test ./vendor/bin/pest` | ~19 passed |
| Pint clean | `docker compose exec laravel.test ./vendor/bin/pint --test` | sin issues |

### En el panel Filament

Login con `admin@heysentinel.test` / `password` en `/admin`. La barra lateral muestra `Plans`, `Accounts`, `Users` (slice de Fase 5):
- `Plans` → 3 filas; editar abre el Relation Manager de `features`.
- `Accounts` → 5 filas con badges de status; editar abre Relation Manager de `users`.
- `Users` → 13 filas.
