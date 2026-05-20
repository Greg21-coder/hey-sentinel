# HeySentinel - Database Architecture (MySQL)

This relational model decouples the SaaS subscription/access ecosystem (customer accounts) from the Core Data Core harvested from Shopify and Storeleads.

## 🔑 Part A: Subscription & Access System (SaaS)

### 1. PLANS
| Attribute | Key Type | Data Type / Constraint | Description |
| :--- | :---: | :--- | :--- |
| **id** | PK | BIGINT UNSIGNED (AutoIncrement) | Unique identifier for the subscription plan. |
| name | | VARCHAR(100) NOT NULL | Commercial name (e.g., Premium, Agency). |
| monthly_price | | DECIMAL(8,2) NOT NULL | Monthly subscription fee in USD. |
| yearly_price | | DECIMAL(8,2) NOT NULL | Yearly subscription fee in USD. |
| status | | VARCHAR(20) DEFAULT 'active' | Operational status of the plan. |

### 2. PLANS_FEATURES
| Attribute | Key Type | Data Type / Constraint | Description |
| :--- | :---: | :--- | :--- |
| **id** | PK | BIGINT UNSIGNED (AutoIncrement) | Unique identifier for the feature limit rule. |
| plan_id | FK | BIGINT UNSIGNED NOT NULL (Ref: PLANS) | The plan this feature rule belongs to. |
| feature_key | | VARCHAR(50) NOT NULL | Technical identifier (e.g., `export_csv`, `alerts`). |
| feature_value | | VARCHAR(255) NOT NULL | The threshold or allowed value (e.g., `true`, `100`). |

### 3. ACCOUNTS
| Attribute | Key Type | Data Type / Constraint | Description |
| :--- | :---: | :--- | :--- |
| **id** | PK | BIGINT UNSIGNED (AutoIncrement) or UUID | Customer organization/workspace account. |
| name | | VARCHAR(100) NOT NULL | Name of the company or workspace. |
| status | | VARCHAR(20) DEFAULT 'active' | Current account status. |
| plan_id | FK | BIGINT UNSIGNED NOT NULL (Ref: PLANS) | Currently active subscription plan. |
| free_trial_ends_at| | TIMESTAMP NULL | Timestamp when the free trial period expires. |
| created_at | | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Account creation timestamp. |

### 4. USERS
| Attribute | Key Type | Data Type / Constraint | Description |
| :--- | :---: | :--- | :--- |
| **id** | PK | BIGINT UNSIGNED (AutoIncrement) or UUID | Unique identifier for the user. |
| email | | VARCHAR(255) UNIQUE NOT NULL | User login email address. |
| password_hash | | VARCHAR(255) NOT NULL | Bcrypt encrypted password. |
| name | | VARCHAR(100) NULL | User's full name. |
| created_at | | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Account registration timestamp. |

### 5. ACCOUNT_USER (Pivot Table / Access Control)
| Attribute | Key Type | Data Type / Constraint | Description |
| :--- | :---: | :--- | :--- |
| account_id | PK, FK | BIGINT UNSIGNED (Ref: ACCOUNTS) | Associated Account ID. |
| user_id | PK, FK | BIGINT UNSIGNED (Ref: USERS) | Associated User ID. |
| role | | VARCHAR(20) NOT NULL | Access role within the workspace (e.g., 'owner', 'admin'). |
| joined_at | | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Timestamp when the user was linked to the account. |

---

## 📊 Part B: Core Data (Scraped Shopify Information)

### 6. SHOPIFY_APPS
| Attribute | Key Type | Data Type / Constraint | Description |
| :--- | :---: | :--- | :--- |
| **id** | PK | BIGINT UNSIGNED (AutoIncrement) | Internal database relation identifier. |
| shopify_app_id | | VARCHAR(255) UNIQUE NOT NULL | Unique app slug or ID from the Shopify App Store. |
| name | | VARCHAR(255) NOT NULL | Name of the competitor's application. |
| developer_name | | VARCHAR(255) NOT NULL | Name of the agency or developer who built it. |
| description | | TEXT NULL | Full text description of the application. |
| pricing_raw | | TEXT NULL | Raw text containing pricing tiers and plans data. |
| avatar_url | | VARCHAR(255) NULL | URL linking to the app's logo or thumbnail. |
| average_rating | | DECIMAL(3,2) DEFAULT 0.00 | Average review rating score on the store. |
| total_reviews | | INT DEFAULT 0 | Total count of indexed reviews for this app. |
| updated_at | | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last time the scraper updated this record. |

### 7. SHOPIFY_STORES
| Attribute | Key Type | Data Type / Constraint | Description |
| :--- | :---: | :--- | :--- |
| **id** | PK | BIGINT UNSIGNED (AutoIncrement) | Internal database identifier for the Shopify store. |
| domain | | VARCHAR(255) UNIQUE NOT NULL | Core web domain (e.g., `example.myshopify.com`). |
| store_name | | VARCHAR(255) NULL | Commercial name of the shop. |
| storeleads_data | | JSON NULL | Raw payload imported directly from the Storeleads API. |
| scraped_at | | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | System timestamp when the store was indexed. |

### 8. STORE_REVIEWS
| Attribute | Key Type | Data Type / Constraint | Description |
| :--- | :---: | :--- | :--- |
| **id** | PK | BIGINT UNSIGNED (AutoIncrement) | Unique database identifier for the review. |
| shopify_app_id | FK | BIGINT UNSIGNED NOT NULL (Ref: SHOPIFY_APPS) | The target application being reviewed. |
| shopify_store_id| FK | BIGINT UNSIGNED NOT NULL (Ref: SHOPIFY_STORES)| The specific store that posted the review. |
| reviewer_name | | VARCHAR(255) NOT NULL | Display name of the reviewer. |
| rating | | TINYINT NOT NULL | Numeric score rating ranging from 1 to 5. |
| review_text | | TEXT NOT NULL | Text body of the review containing feedback or complaints. |
| ai_sentiment | | VARCHAR(50) NULL | AI Classification label (e.g., 'pain_point', 'bug', 'feature_request'). |
| published_at | | TIMESTAMP NOT NULL | Original publication timestamp on the Shopify App Store. |