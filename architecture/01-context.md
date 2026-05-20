# HeySentinel - Business Context and Stack

## 🎯 Product Concept
HeySentinel is a B2B SaaS designed specifically for Shopify App Developers. Its primary goal is to help developers identify market gaps and paths for improvement by analyzing existing applications in the Shopify App Store. The system processes app descriptions, pricing tiers, and—most importantly—user review sentiments and complaints. This aggregated data is then cross-referenced with shop demographics and performance metrics extracted from Storeleads.

## 🛠️ Tech Stack
- **Backend:** Laravel 11 + Filament (For both the admin panel and the Customer Portal).
- **Frontend:** React (TypeScript) + Inertia.js (Native integration via Laravel Breeze).
- **Database:** MySQL 8.0 + Redis (For managing scraping queues, caching, and proxy rate limits).
- **Testing:** Pest Framework.

## 📌 Key Workflows (TODOs)
1. **Scraping Pipeline:** Background modules that concurrently consume Webshare.io proxies using `Http::pool` to efficiently scrape Shopify apps, descriptions, and reviews.
2. **Storeleads Integration:** Periodic consumption of the official Storeleads API to import and enrich profiles for approximately 3 million Shopify stores (estimating traffic, volume, etc.).
3. **Data Integrity Validation:** Mechanisms to benchmark and verify own database accuracy against Storeleads in massive batches (ranging from 100K to 1M records).
4. **AI Analysis Engine:** A cost-effective text-processing pipeline designed to classify huge volumes of review data, extracting customer pain points and structuring them directly into the database. This avoids complex and expensive RAG architectures at runtime.