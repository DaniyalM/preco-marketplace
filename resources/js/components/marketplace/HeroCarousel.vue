<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const slides = [
    { id: '1', title: 'Summer Collection 2025', subtitle: 'Discover trending styles and exclusive deals. Free shipping on orders over $49.', ctaText: 'Shop now', ctaHref: '/products', gradient: 'from-rose-600 via-rose-500 to-amber-500' },
    { id: '2', title: 'Best Sellers', subtitle: 'Thousands of customers love these picks. Limited stock on selected items.', ctaText: 'View best sellers', ctaHref: '/products?sort=popularity&order=desc', gradient: 'from-slate-800 via-slate-700 to-slate-900' },
    { id: '3', title: 'New Arrivals', subtitle: 'Fresh drops every week. Be the first to get the latest products.', ctaText: 'Explore new', ctaHref: '/products', gradient: 'from-emerald-700 via-teal-600 to-cyan-700' },
];

const current = ref(0);
let interval: ReturnType<typeof setInterval> | null = null;

function goTo(index: number) {
    current.value = (index + slides.length) % slides.length;
    resetInterval();
}
function next() {
    goTo(current.value + 1);
}
function prev() {
    goTo(current.value - 1);
}
function resetInterval() {
    if (interval) clearInterval(interval);
    interval = setInterval(next, 5500);
}
onMounted(resetInterval);
onUnmounted(() => {
    if (interval) clearInterval(interval);
});
</script>

<template>
    <section class="relative w-full overflow-hidden rounded-2xl bg-muted" aria-label="Promotional carousel">
        <div class="relative aspect-[21/9] min-h-[200px] w-full sm:aspect-[3/1] sm:min-h-[280px] lg:min-h-[320px]">
            <template v-for="(slide, index) in slides" :key="slide.id">
                <div
                    class="absolute inset-0 flex items-center transition-all duration-500 ease-out"
                    :class="index === current ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'"
                >
                    <div :class="['absolute inset-0 bg-gradient-to-br', slide.gradient]" />
                    <div class="relative z-10 mx-auto w-full max-w-7xl px-6 py-12 sm:px-8 sm:py-16 lg:px-12">
                        <div class="max-w-xl">
                            <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">
                                {{ slide.title }}
                            </h2>
                            <p class="mt-3 text-lg text-white/95 sm:mt-4 sm:text-xl">
                                {{ slide.subtitle }}
                            </p>
                            <div class="mt-6 sm:mt-8">
                                <Link
                                    :href="slide.ctaHref"
                                    class="inline-flex items-center rounded-xl bg-background px-6 py-3.5 text-base font-semibold text-foreground shadow-lg hover:bg-background/95 focus:ring-2 focus:ring-background/80"
                                >
                                    {{ slide.ctaText }}
                                    <svg class="ml-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <div class="absolute bottom-4 left-0 right-0 z-20 flex justify-center gap-2 sm:bottom-6">
            <button
                v-for="(slide, index) in slides"
                :key="'dot-' + slide.id"
                type="button"
                class="h-2.5 w-2.5 rounded-full transition-all duration-300 focus:ring-2 focus:ring-white/80"
                :class="index === current ? 'scale-125 bg-background' : 'bg-background/60 hover:bg-background/80'"
                :aria-label="'Go to slide ' + (index + 1)"
                @click="goTo(index)"
            />
        </div>
        <button
            type="button"
            class="absolute left-4 top-1/2 z-20 -translate-y-1/2 rounded-full bg-black/20 p-2.5 text-white backdrop-blur-sm hover:bg-black/40 focus:ring-2 focus:ring-white/80 sm:left-6"
            aria-label="Previous slide"
            @click="prev"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
        <button
            type="button"
            class="absolute right-4 top-1/2 z-20 -translate-y-1/2 rounded-full bg-black/20 p-2.5 text-white backdrop-blur-sm hover:bg-black/40 focus:ring-2 focus:ring-white/80 sm:right-6"
            aria-label="Next slide"
            @click="next"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </section>
</template>
