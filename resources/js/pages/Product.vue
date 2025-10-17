<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';

// PayPal types
declare global {
    interface Window {
        paypal: any;
    }
}

const selectedImage = ref(0);
const quantity = ref(1);
const isChatOpen = ref(false);
const isCheckoutOpen = ref(false);
const checkoutForm = ref({
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    shipping_address: ''
});

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

// Color selection functionality
const selectColor = (colorName: string) => {
    switch(colorName) {
        case 'Orange':
            selectedImage.value = 0; // orange_1.png
            break;
        case 'Gray':
            selectedImage.value = 2; // blue_1.png
            break;
        case 'White':
            selectedImage.value = 4; // white_1.png
            break;
        default:
            selectedImage.value = 0;
    }
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
    return convertPrice(product.newPrice * quantity.value);
});

const oldTotalPrice = computed(() => {
    return convertPrice(product.oldPrice * quantity.value);
});

const newPriceConverted = computed(() => {
    return convertPrice(product.newPrice);
});

const oldPriceConverted = computed(() => {
    return convertPrice(product.oldPrice);
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

// Checkout functionality
const openCheckout = () => {
    isCheckoutOpen.value = true;
    // Load PayPal buttons after modal opens
    setTimeout(() => {
        loadPayPalButtons();
    }, 100);
};

const closeCheckout = () => {
    isCheckoutOpen.value = false;
    // Reset form
    checkoutForm.value = {
        customer_name: '',
        customer_email: '',
        customer_phone: '',
        shipping_address: ''
    };
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

const loadPayPalButtons = () => {
    // Load PayPal SDK if not already loaded
    if (!window.paypal) {
        const script = document.createElement('script');
        script.src = 'https://www.paypal.com/sdk/js?client-id=BAAnWbwo7vDF9gfAtzKHX3iHd8D3G1_P2siviATC-RZhgy_dWeY6sII-Y3YSm5DWtuJ3sHNuyll5KU7Pmg&components=hosted-buttons&disable-funding=venmo&currency=USD';
        script.async = true;
        script.onload = () => {
            if (window.paypal) {
                window.paypal.HostedButtons({
                    hostedButtonId: "7WMANWRCHP896",
                }).render("#paypal-container-7WMANWRCHP896").then(() => {
                    // Store form data when PayPal buttons are ready
                    sessionStorage.setItem('checkoutData', JSON.stringify({
                        ...checkoutForm.value,
                        product_name: 'iPhone 17 Pro Max 512GB',
                        product_color: getSelectedColor(),
                        quantity: quantity.value,
                        unit_price: product.newPrice,
                        total_amount: totalPrice.value
                    }));
                });
            }
        };
        document.head.appendChild(script);
    } else {
        // PayPal SDK already loaded, render buttons
        window.paypal.HostedButtons({
            hostedButtonId: "7WMANWRCHP896",
        }).render("#paypal-container-7WMANWRCHP896").then(() => {
            // Store form data when PayPal buttons are ready
            sessionStorage.setItem('checkoutData', JSON.stringify({
                ...checkoutForm.value,
                product_name: 'iPhone 17 Pro Max 512GB',
                product_color: getSelectedColor(),
                quantity: quantity.value,
                unit_price: product.newPrice,
                total_amount: totalPrice.value
            }));
        });
    }
};

// Timer functionality
const timeLeft = ref({
    days: 5,
    hours: 0,
    minutes: 0,
    seconds: 0
});

let timerInterval: NodeJS.Timeout | null = null;

const updateTimer = () => {
    const now = new Date().getTime();
    const endTime = now + (timeLeft.value.days * 24 * 60 * 60 * 1000) +
                          (timeLeft.value.hours * 60 * 60 * 1000) +
                          (timeLeft.value.minutes * 60 * 1000) +
                          (timeLeft.value.seconds * 1000);

    const distance = endTime - now;

    if (distance > 0) {
        timeLeft.value.days = Math.floor(distance / (1000 * 60 * 60 * 24));
        timeLeft.value.hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        timeLeft.value.minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        timeLeft.value.seconds = Math.floor((distance % (1000 * 60)) / 1000);
    } else {
        timeLeft.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
        if (timerInterval) {
            clearInterval(timerInterval);
        }
    }
};

onMounted(() => {
    timerInterval = setInterval(updateTimer, 1000);

    // Detect user's currency with delay to ensure DOM is ready
    setTimeout(() => {
        detectUserCurrency();
    }, 100);

    // Start polling for admin responses
    startAdminPolling();

    // Load PayPal SDK
    const script = document.createElement('script');
    script.src = 'https://www.paypal.com/sdk/js?client-id=BAAnWbwo7vDF9gfAtzKHX3iHd8D3G1_P2siviATC-RZhgy_dWeY6sII-Y3YSm5DWtuJ3sHNuyll5KU7Pmg&components=hosted-buttons&disable-funding=venmo&currency=USD';
    script.async = true;
    script.onload = () => {
        // Render PayPal button after SDK loads
        if (window.paypal) {
            window.paypal.HostedButtons({
                hostedButtonId: "7WMANWRCHP896",
            }).render("#paypal-container-7WMANWRCHP896").then(() => {
                // Hide only product details text, not buttons
                setTimeout(() => {
                    const container = document.getElementById('paypal-container-7WMANWRCHP896');
                    if (container) {
                        // Find and hide only the text elements, not iframe or button containers
                        const allDivs = container.querySelectorAll('div:not([id*="zoid"])');
                        allDivs.forEach((div: HTMLElement) => {
                            const hasButton = div.querySelector('iframe') || div.querySelector('[id*="zoid"]');
                            if (!hasButton && (div.textContent?.includes('Apple iPhone') || div.textContent?.includes('$559') || div.textContent?.includes('USD'))) {
                                div.style.display = 'none';
                            }
                        });
                    }
                }, 1000);
            });
        }
    };
    document.head.appendChild(script);
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
    // Stop polling for admin responses
    stopAdminPolling();
});

const product = {
    name: 'Premium Wireless Headphones',
    tagline: 'Studio-quality sound. Elevated comfort.',
    description: 'Experience unprecedented audio clarity with our flagship wireless headphones. Featuring advanced noise cancellation, 40-hour battery life, and premium materials crafted for all-day comfort.',
    features: [
        'Active Noise Cancellation',
        'Spatial Audio with dynamic head tracking',
        '40-hour battery life',
        'Premium memory foam cushions',
        'Hi-Res Audio certified',
        'Multipoint Bluetooth connectivity'
    ],
    specs: [
        { label: 'Driver Size', value: '40mm' },
        { label: 'Frequency Response', value: '20Hz - 40kHz' },
        { label: 'Impedance', value: '32 Ohms' },
        { label: 'Weight', value: '250g' },
        { label: 'Connectivity', value: 'Bluetooth 5.3, USB-C' },
        { label: 'Warranty', value: '2 Years' }
    ],
    oldPrice: 1119,
    newPrice: 559,
    discount: 50,
    images: [
        'http://127.0.0.1:8001/images/orange_1.png',
        'http://127.0.0.1:8001/images/orange_2.png',
        'http://127.0.0.1:8001/images/blue_1.png',
        'http://127.0.0.1:8001/images/blue_2.png',
        'http://127.0.0.1:8001/images/white_1.png',
        'http://127.0.0.1:8001/images/white_2.png'
    ]
};

const reviews = [
    {
        name: 'Sarah Mitchell',
        rating: 5,
        date: 'October 12, 2025',
        title: 'Absolutely Outstanding',
        comment: 'These headphones exceeded all my expectations. The sound quality is pristine, and the noise cancellation is the best I\'ve experienced. Worth every penny.',
        verified: true
    },
    {
        name: 'James Rodriguez',
        rating: 5,
        date: 'October 8, 2025',
        title: 'Professional Grade Quality',
        comment: 'As a music producer, I need accurate sound reproduction. These deliver flawlessly. The comfort level is also exceptional for long studio sessions.',
        verified: true
    },
    {
        name: 'Emily Chen',
        rating: 5,
        date: 'October 5, 2025',
        title: 'Best Purchase This Year',
        comment: 'The build quality feels premium in every way. Battery life is incredible, and the spatial audio feature is mind-blowing. Highly recommended!',
        verified: true
    },
    {
        name: 'Michael Brown',
        rating: 4,
        date: 'October 1, 2025',
        title: 'Impressive Performance',
        comment: 'Really great headphones with fantastic sound. Only minor complaint is the case could be more compact, but that\'s nitpicking.',
        verified: true
    }
];
</script>

<template>
    <Head title="Premium Wireless Headphones" />

    <div class="min-h-screen bg-[#fbfbfd] dark:bg-[#000000]">
        <!-- Navigation -->
        <nav class="fixed top-0 left-0 right-0 z-50 backdrop-blur-2xl bg-[#fbfbfd]/80 dark:bg-[#000000]/80 border-b border-[#d2d2d7]/30 dark:border-[#424245]/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-14 sm:h-16">
                    <div class="flex items-center space-x-6 sm:space-x-8">
                        <a href="/" class="text-lg sm:text-xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7]">
                            Zan Store
                        </a>
                    </div>
                    <div class="flex items-center space-x-4 sm:space-x-6">
                        <!-- Currency Display -->
                        <div class="flex items-center space-x-2">
                            <div class="text-xs text-[#86868b] dark:text-[#6e6e73]">
                                {{ userCurrency }} {{ currencySymbols[userCurrency] }}
                            </div>
                            <select @change="setCurrency(($event.target as HTMLSelectElement).value)" :value="userCurrency" class="text-xs bg-transparent border border-[#d2d2d7] dark:border-[#424245] rounded px-2 py-1 text-[#1d1d1f] dark:text-[#f5f5f7]">
                                <option value="USD">USD</option>
                                <option value="SAR">SAR</option>
                                <option value="EGP">EGP</option>
                                <option value="AED">AED</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                            </select>
                        </div>
                        <button @click="toggleChat" class="text-xs sm:text-sm text-[#1d1d1f] dark:text-[#f5f5f7] hover:text-[#06c] dark:hover:text-[#2997ff] transition-colors">
                            Support
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="pt-20 sm:pt-24 pb-12 sm:pb-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Main Product Section -->
                <div class="grid lg:grid-cols-2 gap-8 sm:gap-12 lg:gap-16 items-start">
                    <!-- Left: Images -->
                    <div class="space-y-4 sm:space-y-6 order-1 lg:order-none">
                        <!-- Main Image -->
                        <div class="aspect-square sm:aspect-[4/3] lg:aspect-square rounded-2xl sm:rounded-3xl overflow-hidden bg-[#f5f5f7] dark:bg-[#1d1d1f] shadow-xl sm:shadow-2xl">
                            <img
                                :src="product.images[selectedImage]"
                                :alt="product.name"
                                class="w-full h-full object-contain transition-opacity duration-300"
                            />
                        </div>

                        <!-- Thumbnail Images -->
                        <div class="grid grid-cols-8 gap-0.5 sm:gap-1 lg:gap-1.5">
                            <button
                                v-for="(image, index) in product.images"
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
                                    :alt="`${product.name} view ${index + 1}`"
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
                                <span class="font-semibold">Limited Time Offer - {{ product.discount }}% Off - Limited Stock Available</span>
                            </div>
                        </div>

                        <!-- Product Title -->
                        <div class="mb-4 sm:mb-6">
                            <h2 class="text-xl sm:text-2xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] leading-relaxed">
                                Apple iPhone 17 Pro Max 512 GB: 6.9-inch Display with ProMotion, A19 Pro Chip, Best Battery Life in Any iPhone Ever, Pro Fusion Camera System, Center Stage Front Camera; Cosmic Orange
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
                                    Save {{ product.discount }}%
                                </span>
                            </div>
                            <p class="text-sm text-[#86868b] dark:text-[#6e6e73] mb-3">
                                Limited quantity available at this price
                            </p>

                            <!-- Countdown Timer (Small) -->
                            <div class="flex items-center space-x-1 text-sm font-mono font-semibold text-[#1d1d1f] dark:text-[#f5f5f7]">
                                <span class="text-xs text-[#86868b] dark:text-[#6e6e73] mr-1">Offer ends in:</span>
                                <span class="bg-[#f5f5f7] dark:bg-[#1d1d1f] px-1.5 py-0.5 rounded border border-[#d2d2d7] dark:border-[#424245]">
                                    {{ timeLeft.days.toString().padStart(2, '0') }}
                                </span>
                                <span class="text-[#86868b] dark:text-[#6e6e73]">:</span>
                                <span class="bg-[#f5f5f7] dark:bg-[#1d1d1f] px-1.5 py-0.5 rounded border border-[#d2d2d7] dark:border-[#424245]">
                                    {{ timeLeft.hours.toString().padStart(2, '0') }}
                                </span>
                                <span class="text-[#86868b] dark:text-[#6e6e73]">:</span>
                                <span class="bg-[#f5f5f7] dark:bg-[#1d1d1f] px-1.5 py-0.5 rounded border border-[#d2d2d7] dark:border-[#424245]">
                                    {{ timeLeft.minutes.toString().padStart(2, '0') }}
                                </span>
                                <span class="text-[#86868b] dark:text-[#6e6e73]">:</span>
                                <span class="bg-[#f5f5f7] dark:bg-[#1d1d1f] px-1.5 py-0.5 rounded border border-[#d2d2d7] dark:border-[#424245] animate-pulse">
                                    {{ timeLeft.seconds.toString().padStart(2, '0') }}
                                </span>
                            </div>
                        </div>

                        <!-- Color Options -->
                        <div class="mb-6 sm:mb-8">
                            <h3 class="text-sm sm:text-base font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-3">
                                Available Colors
                            </h3>
                            <div class="flex items-center space-x-3">
                                <div class="flex flex-col items-center space-y-1" @click="selectColor('Orange')">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full border-2 border-[#d2d2d7] dark:border-[#424245] cursor-pointer hover:scale-110 transition-transform" style="background-color: #fa8b4b;"></div>
                                    <span class="text-xs text-[#86868b] dark:text-[#6e6e73]">Orange</span>
                                </div>
                                <div class="flex flex-col items-center space-y-1" @click="selectColor('Gray')">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full border-2 border-[#d2d2d7] dark:border-[#424245] cursor-pointer hover:scale-110 transition-transform" style="background-color: #4a4d5e;"></div>
                                    <span class="text-xs text-[#86868b] dark:text-[#6e6e73]">Gray</span>
                                </div>
                                <div class="flex flex-col items-center space-y-1" @click="selectColor('White')">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full border-2 border-[#d2d2d7] dark:border-[#424245] cursor-pointer hover:scale-110 transition-transform" style="background-color: #f5f5f5;"></div>
                                    <span class="text-xs text-[#86868b] dark:text-[#6e6e73]">White</span>
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

                        <!-- Checkout Button -->
                        <div class="mb-6 sm:mb-8">
                            <button @click="openCheckout" class="w-full bg-[#0071e3] dark:bg-[#0a84ff] hover:bg-[#0077ed] dark:hover:bg-[#0a84ff] text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200 flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"/>
                                </svg>
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
                                    <p class="text-xs sm:text-sm text-[#86868b] dark:text-[#6e6e73]">2-day shipping on all orders</p>
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

                        <!-- Description -->
                        <div class="mt-6 sm:mt-8">
                            <h3 class="text-xl sm:text-2xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-3 sm:mb-4">
                                Overview
                            </h3>
                            <p class="text-[#1d1d1f] dark:text-[#a1a1a6] leading-relaxed mb-4 sm:mb-6 text-sm sm:text-base">
                                {{ product.description }}
                            </p>
                        </div>

                        <!-- Key Features -->
                        <div class="mt-6 sm:mt-8">
                            <h3 class="text-xl sm:text-2xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-3 sm:mb-4">
                                Key Features
                            </h3>
                            <ul class="space-y-2 sm:space-y-3">
                                <li
                                    v-for="(feature, index) in product.features"
                                    :key="index"
                                    class="flex items-start space-x-3"
                                >
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-[#06c] dark:text-[#2997ff] flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-[#1d1d1f] dark:text-[#a1a1a6] text-sm sm:text-base">{{ feature }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Content Section -->
        <div class="py-12 sm:py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Technical Specifications -->
                <div class="mt-0">
                    <h2 class="text-3xl sm:text-4xl font-semibold text-center text-[#1d1d1f] dark:text-[#f5f5f7] mb-8 sm:mb-12">
                        Technical Specifications
                    </h2>
                    <div class="max-w-4xl mx-auto bg-white dark:bg-[#1d1d1f] rounded-2xl sm:rounded-3xl shadow-xl overflow-hidden">
                        <div class="divide-y divide-[#d2d2d7] dark:divide-[#424245]">
                            <div
                                v-for="(spec, index) in product.specs"
                                :key="index"
                                class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-8 px-4 sm:px-8 py-4 sm:py-6 hover:bg-[#f5f5f7] dark:hover:bg-[#2d2d2d] transition-colors"
                            >
                                <div class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] text-sm sm:text-base">
                                    {{ spec.label }}
                                </div>
                                <div class="text-[#6e6e73] dark:text-[#a1a1a6] text-sm sm:text-base">
                                    {{ spec.value }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Reviews -->
                <div class="mt-16 sm:mt-24">
                    <div class="text-center mb-8 sm:mb-12">
                        <h2 class="text-3xl sm:text-4xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-3 sm:mb-4">
                            Customer Reviews
                        </h2>
                        <div class="flex flex-col sm:flex-row items-center justify-center space-y-2 sm:space-y-0 sm:space-x-2">
                            <div class="flex space-x-1">
                                <svg v-for="i in 5" :key="i" class="w-5 h-5 sm:w-6 sm:h-6 text-[#ffcc00]" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                            <span class="text-lg sm:text-xl text-[#1d1d1f] dark:text-[#f5f5f7] font-semibold">4.9 out of 5</span>
                            <span class="text-[#86868b] dark:text-[#6e6e73] text-sm sm:text-base">Based on {{ reviews.length }} reviews</span>
                        </div>
                    </div>

                    <div class="max-w-5xl mx-auto space-y-4 sm:space-y-6">
                        <div
                            v-for="(review, index) in reviews"
                            :key="index"
                            class="bg-white dark:bg-[#1d1d1f] rounded-xl sm:rounded-2xl p-4 sm:p-6 lg:p-8 shadow-lg hover:shadow-xl transition-shadow"
                        >
                            <div class="flex items-start justify-between mb-3 sm:mb-4">
                                <div class="flex-1">
                                    <div class="flex flex-col sm:flex-row sm:items-center space-y-1 sm:space-y-0 sm:space-x-3 mb-2">
                                        <h4 class="font-semibold text-base sm:text-lg text-[#1d1d1f] dark:text-[#f5f5f7]">
                                            {{ review.name }}
                                        </h4>
                                        <span v-if="review.verified" class="inline-flex items-center space-x-1 text-xs text-[#06c] dark:text-[#2997ff] self-start">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            <span>Verified Purchase</span>
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <div class="flex space-x-0.5">
                                            <svg v-for="i in review.rating" :key="i" class="w-3 h-3 sm:w-4 sm:h-4 text-[#ffcc00]" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </div>
                                        <span class="text-xs sm:text-sm text-[#86868b] dark:text-[#6e6e73]">{{ review.date }}</span>
                                    </div>
                                </div>
                            </div>
                            <h5 class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-2 text-sm sm:text-base">
                                {{ review.title }}
                            </h5>
                            <p class="text-[#6e6e73] dark:text-[#a1a1a6] leading-relaxed text-sm sm:text-base">
                                {{ review.comment }}
                            </p>
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

        <!-- Footer -->
        <footer class="bg-[#f5f5f7] dark:bg-[#1d1d1f] mt-16 sm:mt-24 border-t border-[#d2d2d7] dark:border-[#424245]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Main Footer Content -->
                <div class="py-12 sm:py-16">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 sm:gap-12">
                        <!-- Company Info -->
                        <div class="lg:col-span-1">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="w-10 h-10 bg-gradient-to-r from-[#0071e3] to-[#0077ed] rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-[#1d1d1f] dark:text-[#f5f5f7]">Zan Store LTD</h3>
                                    <p class="text-sm text-[#86868b] dark:text-[#6e6e73]">Premium Electronics & Technology</p>
                                </div>
                            </div>
                            <p class="text-sm text-[#86868b] dark:text-[#6e6e73] mb-6 max-w-md">
                                Your trusted partner for premium electronics and cutting-edge technology. We deliver excellence in every product.
                            </p>
                            <div class="space-y-2">
                                <div class="flex items-center space-x-3 text-sm text-[#86868b] dark:text-[#6e6e73]">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span>123 Technology Avenue, Silicon Valley, CA 94043</span>
                                </div>
                                <div class="flex items-center space-x-3 text-sm text-[#86868b] dark:text-[#6e6e73]">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <span>+1 (555) 123-4567</span>
                                </div>
                                <div class="flex items-center space-x-3 text-sm text-[#86868b] dark:text-[#6e6e73]">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <span>support@zanstore.com</span>
                                </div>
                            </div>
                        </div>


                        <!-- Customer Service -->
                        <div>
                            <h4 class="text-sm font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-4">Customer Service</h4>
                            <ul class="space-y-3">
                                <li><a href="/shipping-info" class="text-sm text-[#86868b] dark:text-[#6e6e73] hover:text-[#0071e3] dark:hover:text-[#2997ff] transition-colors">Shipping Info</a></li>
                                <li><a href="/returns" class="text-sm text-[#86868b] dark:text-[#6e6e73] hover:text-[#0071e3] dark:hover:text-[#2997ff] transition-colors">Returns</a></li>
                                <li><a href="/warranty" class="text-sm text-[#86868b] dark:text-[#6e6e73] hover:text-[#0071e3] dark:hover:text-[#2997ff] transition-colors">Warranty</a></li>
                                <li><a href="/faq" class="text-sm text-[#86868b] dark:text-[#6e6e73] hover:text-[#0071e3] dark:hover:text-[#2997ff] transition-colors">FAQ</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Bottom Footer -->
                <div class="py-6 border-t border-[#d2d2d7] dark:border-[#424245]">
                    <div class="flex flex-col sm:flex-row items-center justify-between space-y-4 sm:space-y-0">
                        <div class="text-center sm:text-left">
                <p class="text-xs sm:text-sm text-[#6e6e73] dark:text-[#a1a1a6]">
                                © 2025 Zan Store LTD. All rights reserved.
                            </p>
                        </div>
                        <div class="flex items-center space-x-6">
                            <a href="/privacy-policy" class="text-xs sm:text-sm text-[#6e6e73] dark:text-[#a1a1a6] hover:text-[#0071e3] dark:hover:text-[#2997ff] transition-colors">Privacy Policy</a>
                            <a href="#" class="text-xs sm:text-sm text-[#6e6e73] dark:text-[#a1a1a6] hover:text-[#0071e3] dark:hover:text-[#2997ff] transition-colors">Terms of Service</a>
                            <a href="#" class="text-xs sm:text-sm text-[#6e6e73] dark:text-[#a1a1a6] hover:text-[#0071e3] dark:hover:text-[#2997ff] transition-colors">Cookies</a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>

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

        <!-- Checkout Modal -->
        <div v-if="isCheckoutOpen" @click="closeCheckout" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm animate-in fade-in duration-300 p-2 sm:p-4">
            <div @click.stop class="bg-white dark:bg-[#1d1d1f] rounded-2xl shadow-2xl w-full max-w-2xl mx-2 sm:mx-4 max-h-[95vh] overflow-y-auto transform transition-all duration-300 scale-100 animate-in zoom-in-95 duration-300">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-4 sm:p-6 border-b border-[#d2d2d7] dark:border-[#424245]">
                    <h2 class="text-lg sm:text-xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7]">Checkout Information</h2>
                    <button @click="closeCheckout" class="text-[#86868b] dark:text-[#a1a1a6] hover:text-[#1d1d1f] dark:hover:text-[#f5f5f7] transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                        <!-- Order Summary -->
                        <div class="bg-[#f5f5f7] dark:bg-[#2d2d2d] rounded-lg p-4">
                            <h3 class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-4">Order Summary</h3>
                            <div class="text-sm text-[#86868b] dark:text-[#a1a1a6] space-y-2">
                                <div class="flex justify-between">
                                    <span>iPhone 17 Pro Max 512GB</span>
                                    <span>{{ quantity }}x</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Color: {{ getSelectedColor() }}</span>
                                    <span>{{ formatPrice(totalPrice) }}</span>
                                </div>
                                <div class="border-t border-[#d2d2d7] dark:border-[#424245] pt-2 flex justify-between font-semibold">
                                    <span>Total:</span>
                                    <span>{{ formatPrice(totalPrice) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Form Fields -->
                        <div class="space-y-3 sm:space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-[#1d1d1f] dark:text-[#f5f5f7] mb-2">
                                Full Name *
                            </label>
                            <input
                                v-model="checkoutForm.customer_name"
                                type="text"
                                required
                                class="w-full px-3 py-2 text-sm sm:text-base border border-[#d2d2d7] dark:border-[#424245] rounded-lg bg-white dark:bg-[#2d2d2d] text-[#1d1d1f] dark:text-[#f5f5f7] focus:outline-none focus:ring-2 focus:ring-[#0071e3] dark:focus:ring-[#0a84ff]"
                                placeholder="Enter your full name"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-[#1d1d1f] dark:text-[#f5f5f7] mb-2">
                                Email Address *
                            </label>
                            <input
                                v-model="checkoutForm.customer_email"
                                type="email"
                                required
                                class="w-full px-3 py-2 text-sm sm:text-base border border-[#d2d2d7] dark:border-[#424245] rounded-lg bg-white dark:bg-[#2d2d2d] text-[#1d1d1f] dark:text-[#f5f5f7] focus:outline-none focus:ring-2 focus:ring-[#0071e3] dark:focus:ring-[#0a84ff]"
                                placeholder="Enter your email address"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-[#1d1d1f] dark:text-[#f5f5f7] mb-2">
                                Phone Number *
                            </label>
                            <input
                                v-model="checkoutForm.customer_phone"
                                type="tel"
                                required
                                class="w-full px-3 py-2 text-sm sm:text-base border border-[#d2d2d7] dark:border-[#424245] rounded-lg bg-white dark:bg-[#2d2d2d] text-[#1d1d1f] dark:text-[#f5f5f7] focus:outline-none focus:ring-2 focus:ring-[#0071e3] dark:focus:ring-[#0a84ff]"
                                placeholder="Enter your phone number"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-[#1d1d1f] dark:text-[#f5f5f7] mb-2">
                                Shipping Address *
                            </label>
                            <textarea
                                v-model="checkoutForm.shipping_address"
                                required
                                rows="3"
                                class="w-full px-3 py-2 text-sm sm:text-base border border-[#d2d2d7] dark:border-[#424245] rounded-lg bg-white dark:bg-[#2d2d2d] text-[#1d1d1f] dark:text-[#f5f5f7] focus:outline-none focus:ring-2 focus:ring-[#0071e3] dark:focus:ring-[#0a84ff] resize-none"
                                placeholder="Enter your complete shipping address"
                            ></textarea>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 sm:p-6 border-t border-[#d2d2d7] dark:border-[#424245]">
                    <!-- PayPal Buttons -->
                    <div id="paypal-container-7WMANWRCHP896"></div>
                </div>
            </div>
        </div>
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

/* PayPal container styling */
#paypal-container-7WMANWRCHP896 {
    min-height: 50px;
}
</style>

