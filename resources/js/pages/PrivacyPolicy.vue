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
            hasAdminResponded.value = true;

            data.messages.forEach((msg: any) => {
                addMessageWithTyping('bot', msg.message, 500);
            });
        }
    } catch (error) {
        console.error('Error polling for admin responses:', error);
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

const showTypingIndicator = () => {
    isTyping.value = true;
    nextTick(() => {
        scrollToBottom();
    });
};

const hideTypingIndicator = () => {
    isTyping.value = false;
};

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

const scrollToBottom = () => {
    const chatContainer = document.querySelector('.chat-messages');
    if (chatContainer) {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
};

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
        return data;
    } catch (error) {
        console.error('Error searching for order:', error);
        return null;
    }
};

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

const handleEmailInput = async (email: string) => {
    if (!isWaitingForEmail.value) return;

    addMessage('user', email);

    const orderData = await searchOrderByEmail(email);

    let response = '';
    if (orderData && orderData.success && orderData.order) {
        const deliveryStatus = calculateDeliveryStatus(orderData.order.created_at);

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
        response = `I couldn't find any orders associated with ${email}. 😔 Please double-check your email address or contact our support team if you believe this is an error. We're here to help! 💬`;
    }

    setTimeout(() => {
        addMessageWithTyping('bot', response, 2000);
        isWaitingForEmail.value = false;
    }, 1000);
};

const handleMessageSubmit = () => {
    if (!messageInput.value.trim()) return;

    const userMessage = messageInput.value.trim();

    if (isWaitingForEmail.value) {
        handleEmailInput(userMessage);
    } else {
        addMessage('user', userMessage);
        sendMessageToDatabase(userMessage, 'customer');

        if (!hasShownWelcomeMessage.value && !hasAdminResponded.value) {
            hasShownWelcomeMessage.value = true;
            setTimeout(() => {
                addMessageWithTyping('bot', 'Thank you for your message! Our support team will respond shortly. You can also use the quick action buttons below for instant answers.', 1500);
            }, 500);
        }
    }

    messageInput.value = '';
};

const toggleChat = () => {
    isChatOpen.value = !isChatOpen.value;
    if (isChatOpen.value) {
        nextTick(() => {
            scrollToBottom();
        });
    }
};
</script>

