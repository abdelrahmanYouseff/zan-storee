<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, nextTick } from 'vue';

interface Product {
    id: number;
    name: string;
    category: string;
    price: number;
    originalPrice: number;
    rating: number;
    reviews: number;
    image: string;
    badge: string;
    discount: number;
}

interface Props {
    products: Product[];
}

const props = defineProps<Props>();

const cartCount = ref(3);
const isMobileMenuOpen = ref(false);
const isChatOpen = ref(false);
const messageInput = ref('');
const chatMessages = ref([
    {
        id: 1,
        type: 'bot',
        message: 'Hi! 👋 Welcome to Zan Store. How can I help you today?',
        timestamp: 'Just now'
    }
]);
const isTyping = ref(false);
const chatSessionId = ref<string | null>(null);

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const toggleChat = () => {
    isChatOpen.value = !isChatOpen.value;
    if (isChatOpen.value) {
        nextTick(() => {
            scrollToBottom();
        });
    }
};

// Auto scroll to bottom of chat
const scrollToBottom = () => {
    const chatContainer = document.querySelector('.chat-messages');
    if (chatContainer) {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
};

// Add message to chat
const addMessage = (type: 'bot' | 'user', message: string) => {
    const newMessage = {
        id: chatMessages.value.length + 1,
        type,
        message,
        timestamp: new Date().toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        })
    };
    chatMessages.value.push(newMessage);

    nextTick(() => {
        scrollToBottom();
    });
};

// Send message to server
const sendMessage = async () => {
    if (!messageInput.value.trim()) return;

    const userMessage = messageInput.value.trim();
    addMessage('user', userMessage);
    messageInput.value = '';

    isTyping.value = true;

    try {
        const response = await fetch('/api/chat/customer/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                message: userMessage,
                session_id: chatSessionId.value
            })
        });

        const data = await response.json();

        if (data.success) {
            chatSessionId.value = data.session_id;

            // Add confirmation message
            setTimeout(() => {
                isTyping.value = false;
                addMessage('bot', 'Thank you for your message! Our team will respond shortly. 💬');
            }, 1000);
        }
    } catch (error) {
        console.error('Error sending message:', error);
        isTyping.value = false;
        addMessage('bot', 'Sorry, there was an error sending your message. Please try again.');
    }
};

// Handle enter key
const handleKeyPress = (event: KeyboardEvent) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
};

const features = [
    {
        icon: 'truck',
        title: 'Free Shipping',
        description: 'On orders over $50',
    },
    {
        icon: 'shield',
        title: 'Secure Payment',
        description: '100% secure transactions',
    },
    {
        icon: 'credit-card',
        title: 'Easy Returns',
        description: '30-day return policy',
    },
    {
        icon: 'headphones',
        title: '24/7 Support',
        description: 'Dedicated customer service',
    },
];

// Products will come from props (database)
</script>

