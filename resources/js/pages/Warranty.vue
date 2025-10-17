<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, nextTick } from 'vue';

// Chatbot state
const isChatOpen = ref(false);
const chatMessages = ref([
    {
        id: 1,
        type: 'bot',
        message: 'Hi! 👋 How can I help you today?',
        timestamp: 'Just now'
    }
]);
const messageInput = ref('');
const isTyping = ref(false);
const isWaitingForEmail = ref(false);

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
    const deliveryDate = new Date(order.getTime() + (21 * 24 * 60 * 60 * 1000));

    const daysRemaining = Math.ceil((deliveryDate.getTime() - now.getTime()) / (24 * 60 * 60 * 1000));

    const formattedDeliveryDate = deliveryDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

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

    addMessage('user', action === 'delivery' ? 'When will it be delivered?' :
                        action === 'shipment' ? 'Where\'s my shipment?' :
                        action === 'specifications' ? 'Tell me about specifications' :
                        'Payment information');

    setTimeout(() => {
        addMessageWithTyping('bot', response, 1500);
    }, 500);
};

// Handle email input for order tracking
const handleEmailInput = async (email: string) => {
    if (!isWaitingForEmail.value) return;

    addMessage('user', email);

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

    setTimeout(() => {
        addMessageWithTyping('bot', response, 2000);
        isWaitingForEmail.value = false;
    }, 1000);
};

// Handle message submission
const handleMessageSubmit = () => {
    if (!messageInput.value.trim()) return;

    if (isWaitingForEmail.value) {
        handleEmailInput(messageInput.value.trim());
    } else {
        addMessage('user', messageInput.value.trim());
        setTimeout(() => {
            addMessageWithTyping('bot', 'I understand your message. How can I help you further?', 1500);
        }, 500);
    }

    messageInput.value = '';
};

// Toggle chat
const toggleChat = () => {
    isChatOpen.value = !isChatOpen.value;
    if (isChatOpen.value) {
        nextTick(() => {
            scrollToBottom();
        });
    }
};

// FAQ items
const faqs = ref([
    {
        question: 'What does the warranty cover?',
        answer: 'Our warranty covers manufacturing defects, hardware malfunctions, and performance issues under normal use. This includes defects in materials and workmanship.',
        isOpen: false
    },
    {
        question: 'How long is the warranty period?',
        answer: 'All products come with a standard 12-month manufacturer warranty from the date of purchase. Extended warranty options are available at checkout.',
        isOpen: false
    },
    {
        question: 'What is NOT covered by the warranty?',
        answer: 'The warranty does not cover accidental damage, water damage, cosmetic damage, unauthorized modifications, or damage from misuse or abuse.',
        isOpen: false
    },
    {
        question: 'How do I claim my warranty?',
        answer: 'Contact our support team with your order number and a description of the issue. We\'ll guide you through the warranty claim process and provide repair or replacement options.',
        isOpen: false
    },
    {
        question: 'Do I need to register my product?',
        answer: 'Product registration is automatic upon purchase. However, we recommend keeping your order confirmation email as proof of purchase for warranty claims.',
        isOpen: false
    }
]);

const toggleFaq = (index: number) => {
    faqs.value[index].isOpen = !faqs.value[index].isOpen;
};
</script>

