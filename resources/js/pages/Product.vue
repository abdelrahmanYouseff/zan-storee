<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';

// Define props
const props = defineProps<{
    product?: {
        id: number;
        name: string;
        description: string;
        mainImage: string;
        secondaryImages: string[];
        features: string[];
        colors: string[];
        quantity: number;
        priceBefore: number;
        priceAfter: number;
        discount: number;
    };
}>();

// PayPal types
declare global {
    interface Window {
        paypal: any;
    }
}

const selectedImage = ref(0);
const quantity = ref(1);
const isChatOpen = ref(false);
const isMobileMenuOpen = ref(false);
const cartCount = ref(3);

// Payment method for product page
const selectedPaymentMethod = ref('pay_now');

// Currency conversion
const userCurrency = ref('USD');
const exchangeRates = ref({
    USD: 1,
    SAR: 3.75,
    EGP: 30.85,
    AED: 3.67,
    EUR: 0.92,
    GBP: 0.79,
    JPY: 149.50,
    CAD: 1.35,
    AUD: 1.52
});

const currencySymbols = {
    USD: '$',
    SAR: 'ر.س',
    EGP: 'ج.م',
    AED: 'د.إ',
    EUR: '€',
    GBP: '£',
    JPY: '¥',
    CAD: 'C$',
    AUD: 'A$'
};

// Color mapping to hex codes
const getColorCode = (colorName: string) => {
    const colorMap: { [key: string]: string } = {
        'Orange': '#fa8b4b',
        'Gray': '#4a4d5e',
        'Grey': '#4a4d5e',
        'White': '#f5f5f5',
        'Black': '#1d1d1f',
        'Blue': '#0071e3',
        'Red': '#ff3b30',
        'Green': '#34c759',
        'Yellow': '#ffcc00',
        'Purple': '#9b59b6',
        'Pink': '#ff2d55',
        'Deep Purple': '#5e3c99',
        'Gold': '#ffd700',
        'Silver': '#c0c0c0',
        'Space Gray': '#535150',
        'Midnight': '#1d1d1f',
        'Starlight': '#f5f5f0',
        'Rose Gold': '#b76e79'
    };

    // If color name matches a key, return the hex code
    if (colorMap[colorName]) {
        return colorMap[colorName];
    }

    // If it's already a hex color, return it
    if (colorName.startsWith('#')) {
        return colorName;
    }

    // Default to a random color based on the name
    return '#' + colorName.split('').reduce((acc, char) => {
        return (acc + char.charCodeAt(0)).toString(16).slice(-6).padStart(6, '0');
    }, '').slice(0, 6);
};

// Color selection functionality
const selectColor = (colorName: string) => {
    // For now, just select the first image when color is clicked
    // You can enhance this to switch between different color images
    selectedImage.value = 0;
};

// Quantity functionality
const increaseQuantity = () => {
    quantity.value++;
};

const decreaseQuantity = () => {
    if (quantity.value > 1) {
        quantity.value--;
    }
};

// Currency conversion functions
const convertPrice = (priceUSD: number) => {
    const rate = exchangeRates.value[userCurrency.value as keyof typeof exchangeRates.value];
    return Math.round(priceUSD * rate);
};

const formatPrice = (price: number) => {
    const symbol = currencySymbols[userCurrency.value as keyof typeof currencySymbols];
    return `${symbol}${price.toLocaleString()}`;
};

// Computed total price
const totalPrice = computed(() => {
    return convertPrice(productData.value.priceAfter * quantity.value);
});

const oldTotalPrice = computed(() => {
    return convertPrice(productData.value.priceBefore * quantity.value);
});

const newPriceConverted = computed(() => {
    return convertPrice(productData.value.priceAfter);
});

const oldPriceConverted = computed(() => {
    return convertPrice(productData.value.priceBefore);
});

// Detect user's country and set currency
const detectUserCurrency = async () => {
    try {
        // Method 1: Try multiple APIs for better reliability
        let countryCode = null;

        // Try ipapi.co first
        try {
            const response1 = await fetch('https://ipapi.co/json/', { timeout: 5000 });
            const data1 = await response1.json();
            if (data1.country_code) {
                countryCode = data1.country_code;
                console.log('Detected country via ipapi.co:', countryCode);
            }
        } catch (e) {
            console.log('ipapi.co failed, trying alternative...');
        }

        // Try ip-api.com as backup
        if (!countryCode) {
            try {
                const response2 = await fetch('http://ip-api.com/json/', { timeout: 5000 });
                const data2 = await response2.json();
                if (data2.countryCode) {
                    countryCode = data2.countryCode;
                    console.log('Detected country via ip-api.com:', countryCode);
                }
            } catch (e) {
                console.log('ip-api.com failed, trying browser language...');
            }
        }

        // Method 2: Use browser language as fallback
        if (!countryCode) {
            const browserLang = navigator.language || navigator.languages[0];
            console.log('Browser language:', browserLang);

            if (browserLang.includes('ar-SA') || browserLang.includes('ar')) {
                countryCode = 'SA'; // Default to Saudi Arabia for Arabic
            } else if (browserLang.includes('en-US') || browserLang.includes('en')) {
                countryCode = 'US'; // Default to US for English
            } else if (browserLang.includes('de')) {
                countryCode = 'DE';
            } else if (browserLang.includes('fr')) {
                countryCode = 'FR';
            } else if (browserLang.includes('es')) {
                countryCode = 'ES';
            } else if (browserLang.includes('ja')) {
                countryCode = 'JP';
            } else {
                countryCode = 'US'; // Default fallback
            }
            console.log('Using browser language fallback:', countryCode);
        }

        const countryToCurrency: { [key: string]: string } = {
            'SA': 'SAR', // Saudi Arabia
            'EG': 'EGP', // Egypt
            'AE': 'AED', // UAE
            'US': 'USD', // United States
            'GB': 'GBP', // United Kingdom
            'DE': 'EUR', // Germany
            'FR': 'EUR', // France
            'IT': 'EUR', // Italy
            'ES': 'EUR', // Spain
            'JP': 'JPY', // Japan
            'CA': 'CAD', // Canada
            'AU': 'AUD', // Australia
        };

        const currency = countryToCurrency[countryCode] || 'USD';
        userCurrency.value = currency;
        localStorage.setItem('userCurrency', currency);
        localStorage.setItem('detectedCountry', countryCode);
        console.log('Final currency set:', currency, 'from country:', countryCode);

    } catch (error) {
        console.log('All detection methods failed, using USD');
        userCurrency.value = 'USD';
    }
};