<template>
    <Head title="Privacy Policy" />

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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="text-3xl sm:text-5xl md:text-6xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-4 sm:mb-6">
                    Privacy Policy
                </h1>
                <p class="text-lg sm:text-xl md:text-2xl text-[#86868b] dark:text-[#a1a1a6] max-w-2xl mx-auto">
                    Your privacy matters to us. Learn how we collect, use, and protect your information.
                </p>
                <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] mt-4">
                    Last updated: October 16, 2025
                </p>
            </div>
        </section>

        <!-- Main Content -->
        <section class="py-12 sm:py-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Introduction -->
                <div class="bg-white dark:bg-[#1d1d1f] rounded-3xl shadow-sm border border-[#d2d2d7] dark:border-[#424245] p-6 sm:p-10 mb-8">
                    <h2 class="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-4">
                        Introduction
                    </h2>
                    <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed">
                        At Zan Store LTD ("we," "our," or "us"), we are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website or make a purchase from us.
                    </p>
                </div>

                <!-- Information We Collect -->
                <div class="bg-white dark:bg-[#1d1d1f] rounded-3xl shadow-sm border border-[#d2d2d7] dark:border-[#424245] p-6 sm:p-10 mb-8">
                    <h2 class="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-6">
                        Information We Collect
                    </h2>

                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg sm:text-xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-3">
                                Personal Information
                            </h3>
                            <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed mb-3">
                                When you place an order or interact with our website, we may collect:
                            </p>
                            <ul class="space-y-2 ml-6">
                                <li class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] flex items-start">
                                    <span class="text-[#0071e3] dark:text-[#0a84ff] mr-2">•</span>
                                    <span>Name and contact information (email address, phone number)</span>
                                </li>
                                <li class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] flex items-start">
                                    <span class="text-[#0071e3] dark:text-[#0a84ff] mr-2">•</span>
                                    <span>Shipping and billing addresses</span>
                                </li>
                                <li class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] flex items-start">
                                    <span class="text-[#0071e3] dark:text-[#0a84ff] mr-2">•</span>
                                    <span>Payment information (processed securely through PayPal)</span>
                                </li>
                                <li class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] flex items-start">
                                    <span class="text-[#0071e3] dark:text-[#0a84ff] mr-2">•</span>
                                    <span>Order history and preferences</span>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-lg sm:text-xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-3">
                                Automatically Collected Information
                            </h3>
                            <ul class="space-y-2 ml-6">
                                <li class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] flex items-start">
                                    <span class="text-[#0071e3] dark:text-[#0a84ff] mr-2">•</span>
                                    <span>IP address and location data (for currency conversion)</span>
                                </li>
                                <li class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] flex items-start">
                                    <span class="text-[#0071e3] dark:text-[#0a84ff] mr-2">•</span>
                                    <span>Browser type and device information</span>
                                </li>
                                <li class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] flex items-start">
                                    <span class="text-[#0071e3] dark:text-[#0a84ff] mr-2">•</span>
                                    <span>Website usage data and analytics</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- How We Use Your Information -->
                <div class="bg-white dark:bg-[#1d1d1f] rounded-3xl shadow-sm border border-[#d2d2d7] dark:border-[#424245] p-6 sm:p-10 mb-8">
                    <h2 class="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-6">
                        How We Use Your Information
                    </h2>

                    <div class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-[#0071e3] dark:text-[#0a84ff] flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed">
                                <strong class="text-[#1d1d1f] dark:text-[#f5f5f7]">Process Orders:</strong> To fulfill your purchases, process payments, and deliver products to you.
                            </p>
                        </div>

                        <div class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-[#0071e3] dark:text-[#0a84ff] flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed">
                                <strong class="text-[#1d1d1f] dark:text-[#f5f5f7]">Customer Service:</strong> To communicate with you about your orders, respond to inquiries, and provide support.
                            </p>
                        </div>

                        <div class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-[#0071e3] dark:text-[#0a84ff] flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed">
                                <strong class="text-[#1d1d1f] dark:text-[#f5f5f7]">Improve Services:</strong> To analyze usage patterns and enhance our website and services.
                            </p>
                        </div>

                        <div class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-[#0071e3] dark:text-[#0a84ff] flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed">
                                <strong class="text-[#1d1d1f] dark:text-[#f5f5f7]">Security:</strong> To detect and prevent fraud, abuse, or other harmful activities.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Data Security -->
                <div class="bg-white dark:bg-[#1d1d1f] rounded-3xl shadow-sm border border-[#d2d2d7] dark:border-[#424245] p-6 sm:p-10 mb-8">
                    <h2 class="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-6">
                        Data Security
                    </h2>
                    <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed mb-4">
                        We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. These measures include:
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6]">SSL encryption for all data transmission</span>
                        </div>
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6]">Secure payment processing via PayPal</span>
                        </div>
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6]">Regular security audits and updates</span>
                        </div>
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6]">Limited access to authorized personnel only</span>
                        </div>
                    </div>
                </div>

                <!-- Information Sharing -->
                <div class="bg-white dark:bg-[#1d1d1f] rounded-3xl shadow-sm border border-[#d2d2d7] dark:border-[#424245] p-6 sm:p-10 mb-8">
                    <h2 class="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-6">
                        Information Sharing
                    </h2>
                    <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed mb-4">
                        We do not sell, trade, or rent your personal information to third parties. We may share your information only in the following circumstances:
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <span class="text-[#0071e3] dark:text-[#0a84ff] font-semibold">→</span>
                            <span class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6]"><strong class="text-[#1d1d1f] dark:text-[#f5f5f7]">Service Providers:</strong> With trusted third-party vendors who assist in operating our website, processing payments, or delivering products.</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <span class="text-[#0071e3] dark:text-[#0a84ff] font-semibold">→</span>
                            <span class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6]"><strong class="text-[#1d1d1f] dark:text-[#f5f5f7]">Legal Obligations:</strong> When required by law or to protect our rights and safety.</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <span class="text-[#0071e3] dark:text-[#0a84ff] font-semibold">→</span>
                            <span class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6]"><strong class="text-[#1d1d1f] dark:text-[#f5f5f7]">Business Transfers:</strong> In connection with a merger, acquisition, or sale of assets.</span>
                        </li>
                    </ul>
                </div>

                <!-- Your Rights -->
                <div class="bg-white dark:bg-[#1d1d1f] rounded-3xl shadow-sm border border-[#d2d2d7] dark:border-[#424245] p-6 sm:p-10 mb-8">
                    <h2 class="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-6">
                        Your Rights
                    </h2>
                    <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed mb-4">
                        You have the right to:
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-[#f5f5f7] dark:bg-[#2d2d2d] rounded-xl">
                            <h3 class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-2">Access</h3>
                            <p class="text-sm text-[#86868b] dark:text-[#a1a1a6]">Request a copy of your personal data</p>
                        </div>
                        <div class="p-4 bg-[#f5f5f7] dark:bg-[#2d2d2d] rounded-xl">
                            <h3 class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-2">Correction</h3>
                            <p class="text-sm text-[#86868b] dark:text-[#a1a1a6]">Update or correct inaccurate information</p>
                        </div>
                        <div class="p-4 bg-[#f5f5f7] dark:bg-[#2d2d2d] rounded-xl">
                            <h3 class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-2">Deletion</h3>
                            <p class="text-sm text-[#86868b] dark:text-[#a1a1a6]">Request deletion of your personal data</p>
                        </div>
                        <div class="p-4 bg-[#f5f5f7] dark:bg-[#2d2d2d] rounded-xl">
                            <h3 class="font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-2">Opt-out</h3>
                            <p class="text-sm text-[#86868b] dark:text-[#a1a1a6]">Unsubscribe from marketing communications</p>
                        </div>
                    </div>
                </div>

                <!-- Cookies -->
                <div class="bg-white dark:bg-[#1d1d1f] rounded-3xl shadow-sm border border-[#d2d2d7] dark:border-[#424245] p-6 sm:p-10 mb-8">
                    <h2 class="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] dark:text-[#f5f5f7] mb-6">
                        Cookies and Tracking
                    </h2>
                    <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed mb-4">
                        We use cookies and similar tracking technologies to enhance your browsing experience, analyze website traffic, and personalize content. You can control cookie preferences through your browser settings.
                    </p>
                    <p class="text-sm sm:text-base text-[#86868b] dark:text-[#a1a1a6] leading-relaxed">
                        Our website uses cookies for: session management, currency preferences, shopping cart functionality, and analytics.
                    </p>
                </div>

                <!-- Contact Us -->
                <div class="bg-gradient-to-r from-[#0071e3] to-[#0077ed] dark:from-[#0a84ff] dark:to-[#409cff] rounded-3xl p-8 sm:p-10 text-center text-white">
                    <h2 class="text-2xl sm:text-3xl font-semibold mb-4">
                        Questions About Privacy?
                    </h2>
                    <p class="text-base sm:text-lg mb-6 opacity-90">
                        If you have any questions or concerns about our privacy practices, please don't hesitate to contact us.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button @click="toggleChat" class="px-6 sm:px-8 py-3 sm:py-4 bg-white text-[#0071e3] dark:text-[#0a84ff] text-sm sm:text-base font-semibold rounded-full hover:bg-gray-50 transition-colors">
                            💬 Chat with Support
                        </button>
                        <a href="mailto:privacy@zanstore.com" class="px-6 sm:px-8 py-3 sm:py-4 bg-white/10 text-white text-sm sm:text-base font-semibold rounded-full hover:bg-white/20 transition-colors border-2 border-white/30">
                            ✉️ Email Privacy Team
                        </a>
                    </div>
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
                    <div v-if="message.type === 'bot'" class="w-6 h-6 bg-[#0071e3] rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
                        </svg>
                    </div>

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

                    <div v-if="message.type === 'user'" class="w-6 h-6 bg-[#86868b] rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>

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
    </div>
</template>

<style scoped>
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
</style>

