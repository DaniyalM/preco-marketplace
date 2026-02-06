<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed demo categories, vendors, and products.
     */
    public function run(): void
    {
        $this->command->info('Seeding demo data...');

        // Create categories
        $categories = $this->createCategories();
        $this->command->info('Created ' . count($categories) . ' categories');

        // Create demo vendor
        $vendor = $this->createDemoVendor();
        $this->command->info('Created demo vendor: ' . $vendor->business_name);

        // Create products
        $products = $this->createProducts($vendor, $categories);
        $this->command->info('Created ' . count($products) . ' products');

        // Ensure any product with 0 stock gets stock (e.g. from old seed or default)
        $updated = Product::where('stock_quantity', 0)->update(['stock_quantity' => 50]);
        if ($updated > 0) {
            $this->command->info("Set stock to 50 for {$updated} product(s) that had 0.");
        }

        $this->command->info('Demo data seeding complete!');
    }

    /**
     * Create categories with subcategories
     */
    private function createCategories(): array
    {
        $categoriesData = [
            [
                'name' => 'Electronics',
                'description' => 'Phones, laptops, gadgets and more',
                'icon' => 'laptop',
                'is_featured' => true,
                'children' => [
                    ['name' => 'Smartphones', 'description' => 'Latest smartphones and accessories'],
                    ['name' => 'Laptops', 'description' => 'Notebooks and laptops for work and gaming'],
                    ['name' => 'Tablets', 'description' => 'Tablets and e-readers'],
                    ['name' => 'Accessories', 'description' => 'Chargers, cables, cases and more'],
                ],
            ],
            [
                'name' => 'Fashion',
                'description' => 'Clothing, shoes, and accessories for all',
                'icon' => 'shirt',
                'is_featured' => true,
                'children' => [
                    ['name' => 'Men\'s Clothing', 'description' => 'Shirts, pants, suits and more'],
                    ['name' => 'Women\'s Clothing', 'description' => 'Dresses, tops, and bottoms'],
                    ['name' => 'Shoes', 'description' => 'Footwear for all occasions'],
                    ['name' => 'Bags & Accessories', 'description' => 'Handbags, wallets, and accessories'],
                ],
            ],
            [
                'name' => 'Home & Garden',
                'description' => 'Furniture, decor, and garden supplies',
                'icon' => 'home',
                'is_featured' => true,
                'children' => [
                    ['name' => 'Furniture', 'description' => 'Sofas, beds, tables and chairs'],
                    ['name' => 'Kitchen', 'description' => 'Cookware, appliances, and utensils'],
                    ['name' => 'Decor', 'description' => 'Wall art, lighting, and decorations'],
                    ['name' => 'Garden', 'description' => 'Plants, tools, and outdoor furniture'],
                ],
            ],
            [
                'name' => 'Sports & Outdoors',
                'description' => 'Sports equipment and outdoor gear',
                'icon' => 'dumbbell',
                'is_featured' => false,
                'children' => [
                    ['name' => 'Fitness', 'description' => 'Gym equipment and workout gear'],
                    ['name' => 'Outdoor Recreation', 'description' => 'Camping, hiking, and adventure gear'],
                    ['name' => 'Team Sports', 'description' => 'Equipment for football, basketball, and more'],
                ],
            ],
            [
                'name' => 'Books & Media',
                'description' => 'Books, music, movies, and games',
                'icon' => 'book',
                'is_featured' => false,
                'children' => [
                    ['name' => 'Books', 'description' => 'Fiction, non-fiction, and educational'],
                    ['name' => 'Music', 'description' => 'CDs, vinyl, and instruments'],
                    ['name' => 'Movies & TV', 'description' => 'DVDs, Blu-ray, and streaming devices'],
                    ['name' => 'Video Games', 'description' => 'Games and gaming consoles'],
                ],
            ],
            [
                'name' => 'Health & Beauty',
                'description' => 'Personal care, cosmetics, and wellness',
                'icon' => 'heart',
                'is_featured' => true,
                'children' => [
                    ['name' => 'Skincare', 'description' => 'Face and body skincare products'],
                    ['name' => 'Makeup', 'description' => 'Cosmetics and beauty tools'],
                    ['name' => 'Hair Care', 'description' => 'Shampoos, styling, and treatments'],
                    ['name' => 'Wellness', 'description' => 'Vitamins, supplements, and health products'],
                ],
            ],
        ];

        $categories = [];
        $sortOrder = 1;

        foreach ($categoriesData as $data) {
            $children = $data['children'] ?? [];
            unset($data['children']);

            $category = Category::create([
                ...$data,
                'sort_order' => $sortOrder++,
                'is_active' => true,
            ]);
            $categories[] = $category;

            // Create children
            $childOrder = 1;
            foreach ($children as $childData) {
                $child = Category::create([
                    ...$childData,
                    'parent_id' => $category->id,
                    'sort_order' => $childOrder++,
                    'is_active' => true,
                ]);
                $categories[] = $child;
            }
        }

        return $categories;
    }

    /**
     * Create a demo vendor
     */
    private function createDemoVendor(): Vendor
    {
        return Vendor::create([
            'keycloak_user_id' => 'demo-vendor-' . Str::uuid(),
            'email' => 'demo-vendor@example.com',
            'business_name' => 'Demo Store',
            'slug' => 'demo-store',
            'business_type' => 'retail',
            'phone' => '+1234567890',
            'description' => 'Welcome to Demo Store! We offer high-quality products at competitive prices. Shop with confidence knowing all our products are carefully curated.',
            'address_line_1' => '123 Commerce Street',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'US',
            'status' => 'approved',
            'approved_at' => now(),
            'commission_rate' => 10.00,
            'is_featured' => true,
        ]);
    }

    /**
     * Create demo products
     */
    private function createProducts(Vendor $vendor, array $categories): array
    {
        // Get leaf categories (those with no children)
        $leafCategories = collect($categories)->filter(function ($cat) {
            return $cat->parent_id !== null;
        })->values();

        $productsData = [
            // Electronics - Smartphones
            [
                'name' => 'Premium Smartphone Pro Max',
                'category' => 'Smartphones',
                'short_description' => 'Latest flagship smartphone with advanced camera system',
                'description' => '<p>Experience the future of mobile technology with our Premium Smartphone Pro Max. Featuring a stunning 6.7-inch OLED display, A17 chip for blazing performance, and a revolutionary camera system.</p><ul><li>6.7" Super Retina XDR display</li><li>48MP main camera with 5x optical zoom</li><li>All-day battery life</li><li>5G connectivity</li></ul>',
                'base_price' => 999.99,
                'compare_at_price' => 1199.99,
                'stock_quantity' => 50,
            ],
            [
                'name' => 'Budget Smartphone SE',
                'category' => 'Smartphones',
                'short_description' => 'Powerful features at an affordable price',
                'description' => '<p>Get flagship performance without breaking the bank. The Budget Smartphone SE offers the perfect balance of features and value.</p>',
                'base_price' => 429.99,
                'compare_at_price' => null,
                'stock_quantity' => 100,
            ],
            // Electronics - Laptops
            [
                'name' => 'Professional Laptop 16"',
                'category' => 'Laptops',
                'short_description' => 'Powerful laptop for creative professionals',
                'description' => '<p>Unleash your creativity with the Professional Laptop. Featuring M3 Pro chip, 18 hours of battery life, and a stunning Liquid Retina XDR display.</p>',
                'base_price' => 2499.99,
                'compare_at_price' => null,
                'stock_quantity' => 25,
            ],
            [
                'name' => 'Gaming Laptop RTX',
                'category' => 'Laptops',
                'short_description' => 'High-performance gaming laptop with RTX graphics',
                'description' => '<p>Dominate the competition with the Gaming Laptop RTX. Powered by Intel Core i9 and NVIDIA RTX 4080 graphics for ultimate gaming performance.</p>',
                'base_price' => 1899.99,
                'compare_at_price' => 2199.99,
                'stock_quantity' => 30,
            ],
            // Fashion - Men's
            [
                'name' => 'Classic Cotton Oxford Shirt',
                'category' => 'Men\'s Clothing',
                'short_description' => 'Timeless oxford shirt in premium cotton',
                'description' => '<p>A wardrobe essential. This classic oxford shirt is crafted from 100% premium cotton for comfort and durability. Perfect for both casual and formal occasions.</p>',
                'base_price' => 79.99,
                'compare_at_price' => null,
                'stock_quantity' => 200,
            ],
            [
                'name' => 'Slim Fit Chino Pants',
                'category' => 'Men\'s Clothing',
                'short_description' => 'Modern slim fit chinos in stretch cotton',
                'description' => '<p>These versatile chinos feature a modern slim fit with just the right amount of stretch for all-day comfort.</p>',
                'base_price' => 89.99,
                'compare_at_price' => 109.99,
                'stock_quantity' => 150,
            ],
            // Fashion - Women's
            [
                'name' => 'Elegant Midi Dress',
                'category' => 'Women\'s Clothing',
                'short_description' => 'Flowing midi dress perfect for any occasion',
                'description' => '<p>Turn heads in this stunning midi dress. Features a flattering silhouette, soft fabric, and elegant details that make it perfect for work or evening events.</p>',
                'base_price' => 129.99,
                'compare_at_price' => 159.99,
                'stock_quantity' => 75,
            ],
            // Home - Furniture
            [
                'name' => 'Modern Sectional Sofa',
                'category' => 'Furniture',
                'short_description' => 'Spacious L-shaped sectional for modern living',
                'description' => '<p>Transform your living space with this contemporary sectional sofa. Features premium upholstery, deep seating, and modular design.</p>',
                'base_price' => 1499.99,
                'compare_at_price' => 1899.99,
                'stock_quantity' => 10,
            ],
            [
                'name' => 'Minimalist Coffee Table',
                'category' => 'Furniture',
                'short_description' => 'Sleek coffee table with hidden storage',
                'description' => '<p>This minimalist coffee table combines form and function with its clean lines and clever hidden storage compartment.</p>',
                'base_price' => 299.99,
                'compare_at_price' => null,
                'stock_quantity' => 40,
            ],
            // Kitchen
            [
                'name' => 'Smart Air Fryer XL',
                'category' => 'Kitchen',
                'short_description' => 'Large capacity air fryer with smart controls',
                'description' => '<p>Cook healthier meals with up to 75% less fat. Features WiFi connectivity, voice control, and 10 preset cooking programs.</p>',
                'base_price' => 149.99,
                'compare_at_price' => 199.99,
                'stock_quantity' => 80,
            ],
            // Health & Beauty
            [
                'name' => 'Vitamin C Brightening Serum',
                'category' => 'Skincare',
                'short_description' => 'Powerful antioxidant serum for radiant skin',
                'description' => '<p>This potent vitamin C serum helps brighten skin, reduce dark spots, and protect against environmental damage. Suitable for all skin types.</p>',
                'base_price' => 49.99,
                'compare_at_price' => null,
                'stock_quantity' => 120,
            ],
            [
                'name' => 'Hydrating Face Moisturizer',
                'category' => 'Skincare',
                'short_description' => '24-hour hydration with hyaluronic acid',
                'description' => '<p>Deeply hydrating moisturizer with hyaluronic acid and ceramides. Provides long-lasting moisture without feeling heavy.</p>',
                'base_price' => 34.99,
                'compare_at_price' => 44.99,
                'stock_quantity' => 150,
            ],
            // Sports
            [
                'name' => 'Premium Yoga Mat',
                'category' => 'Fitness',
                'short_description' => 'Non-slip eco-friendly yoga mat',
                'description' => '<p>Practice with confidence on this premium yoga mat. Made from eco-friendly materials with superior grip and cushioning.</p>',
                'base_price' => 68.99,
                'compare_at_price' => null,
                'stock_quantity' => 100,
            ],
            [
                'name' => 'Adjustable Dumbbell Set',
                'category' => 'Fitness',
                'short_description' => 'Space-saving adjustable dumbbells 5-50 lbs',
                'description' => '<p>Replace 15 sets of weights with one adjustable set. Quick-change mechanism allows you to switch weights in seconds.</p>',
                'base_price' => 349.99,
                'compare_at_price' => 449.99,
                'stock_quantity' => 35,
            ],
            // Books
            [
                'name' => 'The Art of Programming',
                'category' => 'Books',
                'short_description' => 'Comprehensive guide to software development',
                'description' => '<p>Master the art of programming with this comprehensive guide. Covers fundamentals, best practices, and advanced techniques used by industry professionals.</p>',
                'base_price' => 59.99,
                'compare_at_price' => null,
                'stock_quantity' => 200,
            ],
        ];

        $products = [];

        foreach ($productsData as $data) {
            $categoryName = $data['category'];
            unset($data['category']);

            $category = $leafCategories->first(function ($cat) use ($categoryName) {
                return $cat->name === $categoryName;
            });

            if (!$category) {
                continue;
            }

            $product = Product::create([
                ...$data,
                'vendor_id' => $vendor->id,
                'category_id' => $category->id,
                'track_inventory' => true,
                'low_stock_threshold' => 10,
                'status' => 'active',
                'is_featured' => rand(0, 1) === 1,
                'published_at' => now()->subDays(rand(1, 30)),
                'stock_quantity' => ($data['stock_quantity'] ?? 0) > 0 ? (int) $data['stock_quantity'] : 50,
            ]);

            // Dummy product image (picsum.photos – deterministic per product for consistent visuals)
            $imageSeed = 'product-' . $product->id . '-' . $product->slug;
            ProductImage::create([
                'product_id' => $product->id,
                'path' => 'https://picsum.photos/seed/' . md5($imageSeed) . '/600/600',
                'alt_text' => $product->name,
                'is_primary' => true,
                'sort_order' => 1,
            ]);

            $products[] = $product;
        }

        return $products;
    }
}