// Manual currency selection for testing
const setCurrency = (currency: string) => {
    userCurrency.value = currency;
    localStorage.setItem('userCurrency', currency);
    console.log('Currency changed to:', currency);
};

// Chat functionality
const toggleChat = () => {
    isChatOpen.value = !isChatOpen.value;

    // Auto scroll to bottom when chat opens
    if (isChatOpen.value) {
        nextTick(() => {
            scrollToBottom();
        });
    }
};

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

// Chat messages state
const chatMessages = ref([
    {
        id: 1,
        type: 'bot',
        message: 'Hi! 👋 How can I help you today?',
        timestamp: 'Just now'
    }
]);

// Track if user is in email input mode
const isWaitingForEmail = ref(false);
const messageInput = ref('');
const isTyping = ref(false);

// Track if admin has responded
const hasAdminResponded = ref(false);
const hasShownWelcomeMessage = ref(false);

// Generate or get session ID
const getSessionId = () => {
    let sessionId = sessionStorage.getItem('chat_session_id');
    if (!sessionId) {
        sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        sessionStorage.setItem('chat_session_id', sessionId);
    }
    return sessionId;
};

// Send message to database
const sendMessageToDatabase = async (message: string, senderType: 'customer' | 'admin') => {
    try {
        const response = await fetch('/api/chat/customer/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                session_id: getSessionId(),
                message: message,
                customer_email: localStorage.getItem('customer_email') || null
            })
        });

        const data = await response.json();
        console.log('Message saved to database:', data);

        // Check for admin response
        if (data.admin_response) {
            setTimeout(() => {
                addMessageWithTyping('bot', data.admin_response.message, 1000);
            }, 500);
        }
    } catch (error) {
        console.error('Error sending message to database:', error);
    }
};

// Poll for admin responses
const pollForAdminResponses = async () => {
    try {
        const response = await fetch(`/api/chat/${getSessionId()}/admin-responses`);
        const data = await response.json();

        if (data.success && data.messages.length > 0) {
            // Mark that admin has responded
            hasAdminResponded.value = true;

            data.messages.forEach((msg: any) => {
                addMessageWithTyping('bot', msg.message, 500);
            });
        }
    } catch (error) {
        console.error('Error polling for admin responses:', error);
    }
};

// Start polling for admin responses
let adminPollingInterval: any = null;
const startAdminPolling = () => {
    adminPollingInterval = setInterval(() => {
        pollForAdminResponses();
    }, 3000); // Poll every 3 seconds
};

const stopAdminPolling = () => {
    if (adminPollingInterval) {
        clearInterval(adminPollingInterval);
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

    // Auto scroll to bottom after adding message
    nextTick(() => {
        scrollToBottom();
    });
};

// Show typing indicator
const showTypingIndicator = () => {
    isTyping.value = true;
    nextTick(() => {
        scrollToBottom();
    });
};

// Hide typing indicator
const hideTypingIndicator = () => {
    isTyping.value = false;
};

// Add message with typing indicator
const addMessageWithTyping = (type: 'bot' | 'user', message: string, delay: number = 1000) => {
    if (type === 'bot') {
        showTypingIndicator();
        setTimeout(() => {
            hideTypingIndicator();
            addMessage(type, message);
        }, delay);
    } else {
        addMessage(type, message);
    }
};

// Auto scroll to bottom of chat
const scrollToBottom = () => {
    const chatContainer = document.querySelector('.chat-messages');
    if (chatContainer) {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
};

// Search for order by email
const searchOrderByEmail = async (email: string) => {
    try {
        const response = await fetch('/api/orders/search', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ email })
        });

        const data = await response.json();
        console.log('Order search response:', data);
        return data;
    } catch (error) {
        console.error('Error searching for order:', error);
        return null;
    }
};

// Calculate delivery status
const calculateDeliveryStatus = (orderDate: string) => {
    const order = new Date(orderDate);
    const now = new Date();
    // Add 21 days to order date for expected delivery
    const deliveryDate = new Date(order.getTime() + (21 * 24 * 60 * 60 * 1000));

    // Calculate days remaining from now until delivery
    const daysRemaining = Math.ceil((deliveryDate.getTime() - now.getTime()) / (24 * 60 * 60 * 1000));

    // Format delivery date in a nice readable format
    const formattedDeliveryDate = deliveryDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    // Format order date
    const formattedOrderDate = order.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    return {
        daysRemaining: daysRemaining > 0 ? daysRemaining : 0,
        isDelivered: daysRemaining <= 0,
        deliveryDate: formattedDeliveryDate,
        orderDate: formattedOrderDate
    };
};

// Handle quick action clicks
const handleQuickAction = (action: string) => {
    let response = '';

    switch(action) {
        case 'delivery':
            response = 'Thank you for your question! 📦 Your order will be delivered within 21 days from the date of purchase. You will receive a tracking number via email once your order ships.';
            break;
        case 'shipment':
            response = 'I\'d be happy to help you track your shipment! 📍 To provide you with the most accurate information, could you please share your email address? This will allow me to look up your order details and provide you with the current status of your package.';
            isWaitingForEmail.value = true;
            break;
        case 'specifications':
            response = 'Here are the key specifications of the iPhone 17 Pro Max: 📱\n\n• 6.9-inch ProMotion display\n• A19 Pro chip with enhanced performance\n• Pro Fusion Camera System\n• Up to 40 hours of battery life\n• 5G connectivity\n• iOS 18 with advanced features\n\nWould you like to know more about any specific feature?';
            break;
        case 'payment':
            response = 'We accept secure payments through PayPal! 💳 Our checkout process is encrypted and secure. You can pay with your PayPal account, credit card, or debit card. All transactions are protected by PayPal\'s security measures. Do you have any specific payment questions?';
            break;
    }

    // Add user message
    addMessage('user', action === 'delivery' ? 'When will it be delivered?' :
                        action === 'shipment' ? 'Where\'s my shipment?' :
                        action === 'specifications' ? 'Tell me about specifications' :
                        'Payment information');

    // Add bot response with typing indicator
    setTimeout(() => {
        addMessageWithTyping('bot', response, 1500);
    }, 500);
};

