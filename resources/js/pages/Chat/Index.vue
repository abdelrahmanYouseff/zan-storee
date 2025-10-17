<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue';

const props = defineProps<{
    sessions: Array<{
        session_id: string;
        customer_email: string;
        last_message: string;
        last_message_time: string;
        message_count: number;
        unread_count: number;
    }>;
}>();

const selectedSession = ref<string | null>(null);
const messages = ref<Array<any>>([]);
const newMessage = ref('');
const isLoading = ref(false);
const pollingInterval = ref<any>(null);
const sessionsPollingInterval = ref<any>(null);

// Local sessions list that can be updated
const sessionsList = ref([...props.sessions]);

// Select a chat session
const selectSession = async (sessionId: string) => {
    selectedSession.value = sessionId;
    await loadMessages(sessionId);
};

// Load messages for selected session
const loadMessages = async (sessionId: string) => {
    isLoading.value = true;
    try {
        const response = await fetch(`/api/chat/${sessionId}/messages`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });
        const data = await response.json();
        if (data.success) {
            messages.value = data.messages;
            nextTick(() => {
                scrollToBottom();
            });
        }
    } catch (error) {
        console.error('Error loading messages:', error);
    } finally {
        isLoading.value = false;
    }
};

// Send message
const sendMessage = async () => {
    if (!newMessage.value.trim() || !selectedSession.value) return;

    const messageText = newMessage.value;
    newMessage.value = '';

    try {
        const response = await fetch('/api/chat/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                session_id: selectedSession.value,
                message: messageText
            })
        });

        const data = await response.json();
        if (data.success) {
            messages.value.push(data.message);
            nextTick(() => {
                scrollToBottom();
            });
        }
    } catch (error) {
        console.error('Error sending message:', error);
        newMessage.value = messageText; // Restore message on error
    }
};

// Scroll to bottom of messages
const scrollToBottom = () => {
    const container = document.querySelector('.messages-container');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
};

// Format time
const formatTime = (timestamp: string) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    return date.toLocaleDateString();
};

// Selected session data
const selectedSessionData = computed(() => {
    return sessionsList.value.find(s => s.session_id === selectedSession.value);
});

// Fetch updated sessions list
const fetchSessions = async () => {
    try {
        const response = await fetch('/api/chat/sessions', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });
        const data = await response.json();
        if (data.success) {
            sessionsList.value = data.sessions;
        }
    } catch (error) {
        console.error('Error fetching sessions:', error);
    }
};

// Poll for new messages
const startPolling = () => {
    pollingInterval.value = setInterval(async () => {
        if (selectedSession.value) {
            await loadMessages(selectedSession.value);
        }
    }, 5000); // Poll every 5 seconds
};

const stopPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
};

// Poll for new sessions
const startSessionsPolling = () => {
    sessionsPollingInterval.value = setInterval(async () => {
        await fetchSessions();
    }, 3000); // Poll every 3 seconds for new sessions
};

const stopSessionsPolling = () => {
    if (sessionsPollingInterval.value) {
        clearInterval(sessionsPollingInterval.value);
    }
};

onMounted(() => {
    startPolling();
    startSessionsPolling();
});

onUnmounted(() => {
    stopPolling();
    stopSessionsPolling();
});

