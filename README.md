# HeySentinel

Shopify App Store intelligence platform. Discovers apps, scrapes listings and reviews, extracts pain points via AI, and serves analytics dashboards for merchants and analysts.

## Tech Stack

- **Backend:** Laravel 11, PHP 8.5
- **Frontend:** React 18, TypeScript, Inertia.js, Tailwind CSS
- **Database:** MySQL 8.4
- **Queue:** Redis + Laravel Horizon
- **AI:** Ollama (local) or Anthropic Batch API
- **Dev environment:** Docker via Laravel Sail

## Prerequisites

| Requirement | Version |
|---|---|
| Docker Desktop | 4.x+ |
| Git | 2.x+ |
| Node.js | 20+ (only needed outside Docker) |

**Windows:** Install [WSL 2](https://learn.microsoft.com/en-us/windows/wsl/install) with Ubuntu. Docker Desktop must have WSL 2 backend enabled. Clone the repo inside WSL (e.g. `~/projects/`), not on the Windows filesystem.

**macOS / Linux:** Docker Desktop (Mac) or Docker Engine (Linux). No extra setup needed.

## Installation

### 1. Clone and configure

```bash
git clone git@github.com:your-org/HeySentinel.git
cd HeySentinel
cp .env.example .env
```

Edit `.env` and set any API keys you have:

```dotenv
# Optional — only needed if using these services
STORELEADS_API_KEY=your-key-here
ANTHROPIC_API_KEY=your-key-here

# AI provider: "ollama" (local, free) or "anthropic" (cloud, paid)
AI_PROVIDER=ollama
OLLAMA_BASE_URL=http://host.docker.internal:11434
OLLAMA_MODEL=qwen2.5:7b-instruct
```

### 2. Install dependencies and build

```bash
# Install PHP dependencies (builds the Sail Docker image on first run)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php85-composer:latest \
    composer install --ignore-platform-reqs

# Start all services
./vendor/bin/sail up -d

# Generate app key
./vendor/bin/sail artisan key:generate

# Install Node dependencies and build frontend
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# Run migrations
./vendor/bin/sail artisan migrate

# Seed the database (pain point vocabulary + demo data)
./vendor/bin/sail artisan db:seed
```

### 3. Verify

Open [http://localhost](http://localhost) (or the port configured in `APP_PORT`).

## Services

After `sail up -d`, these containers run automatically:

| Service | Container | Purpose |
|---|---|---|
| **App** | `laravel.test` | Laravel web server |
| **Horizon** | `horizon` | Queue worker (processes all jobs) |
| **Scheduler** | `scheduler` | Cron scheduler (`schedule:work`) |
| **Vite** | `vite` | Frontend dev server with HMR |
| **MySQL** | `mysql` | Database |
| **Redis** | `redis` | Queue, cache, sessions |
| **Mailpit** | `mailpit` | Email testing UI at :8025 |

## Data Pipeline

The full pipeline runs automatically via cron and job chaining:

```
Discovery (every 12h)
  Parses Shopify sitemap, discovers new app handles
    |
    v
Scrape Apps (auto-dispatched per new app)
  Fetches app page: name, rating, pricing, category
    |
    v
Scrape Reviews (auto-dispatched, 3 pages per app)
  Extracts reviewer, rating, text, date
    |
    v
AI Pain Point Extraction (auto-dispatched on new reviews)
  Ollama/Anthropic extracts sentiment + pain points per review
    |
    v
Analytics ready in dashboards
```

### Manual triggers

From the admin panel (`/admin`):

- **Discovery > Run Discovery Now** -- Discover new apps from sitemap
- **Shopify Apps > Scrape Pending** -- Scrape all pending apps (or select specific ones)

Both buttons trigger the full pipeline automatically.

### Artisan commands

```bash
# Discover new apps from Shopify sitemap
./vendor/bin/sail artisan app:discover:apps --limit=500

# Scrape pending/error app pages
./vendor/bin/sail artisan app:scrape:apps --limit=300

# Scrape reviews for all scraped apps
./vendor/bin/sail artisan app:scrape:reviews --pages=3

# Compile AI batch (pain point extraction)
./vendor/bin/sail artisan app:ai:compile-batch

# Poll AI batch results (for async providers)
./vendor/bin/sail artisan app:ai:poll-batches
```

### Cron schedule

| Time | Command | Purpose |
|---|---|---|
| 2:00, 14:00 | `app:discover:apps` | Discover + auto-scrape new apps |
| 4:00 | `app:scrape:apps --limit=1000` | Safety net for missed apps |
| 5:00 | `app:storeleads:sync` | Enrich store data |
| Configurable | `app:ai:compile-batch` | Batch AI extraction |
| Configurable | `app:ai:poll-batches` | Poll async AI results |

## AI Setup (Ollama)

For local AI processing (free, no API key needed):

```bash
# Install Ollama (macOS/Linux)
curl -fsSL https://ollama.com/install.sh | sh

# Windows: download from https://ollama.com/download

# Pull the model
ollama pull qwen2.5:7b-instruct

# Ollama runs on http://localhost:11434 by default
# Docker containers reach it via http://host.docker.internal:11434
```

## Development

```bash
# Start services
./vendor/bin/sail up -d

# Frontend dev server (already running via vite container, or manually)
./vendor/bin/sail npm run dev

# Run tests
./vendor/bin/sail test

# TypeScript check
./vendor/bin/sail npx tsc --noEmit

# View Horizon dashboard
# http://localhost/horizon

# View Mailpit inbox
# http://localhost:8025
```

### Useful commands

```bash
# Check pipeline status
./vendor/bin/sail artisan tinker --execute="
echo 'Apps scraped: ' . App\Models\ShopifyApp::where('scraping_status', 'scraped')->count();
echo 'Apps pending: ' . App\Models\ShopifyApp::where('scraping_status', 'pending')->count();
echo 'Reviews: ' . App\Models\StoreReview::count();
echo 'AI processed: ' . App\Models\StoreReview::where('ai_status', 'processed')->count();
"

# Restart Horizon after config changes
docker compose restart horizon

# Clear and retry failed jobs
./vendor/bin/sail artisan queue:flush
./vendor/bin/sail artisan queue:retry all
```

## Project Structure

```
app/
  Console/Commands/     Artisan commands (discover, scrape, AI)
  Http/Controllers/
    Admin/              Admin panel controllers
    Customer/           Customer portal controllers
  Jobs/
    Scraping/           Discovery, app scraping, review scraping
    Ai/                 Batch compilation, polling, ingestion
  Models/               Eloquent models
  Services/Scraping/    Sitemap parser, HTTP scrapers, HTML parsers
config/
  scraping.php          Scraping + discovery configuration
  ai.php                AI provider and batch configuration
  horizon.php           Queue worker configuration
resources/js/
  Pages/Admin/          Admin React pages (Inertia)
  Pages/Customer/       Customer React pages (Inertia)
  Components/           Shared UI components
  Layouts/              Admin and Customer layouts
```

## License

Proprietary. All rights reserved.