// Handle email input for order tracking
const handleEmailInput = async (email: string) => {
    if (!isWaitingForEmail.value) return;

    // Add user message
    addMessage('user', email);

    // Search for order
    const orderData = await searchOrderByEmail(email);

    console.log('Order data received:', orderData);

    let response = '';
    if (orderData && orderData.success && orderData.order) {
        const deliveryStatus = calculateDeliveryStatus(orderData.order.created_at);

        console.log('Delivery status:', deliveryStatus);

        if (deliveryStatus.isDelivered) {
            response = `Great news! 🎉 Your order has been delivered! Your iPhone 17 Pro Max was ordered on ${deliveryStatus.orderDate} and was delivered on ${deliveryStatus.deliveryDate}. Thank you for choosing Zan Store!`;
        } else {
            if (deliveryStatus.daysRemaining === 0) {
                response = `Exciting news! 🎉 Your order is arriving today! Your iPhone 17 Pro Max was ordered on ${deliveryStatus.orderDate} and is expected to be delivered by ${deliveryStatus.deliveryDate}. Keep an eye out for the delivery! 📦`;
            } else {
                response = `Perfect! I found your order! 📦\n\n✅ Order Date: ${deliveryStatus.orderDate}\n🚚 Expected Delivery: ${deliveryStatus.deliveryDate}\n⏰ Days Remaining: ${deliveryStatus.daysRemaining} days\n\nYour iPhone 17 Pro Max is on its way and will arrive within 21 days from your order date. You'll receive a tracking number via email once it ships. Stay tuned! 🚀`;
            }
        }
    } else {
        console.log('No order found for email:', email);
        response = `I couldn't find any orders associated with ${email}. 😔 Please double-check your email address or contact our support team if you believe this is an error. We're here to help! 💬`;
    }

    // Add bot response with typing indicator
    setTimeout(() => {
        addMessageWithTyping('bot', response, 2000);
        isWaitingForEmail.value = false;
    }, 1000);
};

// Handle message submission
const handleMessageSubmit = () => {
    if (!messageInput.value.trim()) return;

    const userMessage = messageInput.value.trim();

    if (isWaitingForEmail.value) {
        handleEmailInput(userMessage);
    } else {
        // Add message to UI
        addMessage('user', userMessage);

        // Save to database
        sendMessageToDatabase(userMessage, 'customer');

        // Show welcome message only once and only if admin hasn't responded yet
        if (!hasShownWelcomeMessage.value && !hasAdminResponded.value) {
            hasShownWelcomeMessage.value = true;
            setTimeout(() => {
                addMessageWithTyping('bot', 'Thank you for your message! Our support team will respond shortly. You can also use the quick action buttons below for instant answers.', 1500);
            }, 500);
        }
    }

    messageInput.value = '';
};

// Direct checkout to PayPal based on payment method
const proceedToCheckout = () => {
    let paymentUrl = '';

    if (selectedPaymentMethod.value === 'pay_now') {
        // Use full payment URL
        paymentUrl = productData.value.paypal_full_payment_url || 'https://www.paypal.com/ncp/payment/7WMANWRCHP896';
    } else {
        // Use COD payment URL
        paymentUrl = productData.value.paypal_cod_payment_url || 'https://www.paypal.com/ncp/payment/7WMANWRCHP896';
    }

    // Open PayPal payment page
    window.open(paymentUrl, '_blank');
};

const getSelectedColor = () => {
    switch(selectedImage.value) {
        case 0:
        case 1:
            return 'Orange';
        case 2:
        case 3:
            return 'Gray';
        case 4:
        case 5:
            return 'White';
        default:
            return 'Orange';
    }
};

const formatCurrency = (amount: number, currency: string = 'USD') => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
    }).format(amount);
};

// PayPal functions removed - using direct checkout link instead


onMounted(() => {
    // Force light mode - remove dark class
    document.documentElement.classList.remove('dark');

    // Detect user's currency with delay to ensure DOM is ready
    setTimeout(() => {
        detectUserCurrency();
    }, 100);

    // Start polling for admin responses
    startAdminPolling();

    // PayPal SDK removed - using direct checkout link instead
});

onUnmounted(() => {
    // Stop polling for admin responses
    stopAdminPolling();
});

// Use product data from props or fallback to default
const productData = computed(() => {
    if (!props.product) {
        return {
            id: 1,
            name: 'Zan Store',
    description: 'Experience unprecedented audio clarity with our flagship wireless headphones. Featuring advanced noise cancellation, 40-hour battery life, and premium materials crafted for all-day comfort.',
            mainImage: '/images/orange_2.png',
            secondaryImages: [
                '/images/orange_1.png',
                '/images/blue_1.png',
                '/images/blue_2.png',
                '/images/white_1.png',
                '/images/white_2.png'
            ],
    features: [
        'Active Noise Cancellation',
        'Spatial Audio with dynamic head tracking',
        '40-hour battery life',
        'Premium memory foam cushions',
        'Hi-Res Audio certified',
        'Multipoint Bluetooth connectivity'
    ],
            colors: ['Orange', 'Gray', 'White'],
            quantity: 100,
            priceBefore: 1119,
            priceAfter: 559,
            discount: 50,
            paypal_full_payment_url: 'https://www.paypal.com/ncp/payment/7WMANWRCHP896',
            paypal_cod_payment_url: 'https://www.paypal.com/ncp/payment/7WMANWRCHP896'
        };
    }
    return props.product;
});

// Create images array combining main and secondary images
const productImages = computed(() => {
    const data = productData.value;
    if (!data) return [];

    const images = [data.mainImage];
    if (data.secondaryImages && data.secondaryImages.length > 0) {
        images.push(...data.secondaryImages);
    }
    return images;
});

// Static data for specs (can be made dynamic later)
const specs = [
        { label: 'Driver Size', value: '40mm' },
        { label: 'Frequency Response', value: '20Hz - 40kHz' },
        { label: 'Impedance', value: '32 Ohms' },
        { label: 'Weight', value: '250g' },
        { label: 'Connectivity', value: 'Bluetooth 5.3, USB-C' },
        { label: 'Warranty', value: '2 Years' }
];

