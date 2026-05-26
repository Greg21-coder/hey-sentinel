export interface User {
    id: number;
    name: string;
    email: string;
    is_super_admin: boolean;
}

export interface Plan {
    id: number;
    name: string;
    slug: string;
    description?: string;
    monthly_price: number;
    yearly_price: number;
    sort_order: number;
    is_public: boolean;
    features?: PlanFeature[];
}

export interface PlanFeature {
    id: number;
    feature_key: string;
    feature_value: string;
    value_type: string;
}

export interface Account {
    id: number;
    name: string;
    slug: string;
    status: string;
    plan: Plan;
}

export interface ShopifyApp {
    id: number;
    shopify_app_handle: string;
    name: string;
    developer_name: string;
    category?: string;
    description?: string;
    pricing_raw?: string;
    pricing_min_usd?: number;
    pricing_has_free: boolean;
    avatar_url?: string;
    average_rating: number;
    total_reviews: number;
    total_installs_estimate?: number;
    scraping_status: string;
    ai_summary?: string;
    unlisted_at?: string;
    pivot_kind?: string;
    pivot_followed_at?: string;
}

export interface ShopifyAppCategory {
    id: number;
    slug: string;
    name: string;
}

export interface ShopifyStore {
    id: number;
    domain: string;
    store_name?: string;
    country_code?: string;
    estimated_monthly_visits?: number;
    estimated_monthly_sales_usd?: number;
    apps_installed_count?: number;
    unlisted_at?: string;
}

export interface StoreReview {
    id: number;
    shopify_app_id: number;
    reviewer_name?: string;
    rating: number;
    review_text?: string;
    ai_status: string;
    ai_sentiment?: string;
    published_at?: string;
    app?: ShopifyApp;
}

export interface AiPainPoint {
    id: number;
    slug: string;
    name: string;
    category?: string;
    mentions?: number;
    apps_count?: number;
}

export interface AccountFollowedApp {
    id: number;
    account_id: number;
    shopify_app_id: number;
    kind: string;
    followed_at: string;
    notes?: string;
    shopify_app?: ShopifyApp;
}

export interface SavedSearchFilters {
    category_id?: number;
    rating_min?: number;
    rating_max?: number;
    pricing?: string;
    pain_point_ids?: number[];
    keyword?: string;
}

export interface SavedSearch {
    id: number;
    name: string;
    filters: SavedSearchFilters;
    notify_on_new: boolean;
    last_viewed_at?: string;
    new_results_count?: number;
    created_at: string;
    updated_at: string;
}

export interface FeatureGate {
    value: boolean | number | string;
    type: string;
    usage: number;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
    path: string;
    first_page_url: string | null;
    last_page_url: string | null;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
}
