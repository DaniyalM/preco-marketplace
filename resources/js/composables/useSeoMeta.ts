import { Head } from '@inertiajs/vue3';
import { computed, defineComponent, h, type PropType } from 'vue';

export interface SeoMeta {
    title: string;
    description?: string;
    keywords?: string[];
    image?: string;
    url?: string;
    type?: 'website' | 'product' | 'article';
    price?: {
        amount: number;
        currency: string;
    };
    availability?: 'in stock' | 'out of stock' | 'preorder';
    brand?: string;
    category?: string;
    rating?: {
        value: number;
        count: number;
    };
    noindex?: boolean;
}

/**
 * Composable for generating SEO meta tags
 */
export function useSeoMeta(meta: SeoMeta) {
    const appName = import.meta.env.VITE_APP_NAME || 'P-Commerce';
    const baseUrl = typeof window !== 'undefined' ? window.location.origin : '';

    const fullTitle = computed(() => 
        meta.title ? `${meta.title} - ${appName}` : appName
    );

    const fullUrl = computed(() => 
        meta.url || (typeof window !== 'undefined' ? window.location.href : '')
    );

    const imageUrl = computed(() => {
        if (!meta.image) return null;
        return meta.image.startsWith('http') ? meta.image : `${baseUrl}${meta.image}`;
    });

    return {
        title: fullTitle,
        description: meta.description || '',
        url: fullUrl,
        image: imageUrl,
        type: meta.type || 'website',
    };
}

/**
 * SEO Head component for use in page components
 */
export const SeoHead = defineComponent({
    name: 'SeoHead',
    props: {
        title: {
            type: String,
            required: true,
        },
        description: {
            type: String,
            default: '',
        },
        keywords: {
            type: Array as PropType<string[]>,
            default: () => [],
        },
        image: {
            type: String,
            default: '',
        },
        url: {
            type: String,
            default: '',
        },
        type: {
            type: String as PropType<'website' | 'product' | 'article'>,
            default: 'website',
        },
        price: {
            type: Object as PropType<{ amount: number; currency: string }>,
            default: null,
        },
        availability: {
            type: String as PropType<'in stock' | 'out of stock' | 'preorder'>,
            default: null,
        },
        brand: {
            type: String,
            default: '',
        },
        category: {
            type: String,
            default: '',
        },
        rating: {
            type: Object as PropType<{ value: number; count: number }>,
            default: null,
        },
        noindex: {
            type: Boolean,
            default: false,
        },
    },
    setup(props) {
        const appName = import.meta.env.VITE_APP_NAME || 'P-Commerce';

        return () => {
            const children = [
                // Basic meta
                h('meta', { name: 'description', content: props.description }),
                
                // Open Graph
                h('meta', { property: 'og:title', content: props.title }),
                h('meta', { property: 'og:description', content: props.description }),
                h('meta', { property: 'og:type', content: props.type }),
                h('meta', { property: 'og:site_name', content: appName }),
                
                // Twitter Card
                h('meta', { name: 'twitter:card', content: props.image ? 'summary_large_image' : 'summary' }),
                h('meta', { name: 'twitter:title', content: props.title }),
                h('meta', { name: 'twitter:description', content: props.description }),
            ];

            // Keywords
            if (props.keywords.length > 0) {
                children.push(h('meta', { name: 'keywords', content: props.keywords.join(', ') }));
            }

            // URL
            if (props.url) {
                children.push(h('meta', { property: 'og:url', content: props.url }));
                children.push(h('link', { rel: 'canonical', href: props.url }));
            }

            // Image
            if (props.image) {
                children.push(h('meta', { property: 'og:image', content: props.image }));
                children.push(h('meta', { name: 'twitter:image', content: props.image }));
            }

            // Product-specific meta (Schema.org via JSON-LD is better, but OG works too)
            if (props.type === 'product') {
                if (props.price) {
                    children.push(h('meta', { property: 'product:price:amount', content: String(props.price.amount) }));
                    children.push(h('meta', { property: 'product:price:currency', content: props.price.currency }));
                }
                if (props.availability) {
                    children.push(h('meta', { property: 'product:availability', content: props.availability }));
                }
                if (props.brand) {
                    children.push(h('meta', { property: 'product:brand', content: props.brand }));
                }
                if (props.category) {
                    children.push(h('meta', { property: 'product:category', content: props.category }));
                }
            }

            // Noindex for non-public pages
            if (props.noindex) {
                children.push(h('meta', { name: 'robots', content: 'noindex, nofollow' }));
            }

            return h(Head, { title: props.title }, () => children);
        };
    },
});

/**
 * Generate JSON-LD structured data for products
 */
export function generateProductJsonLd(product: {
    name: string;
    description: string;
    image: string | string[];
    price: number;
    currency?: string;
    availability?: 'InStock' | 'OutOfStock' | 'PreOrder';
    brand?: string;
    sku?: string;
    rating?: { value: number; count: number };
    url?: string;
}): string {
    const jsonLd = {
        '@context': 'https://schema.org',
        '@type': 'Product',
        name: product.name,
        description: product.description,
        image: Array.isArray(product.image) ? product.image : [product.image],
        sku: product.sku,
        brand: product.brand ? {
            '@type': 'Brand',
            name: product.brand,
        } : undefined,
        offers: {
            '@type': 'Offer',
            price: product.price,
            priceCurrency: product.currency || 'USD',
            availability: `https://schema.org/${product.availability || 'InStock'}`,
            url: product.url,
        },
        aggregateRating: product.rating ? {
            '@type': 'AggregateRating',
            ratingValue: product.rating.value,
            reviewCount: product.rating.count,
        } : undefined,
    };

    // Remove undefined values
    const cleanJsonLd = JSON.parse(JSON.stringify(jsonLd));
    
    return JSON.stringify(cleanJsonLd);
}

/**
 * Generate JSON-LD structured data for organization
 */
export function generateOrganizationJsonLd(org: {
    name: string;
    url: string;
    logo?: string;
    description?: string;
}): string {
    return JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Organization',
        name: org.name,
        url: org.url,
        logo: org.logo,
        description: org.description,
    });
}

/**
 * Generate JSON-LD structured data for breadcrumbs
 */
export function generateBreadcrumbJsonLd(
    items: Array<{ name: string; url: string }>
): string {
    return JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: items.map((item, index) => ({
            '@type': 'ListItem',
            position: index + 1,
            name: item.name,
            item: item.url,
        })),
    });
}