<template>
    <Head title="Home - Zan Store" />

    <div class="min-h-screen bg-white">
        <!-- Header -->
        <header class="sticky top-0 z-50 w-full border-b bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/60">
            <div class="container mx-auto px-4">
                <div class="flex h-16 items-center">
                    <!-- Mobile Menu Button -->
                    <button @click="toggleMobileMenu" class="md:hidden inline-flex items-center justify-center rounded-md p-2 hover:bg-gray-100 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <!-- Logo -->
                    <div class="flex items-center gap-3 md:mr-auto">
                        <span class="text-xl font-semibold">Zan Store</span>
                        <img src="/images/partner.png" alt="Partner" class="h-8" />
                    </div>

                    <!-- Navigation - Centered -->
                    <nav class="hidden md:flex items-center gap-6 absolute left-1/2 transform -translate-x-1/2">
                        <a href="#home" class="text-sm font-medium hover:text-purple-600 transition-colors">
                            Home
                        </a>
                        <a href="#products" class="text-sm font-medium hover:text-purple-600 transition-colors">
                            Products
                        </a>
                        <button @click="toggleChat" class="text-sm font-medium hover:text-purple-600 transition-colors">
                            Support
                        </button>
                    </nav>

                </div>

                <!-- Mobile Menu -->
                <div v-if="isMobileMenuOpen" class="md:hidden py-4 border-t">
                    <nav class="flex flex-col gap-3">
                        <a href="#home" class="text-sm font-medium hover:text-purple-600 transition-colors py-2">
                            Home
                        </a>
                        <a href="#products" class="text-sm font-medium hover:text-purple-600 transition-colors py-2">
                            Products
                        </a>
                        <button @click="toggleChat" class="text-sm font-medium hover:text-purple-600 transition-colors py-2 text-left">
                            Support
                        </button>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main>
            <!-- Hero Section -->
            <section class="relative overflow-hidden bg-gradient-to-br from-purple-50 via-blue-50 to-pink-50">
                <div class="container mx-auto px-4 py-20 md:py-32">
                    <div class="grid md:grid-cols-2 gap-12 items-center">
                        <!-- Left: Content -->
                        <div class="order-1 md:order-1 space-y-6">
                            <div class="inline-block">
                                <span class="px-4 py-2 rounded-full bg-purple-100 text-purple-600 text-sm font-semibold">
                                    New Collection 2025
                                </span>
                            </div>
                <h1 class="text-4xl md:text-6xl font-bold leading-tight">
                    50% OFF on All Products — Limited Time Only!
                </h1>

                            <!-- Partner Image -->
                            <div class="mt-6">
                                <img
                                    src="/images/partner.png"
                                    alt="Partner"
                                    class="h-16 md:h-20"
                                />
                            </div>
                        </div>

                        <!-- Right: Image -->
                        <div class="order-2 md:order-2">
                            <div class="relative">
                                <img
                                    src="/images/orange_2.png"
                                    alt="Product"
                                    class="w-full h-auto max-w-md mx-auto"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Featured Products Section -->
            <section class="py-16 bg-gray-50" id="products">
                <div class="container mx-auto px-4">
                    <div class="flex items-center justify-between mb-12">
                        <div>
                            <h2 class="text-3xl md:text-4xl font-bold mb-2">Featured Products</h2>
                            <p class="text-gray-600">Handpicked items just for you</p>
                        </div>
                        <button class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors font-medium">
                            View All
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        <Link
                            v-for="product in props.products"
                            :key="product.id"
                            :href="`/product/${product.id}`"
                            class="group transition-all duration-300"
                        >
                            <!-- Product Image -->
                            <div class="relative aspect-square overflow-hidden bg-gray-50 flex items-center justify-center">
                                <img
                                    :src="product.image"
                                    :alt="product.name"
                                    class="w-3/4 h-3/4 object-contain group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy"
                                />
                                <!-- Badge -->
                                <div class="absolute top-3 left-3">
                                    <span class="bg-gradient-to-r from-orange-500 to-red-500 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
                                        {{ product.badge }}
                                    </span>
                                </div>
                                <!-- Wishlist Button -->
                                <button @click.prevent class="absolute top-3 right-3 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 hover:bg-white shadow-lg">
                                    <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Product Info -->
                            <div class="pt-4 text-center">
                                <!-- Product Name -->
                                <h3 class="text-lg font-bold text-gray-900 mb-2 line-clamp-2 leading-tight">
                                    {{ product.name }}
                                </h3>

                                <!-- Rating -->
                                <div class="flex items-center justify-center gap-2 mb-3">
                                    <div class="flex items-center">
                                        <svg v-for="i in 5" :key="i" class="h-4 w-4" :class="i <= Math.floor(product.rating) ? 'text-orange-400' : (i === Math.ceil(product.rating) && product.rating % 1 !== 0 ? 'text-orange-200' : 'text-gray-200')" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm text-gray-600 font-medium">{{ product.rating }}</span>
                                    <span class="text-sm text-gray-400">({{ product.reviews }})</span>
                                </div>

                                <!-- Price -->
                                <div class="mb-4">
                                    <div class="flex items-baseline justify-center gap-2">
                                        <span class="text-2xl font-bold text-gray-900"><span class="text-sm">$</span>{{ product.price }}</span>
                                        <span class="text-lg text-gray-400 line-through"><span class="text-sm">$</span>{{ product.originalPrice }}</span>
                                    </div>
                                    <div class="text-sm text-green-600 font-semibold">
                                        {{ product.discount }}% off
                                    </div>
                                </div>

                            </div>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section class="py-16 bg-white">
                <div class="container mx-auto px-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div
                            v-for="feature in features"
                            :key="feature.title"
                            class="flex flex-col items-center text-center group"
                        >
                            <div class="h-16 w-16 rounded-full bg-purple-100 flex items-center justify-center mb-4 group-hover:bg-purple-600 transition-colors">
                                <!-- Truck Icon -->
                                <svg v-if="feature.icon === 'truck'" class="h-8 w-8 text-purple-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                </svg>
                                <!-- Shield Icon -->
                                <svg v-else-if="feature.icon === 'shield'" class="h-8 w-8 text-purple-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <!-- Credit Card Icon -->
                                <svg v-else-if="feature.icon === 'credit-card'" class="h-8 w-8 text-purple-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                <!-- Headphones Icon -->
                                <svg v-else-if="feature.icon === 'headphones'" class="h-8 w-8 text-purple-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold mb-2">{{ feature.title }}</h3>
                            <p class="text-sm text-gray-600">{{ feature.description }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-gray-900 text-gray-300">
            <div class="container mx-auto px-4 py-12">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <!-- About -->
                    <div>
                        <div class="mb-4">
                            <span class="text-xl text-white font-semibold block mb-3">Zan Store</span>
                            <img src="/images/apple-white.png" alt="Partner" class="h-16" />
                        </div>
                        <div class="flex gap-4">
                            <a href="#" class="hover:text-white transition-colors">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </a>
                            <a href="#" class="hover:text-white transition-colors">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                            </a>
                            <a href="#" class="hover:text-white transition-colors">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                                </svg>
                            </a>
                            <a href="#" class="hover:text-white transition-colors">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div>
                        <h3 class="text-white font-semibold mb-4">Contact Us</h3>
                        <ul class="space-y-2 text-sm">
                            <li>Email: info@zan-store.com</li>
                            <li>Phone: +1 (555) 123-4567</li>
                            <li>Address: 123 Store Street</li>
                            <li>City, State 12345</li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-gray-800 pt-8 text-center text-sm">
                    <p>&copy; 2025 Zan Store. All rights reserved.</p>
                </div>
            </div>
        </footer>

        <!-- Chatbot Icon -->
        <button
            @click="toggleChat"
            class="fixed bottom-6 right-6 w-14 h-14 bg-black hover:bg-gray-800 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center z-50 group"
        >
            <svg v-if="!isChatOpen" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
            <svg v-else class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full animate-pulse"></span>
        </button>

        <!-- Chat Window -->
        <div
            v-show="isChatOpen"
            class="fixed bottom-24 right-6 w-[450px] max-w-[calc(100vw-3rem)] bg-white rounded-2xl shadow-2xl z-50 overflow-hidden transition-all duration-300 transform"
            :class="isChatOpen ? 'scale-100 opacity-100' : 'scale-95 opacity-0'"
        >
            <!-- Chat Header -->
            <div class="bg-black p-5 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg">Zan Store Support</h3>
                            <p class="text-sm text-white/80">We're here to help!</p>
                        </div>
                    </div>
                    <button @click="toggleChat" class="text-white/80 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Chat Body -->
            <div class="h-[500px] overflow-y-auto p-4 bg-gray-50 chat-messages">
                <div class="space-y-4">
                    <!-- Chat Messages -->
                    <div v-for="msg in chatMessages" :key="msg.id" :class="msg.type === 'bot' ? 'flex items-start gap-3' : 'flex items-start gap-3 justify-end'">
                        <div v-if="msg.type === 'bot'" class="w-10 h-10 bg-gradient-to-br from-purple-600 to-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                        </div>
                        <div :class="msg.type === 'bot' ? 'bg-white rounded-2xl rounded-tl-none p-4 shadow-sm max-w-[80%]' : 'bg-gradient-to-br from-purple-600 to-blue-500 text-white rounded-2xl rounded-tr-none p-4 shadow-sm max-w-[80%]'">
                            <p class="text-base" :class="msg.type === 'bot' ? 'text-gray-800' : 'text-white'">{{ msg.message }}</p>
                            <span class="text-xs mt-1 block" :class="msg.type === 'bot' ? 'text-gray-500' : 'text-white/80'">{{ msg.timestamp }}</span>
                        </div>
                    </div>

                    <!-- Typing Indicator -->
                    <div v-if="isTyping" class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                        </div>
                        <div class="bg-white rounded-2xl rounded-tl-none p-4 shadow-sm">
                            <div class="flex gap-1">
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Input -->
            <div class="p-5 bg-white border-t border-gray-200">
                <div class="flex items-center gap-3">
                    <input
                        v-model="messageInput"
                        @keypress="handleKeyPress"
                        type="text"
                        placeholder="Type your message..."
                        class="flex-1 px-5 py-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-purple-600 text-base"
                    />
                    <button @click="sendMessage" class="w-12 h-12 bg-gradient-to-br from-purple-600 to-blue-500 hover:from-purple-700 hover:to-blue-600 text-white rounded-full flex items-center justify-center transition-all flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Smooth transitions */
* {
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* Force consistent fonts and colors across all devices */
* {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
}

/* Ensure text colors are consistent */
.text-gray-900 {
    color: #111827 !important;
}

.text-gray-600 {
    color: #4b5563 !important;
}

.text-gray-400 {
    color: #9ca3af !important;
}

.text-white {
    color: #ffffff !important;
}

.text-purple-600 {
    color: #9333ea !important;
}

.text-green-600 {
    color: #059669 !important;
}

.text-orange-400 {
    color: #fb923c !important;
}

.text-orange-200 {
    color: #fed7aa !important;
}

/* Ensure font weights are consistent */
.font-semibold {
    font-weight: 600 !important;
}

.font-bold {
    font-weight: 700 !important;
}

.font-medium {
    font-weight: 500 !important;
}

/* Ensure font sizes are consistent */
.text-xs {
    font-size: 0.75rem !important;
    line-height: 1rem !important;
}

.text-sm {
    font-size: 0.875rem !important;
    line-height: 1.25rem !important;
}

.text-lg {
    font-size: 1.125rem !important;
    line-height: 1.75rem !important;
}

.text-xl {
    font-size: 1.25rem !important;
    line-height: 1.75rem !important;
}

.text-2xl {
    font-size: 1.5rem !important;
    line-height: 2rem !important;
}

.text-3xl {
    font-size: 1.875rem !important;
    line-height: 2.25rem !important;
}

.text-4xl {
    font-size: 2.25rem !important;
    line-height: 2.5rem !important;
}

.text-6xl {
    font-size: 3.75rem !important;
    line-height: 1 !important;
}

/* Mobile specific adjustments */
@media (max-width: 768px) {
    .text-4xl {
        font-size: 2rem !important;
        line-height: 2.25rem !important;
    }

    .text-6xl {
        font-size: 2.5rem !important;
        line-height: 2.75rem !important;
    }

    .text-3xl {
        font-size: 1.5rem !important;
        line-height: 2rem !important;
    }

    .text-2xl {
        font-size: 1.25rem !important;
        line-height: 1.75rem !important;
    }

    /* Force black color for logo and menu button on mobile */
    .text-xl {
        color: #000000 !important;
    }

    /* Force black color for main title on mobile */
    .text-4xl {
        color: #000000 !important;
    }

    .text-6xl {
        color: #000000 !important;
    }

    /* Force black color for Featured Products section on mobile */
    .text-3xl {
        color: #000000 !important;
    }

    /* Force black color for View All button text on mobile */
    .font-medium {
        color: #000000 !important;
    }

    /* Force black color for features section text on mobile */
    .text-gray-900 {
        color: #000000 !important;
    }

    .text-gray-600 {
        color: #000000 !important;
    }

    /* Force white color for footer Zan Store text on mobile */
    .text-white {
        color: #ffffff !important;
    }

    /* Force red color for crossed out price (original price) */
    .line-through {
        color: #ef4444 !important;
    }

    /* Force black color for hamburger menu button on mobile */
    .h-5.w-5 {
        color: #000000 !important;
    }

    /* Force white color for social media icons in footer on mobile */
    .h-5.w-5 svg {
        color: #ffffff !important;
    }

    /* Force black color for features text on mobile */
    .text-center h3 {
        color: #000000 !important;
    }

    .text-center p {
        color: #000000 !important;
    }
}
</style>