<template>
    <Head title="Warranty Information" />

    <div class="min-h-screen bg-[#fbfbfd] dark:bg-[#000000]">
        <!-- Header -->
        <header class="sticky top-0 z-50 bg-[#fbfbfd]/80 dark:bg-[#000000]/80 backdrop-blur-xl border-b border-[#d2d2d7] dark:border-[#424245]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-12 sm:h-16">
                    <a href="/" class="text-lg sm:text-xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] hover:text-[#06c] dark:hover:text-[#2997ff] transition-colors">
                        Zan Store
                    </a>
                    <a href="/" class="text-xs sm:text-sm text-[#06c] dark:text-[#2997ff] hover:underline">
                        ← Back to Store
                    </a>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="py-12 sm:py-20 bg-gradient-to-b from-white to-[#fbfbfd] dark:from-[#000000] dark:to-[#1d1d1f]">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 bg-[#0071e3] dark:bg-[#0a84ff] rounded-full mb-6 sm:mb-8">
                    <svg class="w-8 h-8 sm:w-10 sm:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h1 class="text-3xl sm:text-5xl md:text-6xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-4 sm:mb-6">
                    Warranty Information
                </h1>
                <p class="text-lg sm:text-xl md:text-2xl text-[#86868b] dark:text-[#a1a1a6] max-w-2xl mx-auto">
                    Comprehensive coverage for peace of mind. We stand behind our products.
                </p>
            </div>
        </section>

        <!-- Main Content -->
        <section class="py-12 sm:py-16">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Warranty Overview -->
                <div class="bg-white dark:bg-[#1d1d1f] rounded-3xl shadow-sm border border-[#d2d2d7] dark:border-[#424245] p-6 sm:p-10 mb-8 sm:mb-12">
                    <h2 class="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-6 sm:mb-8">
                        Our Warranty Promise
                    </h2>

                    <div class="prose prose-lg dark:prose-invert max-w-none">
                        <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed mb-6">
                            At Zan Store, we're committed to providing you with high-quality products that last. Every product comes with our comprehensive warranty that protects you against manufacturing defects and ensures your investment is secure.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8 mt-8">
                            <div class="text-center">
                                <div class="w-16 h-16 bg-[#0071e3]/10 dark:bg-[#0a84ff]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-[#0071e3] dark:text-[#0a84ff]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-2">12 Months</h3>
                                <p class="text-sm text-[#86868b] dark:text-[#a1a1a6]">Standard warranty coverage</p>
                            </div>

                            <div class="text-center">
                                <div class="w-16 h-16 bg-[#0071e3]/10 dark:bg-[#0a84ff]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-[#0071e3] dark:text-[#0a84ff]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-2">Free Repairs</h3>
                                <p class="text-sm text-[#86868b] dark:text-[#a1a1a6]">No cost for covered repairs</p>
                            </div>

                            <div class="text-center">
                                <div class="w-16 h-16 bg-[#0071e3]/10 dark:bg-[#0a84ff]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-[#0071e3] dark:text-[#0a84ff]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-2">Easy Exchange</h3>
                                <p class="text-sm text-[#86868b] dark:text-[#a1a1a6]">Quick replacement process</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- What's Covered -->
                <div class="bg-white dark:bg-[#1d1d1f] rounded-3xl shadow-sm border border-[#d2d2d7] dark:border-[#424245] p-6 sm:p-10 mb-8 sm:mb-12">
                    <h2 class="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-6 sm:mb-8">
                        What's Covered
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-1">
                                    Manufacturing Defects
                                </h3>
                                <p class="text-sm text-[#86868b] dark:text-[#a1a1a6]">
                                    Defects in materials or workmanship
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-1">
                                    Hardware Malfunctions
                                </h3>
                                <p class="text-sm text-[#86868b] dark:text-[#a1a1a6]">
                                    Component failures under normal use
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-1">
                                    Performance Issues
                                </h3>
                                <p class="text-sm text-[#86868b] dark:text-[#a1a1a6]">
                                    Product not performing as specified
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-1">
                                    Battery Issues
                                </h3>
                                <p class="text-sm text-[#86868b] dark:text-[#a1a1a6]">
                                    Defective battery or charging problems
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- What's NOT Covered -->
                <div class="bg-white dark:bg-[#1d1d1f] rounded-3xl shadow-sm border border-[#d2d2d7] dark:border-[#424245] p-6 sm:p-10 mb-8 sm:mb-12">
                    <h2 class="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-6 sm:mb-8">
                        What's NOT Covered
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-[#86868b] dark:text-[#a1a1a6]">Accidental damage (drops, impacts)</span>
                        </div>

                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-[#86868b] dark:text-[#a1a1a6]">Water or liquid damage</span>
                        </div>

                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-[#86868b] dark:text-[#a1a1a6]">Cosmetic damage (scratches, dents)</span>
                        </div>

                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-[#86868b] dark:text-[#a1a1a6]">Unauthorized repairs or modifications</span>
                        </div>

                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-[#86868b] dark:text-[#a1a1a6]">Normal wear and tear</span>
                        </div>

                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-[#86868b] dark:text-[#a1a1a6]">Misuse or abuse of product</span>
                        </div>
                    </div>
                </div>

                <!-- Warranty Claim Process -->
                <div class="bg-white dark:bg-[#1d1d1f] rounded-3xl shadow-sm border border-[#d2d2d7] dark:border-[#424245] p-6 sm:p-10 mb-8 sm:mb-12">
                    <h2 class="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-6 sm:mb-8">
                        How to Claim Your Warranty
                    </h2>

                    <div class="space-y-6 sm:space-y-8">
                        <!-- Step 1 -->
                        <div class="flex items-start space-x-4 sm:space-x-6">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-[#0071e3] dark:bg-[#0a84ff] rounded-full flex items-center justify-center">
                                    <span class="text-white font-semibold text-sm sm:text-base">1</span>
                                </div>
                            </div>
                            <div class="flex-1 pt-1">
                                <h3 class="text-lg sm:text-xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-2">
                                    Contact Support
                                </h3>
                                <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed">
                                    Reach out to our support team with your order number and a detailed description of the issue. Include photos if possible.
                                </p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="flex items-start space-x-4 sm:space-x-6">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-[#0071e3] dark:bg-[#0a84ff] rounded-full flex items-center justify-center">
                                    <span class="text-white font-semibold text-sm sm:text-base">2</span>
                                </div>
                            </div>
                            <div class="flex-1 pt-1">
                                <h3 class="text-lg sm:text-xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-2">
                                    Diagnostic Review
                                </h3>
                                <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed">
                                    Our technical team will review your case and may request additional information or diagnostics to assess the issue.
                                </p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="flex items-start space-x-4 sm:space-x-6">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-[#0071e3] dark:bg-[#0a84ff] rounded-full flex items-center justify-center">
                                    <span class="text-white font-semibold text-sm sm:text-base">3</span>
                                </div>
                            </div>
                            <div class="flex-1 pt-1">
                                <h3 class="text-lg sm:text-xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-2">
                                    Approval & Shipping
                                </h3>
                                <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed">
                                    Once approved, we'll send you a prepaid shipping label to return the product for repair or replacement.
                                </p>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="flex items-start space-x-4 sm:space-x-6">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-[#0071e3] dark:bg-[#0a84ff] rounded-full flex items-center justify-center">
                                    <span class="text-white font-semibold text-sm sm:text-base">4</span>
                                </div>
                            </div>
                            <div class="flex-1 pt-1">
                                <h3 class="text-lg sm:text-xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-2">
                                    Resolution
                                </h3>
                                <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed">
                                    We'll repair or replace your product and ship it back to you within 7-10 business days. All shipping costs are covered by us.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQs -->
                <div class="bg-white dark:bg-[#1d1d1f] rounded-3xl shadow-sm border border-[#d2d2d7] dark:border-[#424245] p-6 sm:p-10">
                    <h2 class="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-6 sm:mb-8">
                        Frequently Asked Questions
                    </h2>

                    <div class="space-y-4">
                        <div v-for="(faq, index) in faqs" :key="index" class="border-b border-[#d2d2d7] dark:border-[#424245] last:border-0 pb-4 last:pb-0">
                            <button @click="toggleFaq(index)" class="w-full flex items-center justify-between py-3 text-left">
                                <span class="text-base sm:text-lg font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] pr-4">
                                    {{ faq.question }}
                                </span>
                                <svg :class="['w-5 h-5 sm:w-6 sm:h-6 text-[#86868b] dark:text-[#a1a1a6] transition-transform flex-shrink-0', faq.isOpen ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div v-show="faq.isOpen" class="pb-3">
                                <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed">
                                    {{ faq.answer }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Support -->
                <div class="mt-8 sm:mt-12 text-center">
                    <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] mb-4">
                        Have questions about your warranty?
                    </p>
                    <button @click="toggleChat" class="inline-block px-6 sm:px-8 py-3 sm:py-4 bg-[#0071e3] dark:bg-[#0a84ff] text-white text-sm sm:text-base font-semibold rounded-full hover:bg-[#0077ed] dark:hover:bg-[#409cff] transition-colors">
                        Chat with Support
                    </button>
                </div>
            </div>
        </section>

        <!-- Chatbot Icon -->
        <button @click="toggleChat" class="fixed bottom-6 right-6 z-50 w-14 h-14 bg-[#0071e3] dark:bg-[#0a84ff] rounded-full flex items-center justify-center shadow-lg hover:bg-[#0077ed] dark:hover:bg-[#409cff] transition-all hover:scale-110">
            <svg v-if="!isChatOpen" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
            <svg v-else class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <!-- Chat Window -->
        <div v-if="isChatOpen" class="fixed bottom-24 right-6 z-50 w-96 max-w-[calc(100vw-3rem)] bg-white dark:bg-[#1d1d1f] rounded-2xl shadow-2xl border border-[#d2d2d7] dark:border-[#424245] flex flex-col animate-in slide-in-from-bottom-5 duration-300" style="height: 600px; max-height: calc(100vh - 8rem);">
            <!-- Chat Header -->
            <div class="p-4 border-b border-[#d2d2d7] dark:border-[#424245] flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-[#0071e3] dark:bg-[#0a84ff] rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] text-sm">Zan Store Support</h3>
                        <p class="text-xs text-[#86868b] dark:text-[#a1a1a6]">Online now</p>
                    </div>
                </div>
                <button @click="toggleChat" class="w-8 h-8 flex items-center justify-center hover:bg-[#f5f5f7] dark:hover:bg-[#2d2d2d] rounded-full transition-colors">
                    <svg class="w-5 h-5 text-[#86868b] dark:text-[#a1a1a6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <footer class="bg-[#f5f5f7] dark:bg-[#1d1d1f] mt-16 sm:mt-24 border-t border-[#d2d2d7] dark:border-[#424245]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="text-center">
                    <p class="text-xs sm:text-sm text-[#6e6e73] dark:text-[#a1a1a6]">
                        © 2025 Zan Store LTD. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Smooth transitions */
* {
    transition: background-color 0.3s ease, color 0.3s ease;
}
</style>