// Generate dynamic reviews based on product name
const generateReviews = (productName: string) => {
    const reviewers = [
        { name: 'Sarah Mitchell', gender: 'female' },
        { name: 'James Rodriguez', gender: 'male' },
        { name: 'Emily Chen', gender: 'female' },
        { name: 'Michael Brown', gender: 'male' },
        { name: 'Ahmed Hassan', gender: 'male' },
        { name: 'Fatima Ali', gender: 'female' },
        { name: 'David Kim', gender: 'male' },
        { name: 'Sofia Martinez', gender: 'female' }
    ];

    const positiveTemplates = [
        {
        title: 'Absolutely Outstanding',
            comment: `The ${productName} exceeded all my expectations! The quality is exceptional and performance is top-notch. Highly recommend this product to anyone looking for the best.`
        },
        {
            title: 'Best Purchase This Year',
            comment: `I'm so happy with my ${productName}! The build quality is premium and it works flawlessly. Worth every penny and I would buy it again.`
        },
        {
            title: 'Professional Grade Quality',
            comment: `As someone who needs reliable products, the ${productName} delivers perfectly. The features are exactly what I needed and the quality is outstanding.`
        },
        {
            title: 'Highly Recommended',
            comment: `The ${productName} is amazing! Everything about it is well-designed and functional. Great value for money and excellent performance.`
        }
    ];

    const shuffledReviewers = [...reviewers].sort(() => Math.random() - 0.5).slice(0, 4);
    const shuffledTemplates = [...positiveTemplates].sort(() => Math.random() - 0.5);

    return shuffledReviewers.map((reviewer, index) => {
        const template = shuffledTemplates[index % shuffledTemplates.length];
        const daysAgo = Math.floor(Math.random() * 30) + 1;
        const date = new Date();
        date.setDate(date.getDate() - daysAgo);

        return {
            name: reviewer.name,
            rating: Math.random() > 0.2 ? 5 : 4, // 80% 5-star, 20% 4-star
            date: date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }),
            title: template.title,
            comment: template.comment,
            verified: true
        };
    });
};

const reviews = computed(() => {
    return generateReviews(productData.value.name || 'Product');
});
</script>

