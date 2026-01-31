// Auth types
export interface User {
    id: string;
    email: string | null;
    name: string | null;
    given_name: string | null;
    family_name: string | null;
    username: string | null;
    email_verified: boolean;
    roles: string[];
    is_admin: boolean;
    is_vendor: boolean;
    is_customer: boolean;
}

export interface VendorInfo {
    exists: boolean;
    id?: number;
    business_name?: string;
    slug?: string;
    status?: string;
    is_approved?: boolean;
    logo?: string | null;
}

export interface Auth {
    user: User | null;
    vendor: VendorInfo | null;
}

export interface Flash {
    success: string | null;
    error: string | null;
    warning: string | null;
    info: string | null;
}

export interface AppConfig {
    name: string;
    url: string;
}

export interface Brand {
    name: string;
    logo?: string;
    primary_color?: string;
}

export interface Tenant {
    id: string | null;
}

// Page props shared across all pages
export interface SharedPageProps {
    app: AppConfig;
    auth: Auth;
    tenant: Tenant;
    flash: Flash;
    brand?: Brand;
}

// Product types
export interface Product {
    id: number;
    name: string;
    slug: string;
    sku: string;
    short_description?: string;
    description?: string;
    base_price: number;
    compare_at_price?: number | null;
    current_price: number;
    price_range: { min: number; max: number };
    has_discount: boolean;
    discount_percentage?: number;
    is_in_stock: boolean;
    stock_quantity: number;
    has_variants: boolean;
    product_type: 'physical' | 'digital' | 'service';
    average_rating: number;
    review_count: number;
    primary_image_url?: string | null;
    is_featured: boolean;
    status: 'draft' | 'pending_review' | 'active' | 'inactive' | 'rejected';
    vendor?: VendorSummary;
    category?: CategorySummary;
    images?: ProductImage[];
    options?: ProductOption[];
    variants?: ProductVariant[];
}

export interface ProductImage {
    id: number;
    url: string;
    alt_text?: string;
    is_primary: boolean;
}

export interface ProductOption {
    id: number;
    name: string;
    values: ProductOptionValue[];
}

export interface ProductOptionValue {
    id: number;
    value: string;
    label: string;
    color_code?: string | null;
}

export interface ProductVariant {
    id: number;
    sku: string;
    name: string;
    price: number;
    compare_at_price?: number | null;
    stock_quantity: number;
    is_in_stock: boolean;
    option_values: Record<string, string>;
}

// Vendor types
export interface Vendor {
    id: number;
    business_name: string;
    slug: string;
    email: string;
    business_type: 'individual' | 'company' | 'partnership';
    phone?: string;
    description?: string;
    logo?: string | null;
    banner?: string | null;
    website?: string;
    status: 'pending' | 'under_review' | 'approved' | 'suspended' | 'rejected';
    is_featured: boolean;
    commission_rate: number;
    products_count?: number;
}

export interface VendorSummary {
    id?: number;
    business_name: string;
    slug: string;
    logo?: string | null;
}

// Category types
export interface Category {
    id: number;
    name: string;
    slug: string;
    description?: string;
    image?: string | null;
    icon?: string | null;
    parent_id?: number | null;
    is_active: boolean;
    is_featured: boolean;
    products_count?: number;
    children?: Category[];
}

export interface CategorySummary {
    id?: number;
    name: string;
    slug: string;
}

// Order types
export interface Order {
    id: number;
    order_number: string;
    status: OrderStatus;
    payment_status: PaymentStatus;
    subtotal: number;
    discount_amount: number;
    shipping_amount: number;
    tax_amount: number;
    total: number;
    currency: string;
    shipping_address: Address;
    billing_address?: Address;
    tracking_number?: string;
    items: OrderItem[];
    created_at: string;
    shipped_at?: string;
    delivered_at?: string;
}

export type OrderStatus = 
    | 'pending'
    | 'confirmed'
    | 'processing'
    | 'shipped'
    | 'delivered'
    | 'cancelled'
    | 'refunded'
    | 'partially_refunded';

export type PaymentStatus = 
    | 'pending'
    | 'authorized'
    | 'paid'
    | 'partially_refunded'
    | 'refunded'
    | 'failed';

export interface OrderItem {
    id: number;
    product_name: string;
    variant_name?: string;
    sku: string;
    options?: Record<string, string>;
    quantity: number;
    unit_price: number;
    subtotal: number;
    total: number;
    fulfillment_status: string;
    vendor: VendorSummary;
}

export interface Address {
    first_name: string;
    last_name: string;
    phone?: string;
    address_line_1: string;
    address_line_2?: string;
    city: string;
    state: string;
    postal_code: string;
    country: string;
}

// Cart types
export interface Cart {
    items: CartItem[];
    subtotal: number;
    item_count: number;
    coupon_code?: string | null;
}

export interface CartItem {
    id: number;
    quantity: number;
    product: {
        id: number;
        name: string;
        slug: string;
        primary_image_url?: string | null;
    };
    variant?: {
        id: number;
        display_name: string;
        option_values: Record<string, string>;
    } | null;
    price: number;
    compare_at_price?: number | null;
    subtotal: number;
    is_in_stock: boolean;
    available_stock: number;
}

// Review types
export interface Review {
    id: number;
    customer_name: string;
    rating: number;
    title?: string;
    comment?: string;
    is_verified_purchase: boolean;
    created_at: string;
}

// KYC types
export interface VendorKyc {
    id: number;
    vendor_id: number;
    legal_name: string;
    id_type: 'passport' | 'national_id' | 'drivers_license' | 'business_license';
    status: 'pending' | 'under_review' | 'approved' | 'rejected' | 'expired';
    submitted_at?: string;
    reviewed_at?: string;
    rejection_reason?: string;
    admin_notes?: string;
}

// Pagination types
export interface PaginatedResponse<T> {
    data: T[];
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        path: string;
        per_page: number;
        to: number;
        total: number;
    };
}
