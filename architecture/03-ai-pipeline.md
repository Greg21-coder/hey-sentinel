# HeySentinel - Data Pipeline & AI Specification

## 🕸️ Scraping Strategy & Integrity
1. **Concurrency Management:** Built using Laravel's native HTTP client (`Http::pool`), dispatching background chunk operations managed via Laravel Queues (Redis).
2. **Proxy Rotation:** Integration with the Webshare.io API to execute distributed requests, ensuring total bypass of Shopify App Store IP rate limits.
3. **Data Sync Scheduling:** Scheduled console tasks (`App\Console\Kernel`) engineered to automatically ingest and compute data deltas coming periodically from Storeleads.

## 🤖 AI Analysis Engine Architecture (Cost-Efficient Alternative to RAG)
To guarantee low API overhead costs and rapid query performance across multi-tenant Filament dashboards:
- Scraped reviews and raw application text data **will not** be embedded into vector databases for real-time querying.
- **Asynchronous Pipeline:** Upon capturing data via the scraping engine, background jobs push small text payloads to the LLM.
- The LLM processes, classifies, and distills key tags, specific customer pain points, and market gaps.
- This structured output is saved directly into standard relational columns (such as the `ai_sentiment` field and structured metadata). End-users fetch this pre-processed analytical intelligence instantly via standard Filament database queries.