<template>
    <Head title="Zan Store" />

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
                    <a href="/" class="flex items-center gap-3 md:mr-auto">
                        <span class="text-xl font-semibold">Zan Store</span>
                        <img src="/images/partner.png" alt="Partner" class="h-8" />
                    </a>

                    <!-- Navigation - Centered -->
                    <nav class="hidden md:flex items-center gap-6 absolute left-1/2 transform -translate-x-1/2">
                        <a href="/" class="text-sm font-medium hover:text-purple-600 transition-colors">
                            Home
                        </a>
                        <a href="/#products" class="text-sm font-medium hover:text-purple-600 transition-colors">
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
                        <a href="/" class="text-sm font-medium hover:text-purple-600 transition-colors py-2">
                            Home
                        </a>
                        <a href="/#products" class="text-sm font-medium hover:text-purple-600 transition-colors py-2">
                            Products
                        </a>
                        <button @click="toggleChat" class="text-sm font-medium hover:text-purple-600 transition-colors py-2 text-left">
                            Support
                        </button>
                    </nav>
                </div>
                </div>
        </header>

        <!-- Hero Section -->
        <div class="pt-20 sm:pt-24 pb-12 sm:pb-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Main Product Section -->
                <div class="grid lg:grid-cols-2 gap-8 sm:gap-12 lg:gap-16 items-start">
                    <!-- Left: Images -->
                    <div class="space-y-4 sm:space-y-6 order-1 lg:order-none">
                        <!-- Main Image -->
                        <div class="aspect-square sm:aspect-[4/3] lg:aspect-square">
                            <img
                                v-if="productImages.length > 0"
                                :src="productImages[selectedImage]"
                                :alt="productData.name"
                                class="w-full h-full object-contain transition-opacity duration-300"
                            />
                        </div>

                        <!-- Thumbnail Images -->
                        <div v-if="productImages.length > 0" class="grid grid-cols-8 gap-0.5 sm:gap-1 lg:gap-1.5">
                            <button
                                v-for="(image, index) in productImages"
                                :key="index"
                                @click="selectedImage = index"
                                :class="[
                                    'aspect-square rounded-xs sm:rounded-sm overflow-hidden transition-all duration-300',
                                    selectedImage === index
                                        ? 'ring-0.5 ring-[#0071e3] dark:ring-[#0a84ff] scale-105'
                                        : 'ring-0.5 ring-[#d2d2d7] dark:ring-[#424245] hover:scale-105 opacity-60 hover:opacity-100'
                                ]"
                            >
                                <img
                                    :src="image"
                                    :alt="`${productData.name} view ${index + 1}`"
                                    class="w-full h-full object-contain"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Right: Product Info -->
                    <div class="lg:sticky lg:top-24 order-2 lg:order-none px-4 sm:px-0">
                        <!-- Limited Offer Badge -->
                        <div class="flex justify-start mb-4">
                            <div class="inline-flex items-center space-x-2 bg-gradient-to-r from-[#ff3b30] to-[#ff6b35] text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                                </svg>
                                <span class="font-semibold">Limited Time Offer - {{ productData.discount }}% Off - Limited Stock Available</span>
                            </div>
                        </div>

                        <!-- Product Title -->
                        <div class="mb-4 sm:mb-6">
                            <h2 class="text-xl sm:text-2xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] leading-relaxed">
                                {{ productData.name }}
                            </h2>
                        </div>

                        <!-- Pricing -->
                        <div class="mb-6 sm:mb-8">
                            <div class="flex flex-col sm:flex-row sm:items-baseline sm:space-x-4 space-y-2 sm:space-y-0 mb-3">
                                <div class="flex items-baseline space-x-3 sm:space-x-4">
                        <div class="flex items-start">
                            <span class="text-lg sm:text-xl font-medium text-[#1d1d1f] dark:text-[#f5f5f7] -mt-1">{{ currencySymbols[userCurrency] }}</span>
                            <span class="text-4xl sm:text-5xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7]">{{ totalPrice.toLocaleString() }}</span>
                        </div>
                        <div class="flex items-start">
                            <span class="text-sm sm:text-base text-[#86868b] dark:text-[#6e6e73] line-through -mt-0.5">{{ currencySymbols[userCurrency] }}</span>
                            <span class="text-2xl sm:text-3xl text-[#86868b] dark:text-[#6e6e73] line-through">{{ oldTotalPrice.toLocaleString() }}</span>
                        </div>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-[#ff3b30] text-white self-start">
                                    Save 20%
                                </span>
                            </div>
                            <p class="text-sm text-[#86868b] dark:text-[#6e6e73] mb-3">
                                Limited quantity available at this price
                            </p>
                        </div>

                        <!-- Color Options -->
                        <div v-if="productData.colors && productData.colors.length > 0" class="mb-6 sm:mb-8">
                            <h3 class="text-sm sm:text-base font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-3">
                                Available Colors
                            </h3>
                            <div class="flex items-center space-x-3">
                                <div
                                    v-for="(color, index) in productData.colors"
                                    :key="index"
                                    class="flex flex-col items-center space-y-1"
                                    @click="selectColor(color)"
                                >
                                    <div
                                        class="w-8 h-8 sm:w-10 sm:h-10 rounded-full border-2 border-[#d2d2d7] dark:border-[#424245] cursor-pointer hover:scale-110 transition-transform"
                                        :style="`background-color: ${getColorCode(color)};`"
                                    ></div>
                                    <span class="text-xs text-[#86868b] dark:text-[#6e6e73]">{{ color }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Quantity -->
                        <div class="mb-6 sm:mb-8">
                            <h3 class="text-sm sm:text-base font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-3">
                                Quantity
                            </h3>
                            <div class="flex items-center space-x-3">
                                <button @click="decreaseQuantity" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full border-2 border-[#d2d2d7] dark:border-[#424245] flex items-center justify-center hover:bg-[#f5f5f7] dark:hover:bg-[#1d1d1f] transition-colors">
                                    <svg class="w-4 h-4 text-[#1d1d1f] dark:text-[#f5f5f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                    </svg>
                            </button>
                                <span class="text-lg sm:text-xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] min-w-[2rem] text-center">{{ quantity }}</span>
                                <button @click="increaseQuantity" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full border-2 border-[#d2d2d7] dark:border-[#424245] flex items-center justify-center hover:bg-[#f5f5f7] dark:hover:bg-[#1d1d1f] transition-colors">
                                    <svg class="w-4 h-4 text-[#1d1d1f] dark:text-[#f5f5f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                            </button>
                            </div>
                        </div>

                        <!-- Payment Options -->
                        <div class="mb-6 sm:mb-8">
                            <h3 class="text-sm sm:text-base font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-4">
                                Choose Payment Method
                            </h3>
                            <div class="space-y-3">
                                <label class="group relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200" :class="selectedPaymentMethod === 'pay_now' ? 'border-[#0071e3] bg-[#0071e3]/5' : 'border-[#e5e5e7] dark:border-[#424245] hover:border-[#0071e3]/50'">
                                    <input type="radio" v-model="selectedPaymentMethod" value="pay_now" class="sr-only" />
                                    <div class="flex items-center space-x-4 w-full">
                                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center" :class="selectedPaymentMethod === 'pay_now' ? 'border-[#0071e3] bg-[#0071e3]' : 'border-[#d2d2d7] dark:border-[#424245]'">
                                            <div v-if="selectedPaymentMethod === 'pay_now'" class="w-2 h-2 bg-white rounded-full"></div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-5 h-5 text-[#0071e3]" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"/>
                                                </svg>
                                                <span class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7]">Pay Now (Full Amount)</span>
                                            </div>
                                            <div class="mt-2">
                                                <div class="flex items-center space-x-2">
                                                    <!-- Visa Icon -->
                                                    <div class="w-8 h-5 bg-[#1a1f71] rounded flex items-center justify-center shadow-sm">
                                                        <span class="text-white text-xs font-bold">VISA</span>
                                                    </div>
                                                    <!-- Mastercard Icon -->
                                                    <div class="w-8 h-5 bg-[#eb001b] rounded flex items-center justify-center relative overflow-hidden shadow-sm">
                                                        <div class="w-4 h-4 bg-[#f79e1b] rounded-full absolute -left-1"></div>
                                                        <div class="w-4 h-4 bg-[#eb001b] rounded-full absolute -right-1"></div>
                                                    </div>
                                                    <!-- American Express Icon -->
                                                    <div class="w-8 h-5 bg-[#006fcf] rounded flex items-center justify-center shadow-sm">
                                                        <span class="text-white text-xs font-bold">AE</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </label>

                                <label class="group relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200" :class="selectedPaymentMethod === 'pay_on_delivery' ? 'border-[#0071e3] bg-[#0071e3]/5' : 'border-[#e5e5e7] dark:border-[#424245] hover:border-[#0071e3]/50'">
                                    <input type="radio" v-model="selectedPaymentMethod" value="pay_on_delivery" class="sr-only" />
                                    <div class="flex items-center space-x-4 w-full">
                                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center" :class="selectedPaymentMethod === 'pay_on_delivery' ? 'border-[#0071e3] bg-[#0071e3]' : 'border-[#d2d2d7] dark:border-[#424245]'">
                                            <div v-if="selectedPaymentMethod === 'pay_on_delivery'" class="w-2 h-2 bg-white rounded-full"></div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-5 h-5 text-[#34c759]" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.155.103.346.196.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                                </svg>
                                                <span class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7]">Pay on Delivery</span>
                                            </div>
                                            <p class="text-sm text-[#86868b] dark:text-[#a1a1a6] mt-1">Pay {{ currencySymbols[userCurrency] }}50 shipping now, {{ formatPrice(totalPrice) }} on delivery</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Payment Summary -->
                        <div class="mb-6 sm:mb-8 bg-[#f5f5f7] dark:bg-[#2d2d2d] rounded-xl p-4">
                            <h4 class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-3">Payment Summary</h4>
                            <div v-if="selectedPaymentMethod === 'pay_now'" class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-[#86868b] dark:text-[#a1a1a6]">Product Amount</span>
                                    <span class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7]">{{ formatPrice(totalPrice) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-[#86868b] dark:text-[#a1a1a6]">Shipping Fee</span>
                                    <span class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7]">{{ currencySymbols[userCurrency] }}49</span>
                                </div>
                                <div class="border-t border-[#d2d2d7] dark:border-[#424245] pt-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-lg font-semibold text-[#1d1d1f] dark:text-[#f5f5f7]">Total to Pay Now</span>
                                        <span class="text-xl font-bold text-[#0071e3]">{{ currencySymbols[userCurrency] }}{{ (totalPrice + 49).toLocaleString() }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-[#86868b] dark:text-[#a1a1a6]">Shipping Fee (Pay Now)</span>
                                    <span class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7]">{{ currencySymbols[userCurrency] }}50</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-[#86868b] dark:text-[#a1a1a6]">Product Amount (Pay on Delivery)</span>
                                    <span class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7]">{{ formatPrice(totalPrice) }}</span>
                                </div>
                                <div class="border-t border-[#d2d2d7] dark:border-[#424245] pt-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-lg font-semibold text-[#1d1d1f] dark:text-[#f5f5f7]">Amount to Pay Now</span>
                                        <span class="text-xl font-bold text-[#0071e3]">{{ currencySymbols[userCurrency] }}50</span>
                                    </div>
                                    <p class="text-xs text-[#86868b] dark:text-[#a1a1a6] mt-1">Remaining {{ formatPrice(totalPrice) }} to be paid on delivery</p>
                                </div>
                            </div>
                        </div>

                        <!-- Checkout Button -->
                        <div class="mb-6 sm:mb-8">
                            <button @click="proceedToCheckout" class="w-full bg-[#1d1d1f] dark:bg-[#1d1d1f] hover:bg-[#2d2d2d] dark:hover:bg-[#2d2d2d] text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200 flex items-center justify-center">
                                <span>Proceed to Checkout</span>
                            </button>
                        </div>

                        <!-- Delivery Info -->
                        <div class="mt-6 sm:mt-8 pb-6 sm:pb-8 border-b border-[#d2d2d7] dark:border-[#424245]">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                <!-- Row 1 -->
                            <div class="flex items-start space-x-3">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-[#06c] dark:text-[#2997ff] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <div>
                                    <p class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] text-sm sm:text-base">Free Delivery</p>
                                    <p class="text-xs sm:text-sm text-[#86868b] dark:text-[#6e6e73]">21-day shipping on all orders</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-[#06c] dark:text-[#2997ff] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] text-sm sm:text-base">2-Year Warranty</p>
                                    <p class="text-xs sm:text-sm text-[#86868b] dark:text-[#6e6e73]">Complete protection included</p>
                                </div>
                            </div>
                                <!-- Row 2 -->
                            <div class="flex items-start space-x-3">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-[#06c] dark:text-[#2997ff] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <div>
                                    <p class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] text-sm sm:text-base">30-Day Returns</p>
                                    <p class="text-xs sm:text-sm text-[#86868b] dark:text-[#6e6e73]">Free returns, no questions asked</p>
                                </div>
                            </div>
                                <div class="flex items-start space-x-3">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-[#06c] dark:text-[#2997ff] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <div>
                                        <p class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] text-sm sm:text-base">Secure Payment</p>
                                        <p class="text-xs sm:text-sm text-[#86868b] dark:text-[#6e6e73]">SSL encrypted checkout with PayPal</p>
                        </div>
                        </div>
                        </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Content Section -->
        <div class="py-12 sm:py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Overview -->
                <div class="mt-0">
                    <div class="text-center mb-8 sm:mb-12">
                        <div class="inline-flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-gradient-to-r from-[#0071e3] to-[#0a84ff] rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                                </svg>
                            </div>
                            <h2 class="text-3xl sm:text-4xl font-bold text-[#1d1d1f] dark:text-[#f5f5f7]">
                                Overview
                            </h2>
                        </div>
                        <p class="text-lg text-[#6e6e73] dark:text-[#a1a1a6] max-w-2xl mx-auto">
                            Discover the cutting-edge technology and premium features that make this device exceptional
                        </p>
                    </div>

                    <div class="max-w-4xl mx-auto">
                        <p class="text-[#6e6e73] dark:text-[#a1a1a6] leading-relaxed text-lg text-center">
                            Experience the future of mobile technology with the iPhone 17 Pro Max. Featuring the revolutionary A18 Pro chip, advanced camera system with 5x optical zoom, and titanium design crafted for ultimate durability and premium feel.
                        </p>
                    </div>
                </div>


                <!-- Technical Specifications -->
                <div class="mt-16 sm:mt-24">
                    <div class="text-center mb-8 sm:mb-12">
                        <div class="inline-flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-gradient-to-r from-[#0071e3] to-[#0a84ff] rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h2 class="text-3xl sm:text-4xl font-bold text-[#1d1d1f] dark:text-[#f5f5f7]">
                        Technical Specifications
                    </h2>
                        </div>
                        <p class="text-lg text-[#6e6e73] dark:text-[#a1a1a6] max-w-2xl mx-auto">
                            Discover the cutting-edge technology and premium features that make this device exceptional
                        </p>
                    </div>

                    <div class="max-w-6xl mx-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div
                                v-for="(spec, index) in specs"
                                :key="index"
                                class="group bg-gradient-to-br from-white to-gray-50 dark:from-[#1d1d1f] dark:to-[#2d2d2d] rounded-2xl p-6 border border-gray-100 dark:border-gray-800 hover:border-[#0071e3] dark:hover:border-[#0a84ff] transition-all duration-300 hover:shadow-xl hover:-translate-y-1"
                            >
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-gradient-to-r from-[#0071e3] to-[#0a84ff] rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-[#1d1d1f] dark:text-[#f5f5f7] text-lg mb-2 group-hover:text-[#0071e3] dark:group-hover:text-[#0a84ff] transition-colors">
                                            {{ spec.label }}
                                        </h3>
                                        <p class="text-[#6e6e73] dark:text-[#a1a1a6] text-base leading-relaxed">
                                    {{ spec.value }}
                                        </p>
                                </div>
                                </div>

                                <!-- Hover Effect -->
                                <div class="mt-4 h-1 bg-gradient-to-r from-[#0071e3] to-[#0a84ff] rounded-full transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Reviews -->
                <div class="mt-16 sm:mt-24">
                    <div class="text-center mb-8 sm:mb-12">
                        <div class="inline-flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-gradient-to-r from-[#ffcc00] to-[#ff9500] rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                            <h2 class="text-3xl sm:text-4xl font-bold text-[#1d1d1f] dark:text-[#f5f5f7]">
                            Customer Reviews
                        </h2>
                        </div>
                        <p class="text-lg text-[#6e6e73] dark:text-[#a1a1a6] max-w-2xl mx-auto mb-6">
                            Real experiences from our verified customers
                        </p>

                        <!-- Rating Summary -->
                        <div class="max-w-md mx-auto bg-gradient-to-br from-white to-gray-50 dark:from-[#1d1d1f] dark:to-[#2d2d2d] rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-lg">
                            <div class="flex flex-col items-center">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg v-for="i in 5" :key="i" class="w-7 h-7 text-[#ffcc00]" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                                <div class="text-4xl font-bold text-[#1d1d1f] dark:text-[#f5f5f7] mb-1">4.9</div>
                                <div class="text-sm text-[#6e6e73] dark:text-[#a1a1a6]">out of 5 stars</div>
                                <div class="text-xs text-[#86868b] dark:text-[#6e6e73] mt-2">
                                    Based on <span class="font-semibold text-[#0071e3]">{{ reviews.length }} verified reviews</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div
                            v-for="(review, index) in reviews"
                            :key="index"
                            class="group bg-gradient-to-br from-white to-gray-50 dark:from-[#1d1d1f] dark:to-[#2d2d2d] rounded-2xl p-6 border border-gray-100 dark:border-gray-800 hover:border-[#0071e3] dark:hover:border-[#0a84ff] transition-all duration-300 hover:shadow-xl hover:-translate-y-1"
                        >
                            <!-- Review Header -->
                            <div class="flex items-start gap-4 mb-4">
                                <!-- Avatar -->
                                <div class="w-12 h-12 bg-gradient-to-r from-[#0071e3] to-[#0a84ff] rounded-full flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                    <span class="text-white font-bold text-lg">{{ review.name.charAt(0) }}</span>
                                </div>

                                <!-- Name and Date -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h4 class="font-bold text-[#1d1d1f] dark:text-[#f5f5f7] text-lg group-hover:text-[#0071e3] dark:group-hover:text-[#0a84ff] transition-colors">
                                            {{ review.name }}
                                        </h4>
                                        <span v-if="review.verified" class="inline-flex items-center gap-1 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-xs font-medium px-2 py-1 rounded-full">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Verified
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex gap-0.5">
                                            <svg v-for="i in review.rating" :key="i" class="w-4 h-4 text-[#ffcc00]" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </div>
                                        <span class="text-xs text-[#86868b] dark:text-[#6e6e73]">{{ review.date }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Review Title -->
                            <h5 class="font-bold text-[#1d1d1f] dark:text-[#f5f5f7] mb-3 text-lg leading-tight">
                                {{ review.title }}
                            </h5>

                            <!-- Review Comment -->
                            <p class="text-[#6e6e73] dark:text-[#a1a1a6] leading-relaxed text-base">
                                {{ review.comment }}
                            </p>

                            <!-- Hover Effect -->
                            <div class="mt-4 h-1 bg-gradient-to-r from-[#ffcc00] to-[#ff9500] rounded-full transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                        </div>
                    </div>
                </div>

                <!-- Final CTA -->
                <div class="mt-16 sm:mt-24 bg-gradient-to-r from-[#0071e3] to-[#0077ed] dark:from-[#0a84ff] dark:to-[#0077ed] rounded-2xl sm:rounded-3xl p-6 sm:p-8 lg:p-12 text-center text-white">
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold mb-3 sm:mb-4">
                        Don't Miss This Limited Offer
                    </h2>
                    <p class="text-base sm:text-lg lg:text-xl mb-6 sm:mb-8 opacity-90">
                        Only a few units left at this special price. Order now and save {{ currencySymbols[userCurrency] }}{{ (oldPriceConverted - newPriceConverted).toLocaleString() }}.
                    </p>
                    <button class="bg-white text-[#0071e3] dark:text-[#0a84ff] text-base sm:text-lg font-semibold px-8 sm:px-12 py-3 sm:py-4 rounded-xl hover:scale-105 transition-transform shadow-xl">
                        Get Yours Today
                    </button>
                </div>
            </div>
        </div>

        <!-- Chat Bot Icon -->
        <div class="fixed bottom-6 right-6 z-50">
            <button @click="toggleChat" class="group relative">
                <!-- Main Chat Button -->
                <div class="w-14 h-14 sm:w-16 sm:h-16 bg-gradient-to-r from-[#0071e3] to-[#0077ed] dark:from-[#0a84ff] dark:to-[#0077ed] rounded-full shadow-2xl hover:shadow-3xl transition-all duration-300 transform hover:scale-110 flex items-center justify-center">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>

                <!-- Notification Badge -->
                <div class="absolute -top-1 -right-1 w-5 h-5 bg-[#ff3b30] rounded-full flex items-center justify-center animate-pulse">
                    <span class="text-xs font-bold text-white">1</span>
                </div>

                <!-- Tooltip -->
                <div class="absolute bottom-full right-0 mb-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                    <div class="bg-[#1d1d1f] dark:bg-[#f5f5f7] text-white dark:text-[#1d1d1f] text-sm font-medium px-3 py-2 rounded-lg shadow-lg whitespace-nowrap">
                        Need help? Chat with us!
                        <div class="absolute top-full right-4 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-[#1d1d1f] dark:border-t-[#f5f5f7]"></div>
                    </div>
                </div>

                <!-- Ripple Effect -->
                <div class="absolute inset-0 rounded-full bg-[#0071e3] opacity-0 group-hover:opacity-20 group-hover:animate-ping"></div>
            </button>
        </div>

        <!-- Chat Window -->
        <div v-if="isChatOpen" class="fixed bottom-24 right-6 z-50 w-96 sm:w-[28rem] h-[32rem] sm:h-[36rem] bg-white dark:bg-[#1d1d1f] rounded-2xl shadow-2xl border border-[#d2d2d7] dark:border-[#424245] overflow-hidden flex flex-col">
            <!-- Chat Header -->
            <div class="bg-[#0071e3] dark:bg-[#0a84ff] px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold text-sm">Support Team</h3>
                        <p class="text-white/80 text-xs">Online now</p>
                    </div>
                </div>
                <button @click="toggleChat" class="text-white/80 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Chat Messages -->
            <div class="flex-1 p-4 space-y-3 overflow-y-auto chat-messages">
                <div v-for="message in chatMessages" :key="message.id" class="flex items-start space-x-2" :class="message.type === 'user' ? 'justify-end' : ''">
                    <!-- Bot Avatar -->
                    <div v-if="message.type === 'bot'" class="w-6 h-6 bg-[#0071e3] rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
                        </svg>
                    </div>

                    <!-- Message Content -->
                    <div :class="[
                        'rounded-2xl px-3 py-2 max-w-xs',
                        message.type === 'bot'
                            ? 'bg-[#f5f5f7] dark:bg-[#2d2d2d] rounded-tl-sm'
                            : 'bg-[#0071e3] dark:bg-[#0a84ff] rounded-tr-sm'
                    ]">
                        <p :class="[
                            'text-sm whitespace-pre-line',
                            message.type === 'bot'
                                ? 'text-[#1d1d1f] dark:text-[#f5f5f7]'
                                : 'text-white'
                        ]">{{ message.message }}</p>
                        <span :class="[
                            'text-xs',
                            message.type === 'bot'
                                ? 'text-[#86868b] dark:text-[#a1a1a6]'
                                : 'text-white/80'
                        ]">{{ message.timestamp }}</span>
                    </div>

                    <!-- User Avatar -->
                    <div v-if="message.type === 'user'" class="w-6 h-6 bg-[#86868b] rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>

                <!-- Typing Indicator -->
                <div v-if="isTyping" class="flex items-start space-x-2 px-4">
                    <div class="w-6 h-6 bg-[#0071e3] rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
                        </svg>
                    </div>
                    <div class="bg-[#f5f5f7] dark:bg-[#2d2d2d] rounded-2xl rounded-tl-sm px-3 py-2">
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 bg-[#86868b] dark:bg-[#a1a1a6] rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-[#86868b] dark:bg-[#a1a1a6] rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                            <div class="w-2 h-2 bg-[#86868b] dark:bg-[#a1a1a6] rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="px-4 py-3 border-t border-[#d2d2d7] dark:border-[#424245] flex-shrink-0">
                <div class="flex flex-wrap gap-2">
                    <button @click="handleQuickAction('specifications')" class="px-3 py-1.5 bg-[#f5f5f7] dark:bg-[#2d2d2d] text-[#1d1d1f] dark:text-[#f5f5f7] text-xs rounded-full hover:bg-[#e5e5e7] dark:hover:bg-[#3d3d3d] transition-colors">
                        📱 Specifications
                    </button>
                    <button @click="handleQuickAction('payment')" class="px-3 py-1.5 bg-[#f5f5f7] dark:bg-[#2d2d2d] text-[#1d1d1f] dark:text-[#f5f5f7] text-xs rounded-full hover:bg-[#e5e5e7] dark:hover:bg-[#3d3d3d] transition-colors">
                        💳 Payment
                    </button>
                    <button @click="handleQuickAction('delivery')" class="px-3 py-1.5 bg-[#f5f5f7] dark:bg-[#2d2d2d] text-[#1d1d1f] dark:text-[#f5f5f7] text-xs rounded-full hover:bg-[#e5e5e7] dark:hover:bg-[#3d3d3d] transition-colors">
                        🚚 When will it be delivered?
                    </button>
                    <button @click="handleQuickAction('shipment')" class="px-3 py-1.5 bg-[#f5f5f7] dark:bg-[#2d2d2d] text-[#1d1d1f] dark:text-[#f5f5f7] text-xs rounded-full hover:bg-[#e5e5e7] dark:hover:bg-[#3d3d3d] transition-colors">
                        📦 Where's my shipment?
                    </button>
                </div>
            </div>

            <!-- Message Input -->
            <div class="px-4 py-3 border-t border-[#d2d2d7] dark:border-[#424245] flex-shrink-0">
                <div class="flex items-center space-x-2">
                    <input
                        type="text"
                        :placeholder="isWaitingForEmail ? 'Please enter your email address...' : 'Type your message...'"
                        @keyup.enter="handleMessageSubmit"
                        v-model="messageInput"
                        class="flex-1 bg-[#f5f5f7] dark:bg-[#2d2d2d] text-[#1d1d1f] dark:text-[#f5f5f7] text-sm px-3 py-2 rounded-full border-0 focus:outline-none focus:ring-2 focus:ring-[#0071e3] dark:focus:ring-[#0a84ff]"
                    />
                    <button @click="handleMessageSubmit" class="w-8 h-8 bg-[#0071e3] dark:bg-[#0a84ff] rounded-full flex items-center justify-center hover:bg-[#0077ed] transition-colors">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>


        <!-- Footer -->
        <footer class="bg-gray-900 text-gray-300 mt-16">
            <div class="container mx-auto px-4 py-12">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
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

                    <!-- Quick Links -->
                    <div>
                        <h3 class="text-white font-semibold mb-4">Quick Links</h3>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-white transition-colors">About Us</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Blog</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Careers</a></li>
                        </ul>
                    </div>

                    <!-- Customer Service -->
                    <div>
                        <h3 class="text-white font-semibold mb-4">Customer Service</h3>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-white transition-colors">Help Center</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Track Order</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Returns</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Shipping Info</a></li>
                        </ul>
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
    </div>
</template>

<style scoped>
/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Custom scrollbar for webkit browsers */
::-webkit-scrollbar {
    width: 10px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #d2d2d7;
    border-radius: 5px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a1a1a6;
}

.dark ::-webkit-scrollbar-thumb {
    background: #424245;
}

.dark ::-webkit-scrollbar-thumb:hover {
    background: #6e6e73;
}

/* PayPal styling removed */

/* Force light mode on mobile and all devices */
html {
    color-scheme: light !important;
}

html.dark {
    color-scheme: light !important;
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

.text-blue-600 {
    color: #2563eb !important;
}

.text-red-500 {
    color: #ef4444 !important;
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

.text-5xl {
    font-size: 3rem !important;
    line-height: 1 !important;
}

/* Mobile specific adjustments */
@media (max-width: 768px) {
    .text-4xl {
        font-size: 2rem !important;
        line-height: 2.25rem !important;
    }

    .text-5xl {
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
}

/* Override all dark mode styles */
.dark * {
    color-scheme: light !important;
}
</style>

