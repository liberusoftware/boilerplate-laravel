@extends('messages.layout')

@section('content')
<div class="py-12" x-data="messagesApp()">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">Messages</h2>
                    <button @click="showNewMessageModal = true" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                        New Message
                    </button>
                </div>

                <!-- Loading State -->
                <div x-show="loading" class="text-center py-8">
                    <div class="text-gray-500 dark:text-gray-400">Loading conversations...</div>
                </div>

                <!-- Conversations List -->
                <div x-show="!loading && conversations.length > 0" class="space-y-4">
                    <template x-for="conversation in conversations" :key="conversation.user?.id">
                        <a :href="`/messages/${conversation.user?.id}`" 
                           class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200" 
                                            x-text="conversation.user?.name"></h3>
                                        <span x-show="conversation.unread_count > 0" 
                                              class="ml-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full"
                                              x-text="conversation.unread_count"></span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1" 
                                       x-text="conversation.last_message?.body?.substring(0, 100)"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1" 
                                       x-text="formatDate(conversation.last_message?.created_at)"></p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </a>
                    </template>
                </div>

                <!-- Empty State -->
                <div x-show="!loading && conversations.length === 0" 
                     class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No messages</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Start a conversation by clicking "New Message"</p>
                </div>
            </div>
        </div>
    </div>

    <!-- New Message Modal -->
    <div x-show="showNewMessageModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 @click="showNewMessageModal = false"></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-lg w-full p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">New Message</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Select User
                        </label>
                        <select x-model="newMessage.recipient_id" 
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Choose a user...</option>
                            <template x-for="user in users" :key="user.id">
                                <option :value="user.id" x-text="user.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Message
                        </label>
                        <textarea x-model="newMessage.body" 
                                  rows="4"
                                  class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                  placeholder="Type your message..."></textarea>
                    </div>

                    <div x-show="error" class="text-sm text-red-600" x-text="error"></div>

                    <div class="flex justify-end space-x-3">
                        <button @click="showNewMessageModal = false" 
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button @click="sendNewMessage" 
                                :disabled="sending"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50">
                            <span x-show="!sending">Send</span>
                            <span x-show="sending">Sending...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function messagesApp() {
    return {
        conversations: [],
        users: [],
        loading: true,
        showNewMessageModal: false,
        newMessage: {
            recipient_id: '',
            body: ''
        },
        sending: false,
        error: '',

        init() {
            this.loadConversations();
            this.loadUsers();
        },

        async loadConversations() {
            try {
                const response = await fetch('/api/messages', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                this.conversations = await response.json();
            } catch (error) {
                console.error('Error loading conversations:', error);
            } finally {
                this.loading = false;
            }
        },

        async loadUsers() {
            try {
                const response = await fetch('/api/messages/users', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                this.users = await response.json();
            } catch (error) {
                console.error('Error loading users:', error);
            }
        },

        async sendNewMessage() {
            if (!this.newMessage.recipient_id || !this.newMessage.body) {
                this.error = 'Please select a user and enter a message';
                return;
            }

            this.sending = true;
            this.error = '';

            try {
                const response = await fetch('/api/messages', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.newMessage)
                });

                if (response.ok) {
                    window.location.href = `/messages/${this.newMessage.recipient_id}`;
                } else {
                    const data = await response.json();
                    this.error = data.message || 'Error sending message';
                }
            } catch (error) {
                this.error = 'Error sending message';
                console.error('Error:', error);
            } finally {
                this.sending = false;
            }
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            
            if (days === 0) {
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } else if (days === 1) {
                return 'Yesterday';
            } else if (days < 7) {
                return `${days} days ago`;
            } else {
                return date.toLocaleDateString();
            }
        }
    }
}
</script>
@endsection