// Delete chat session
const deleteSession = async (sessionId: string) => {
    if (!confirm('Are you sure you want to delete this conversation? This action cannot be undone.')) {
        return;
    }

    try {
        const response = await fetch(`/api/chat/${sessionId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });

        const data = await response.json();
        if (data.success) {
            // Remove session from local list
            sessionsList.value = sessionsList.value.filter(s => s.session_id !== sessionId);

            // Clear selection if deleted session was selected
            if (selectedSession.value === sessionId) {
                selectedSession.value = null;
                messages.value = [];
            }
        }
    } catch (error) {
        console.error('Error deleting session:', error);
        alert('Failed to delete conversation. Please try again.');
    }
};
</script>

<template>
    <Head title="ChatBot - Customer Support" />

    <AppSidebarLayout>
        <div class="flex h-[calc(100vh-4rem)] bg-gray-50 dark:bg-gray-900">
            <!-- Sessions List -->
            <div class="w-80 border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex flex-col">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Customer Chats</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ sessionsList.length }} active conversations</p>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div
                        v-for="session in sessionsList"
                        :key="session.session_id"
                        :class="[
                            'p-4 border-b border-gray-200 dark:border-gray-700 transition-colors group relative',
                            selectedSession === session.session_id
                                ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-l-blue-500'
                                : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
                        ]"
                    >
                        <div @click="selectSession(session.session_id)" class="cursor-pointer">
                            <div class="flex items-start justify-between mb-1">
                                <div class="flex items-center space-x-2">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                                        {{ session.customer_email.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                            {{ session.customer_email }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ formatTime(session.last_message_time) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span v-if="session.unread_count > 0" class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full">
                                        {{ session.unread_count }}
                                    </span>
                                    <button
                                        @click.stop="deleteSession(session.session_id)"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity p-1.5 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg"
                                        title="Delete conversation"
                                    >
                                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                {{ session.last_message }}
                            </p>
                        </div>
                    </div>

                    <div v-if="sessionsList.length === 0" class="p-8 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">No active chats yet</p>
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Customer messages will appear here</p>
                    </div>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="flex-1 flex flex-col">
                <!-- Chat Header -->
                <div v-if="selectedSession" class="p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                                {{ selectedSessionData?.customer_email.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ selectedSessionData?.customer_email }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ selectedSessionData?.message_count }} messages
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                <span class="w-2 h-2 bg-green-400 rounded-full mr-1.5"></span>
                                Online
                            </span>
                            <button
                                @click="deleteSession(selectedSession)"
                                class="p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                title="Delete conversation"
                            >
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <div v-if="selectedSession" class="flex-1 overflow-y-auto p-4 space-y-4 messages-container bg-gray-50 dark:bg-gray-900">
                    <div v-if="isLoading" class="flex items-center justify-center h-full">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                    </div>

                    <div v-else>
                        <div
                            v-for="message in messages"
                            :key="message.id"
                            :class="[
                                'flex',
                                message.sender_type === 'admin' ? 'justify-end' : 'justify-start'
                            ]"
                        >
                            <div :class="[
                                'max-w-xs lg:max-w-md xl:max-w-lg px-4 py-2 rounded-2xl',
                                message.sender_type === 'admin'
                                    ? 'bg-blue-500 text-white rounded-tr-sm'
                                    : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-tl-sm shadow-sm'
                            ]">
                                <p class="text-sm whitespace-pre-wrap break-words">{{ message.message }}</p>
                                <p :class="[
                                    'text-xs mt-1',
                                    message.sender_type === 'admin' ? 'text-blue-100' : 'text-gray-500 dark:text-gray-400'
                                ]">
                                    {{ formatTime(message.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="flex-1 flex items-center justify-center bg-gray-50 dark:bg-gray-900">
                    <div class="text-center">
                        <svg class="w-24 h-24 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Select a conversation</h3>
                        <p class="text-gray-500 dark:text-gray-400">Choose a chat from the left to start messaging</p>
                    </div>
                </div>

                <!-- Message Input -->
                <div v-if="selectedSession" class="p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <form @submit.prevent="sendMessage" class="flex items-end space-x-2">
                        <div class="flex-1">
                            <textarea
                                v-model="newMessage"
                                @keydown.enter.exact.prevent="sendMessage"
                                placeholder="Type your message..."
                                rows="1"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                            ></textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Press Enter to send, Shift+Enter for new line</p>
                        </div>
                        <button
                            type="submit"
                            :disabled="!newMessage.trim()"
                            class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center space-x-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            <span>Send</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>

<style scoped>
.messages-container {
    scroll-behavior: smooth;
}

.messages-container::-webkit-scrollbar {
    width: 6px;
}

.messages-container::-webkit-scrollbar-track {
    background: transparent;
}

.messages-container::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 3px;
}

.dark .messages-container::-webkit-scrollbar-thumb {
    background: #4a5568;
}
</style>

