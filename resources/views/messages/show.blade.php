@extends('messages.layout')

@section('content')
<div class="py-12" x-data="conversationApp({{ $user->id }})">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <!-- Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center">
                    <a href="{{ route('messages.index') }}" 
                       class="mr-4 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                        {{ $user->name }}
                    </h2>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="h-96 overflow-y-auto p-4 space-y-4" id="messagesContainer">
                <div x-show="loading" class="text-center py-8">
                    <div class="text-gray-500 dark:text-gray-400">Loading messages...</div>
                </div>

                <div x-show="!loading && messages.length === 0" class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">No messages yet. Start the conversation!</p>
                </div>

                <template x-for="message in messages" :key="message.id">
                    <div :class="message.sender_id == currentUserId ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="message.sender_id == currentUserId 
                            ? 'bg-indigo-600 text-white' 
                            : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200'"
                             class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg">
                            <p class="text-sm" x-text="message.body"></p>
                            <p class="text-xs mt-1 opacity-75" 
                               x-text="formatTime(message.created_at)"></p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Message Input -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <form @submit.prevent="sendMessage" class="flex space-x-2">
                    <input type="text" 
                           x-model="newMessageBody"
                           placeholder="Type a message..."
                           class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                           :disabled="sending">
                    <button type="submit" 
                            :disabled="!newMessageBody.trim() || sending"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!sending">Send</span>
                        <span x-show="sending">Sending...</span>
                    </button>
                </form>
                <div x-show="error" class="mt-2 text-sm text-red-600" x-text="error"></div>
            </div>
        </div>
    </div>
</div>

<script>
function conversationApp(recipientId) {
    return {
        messages: [],
        newMessageBody: '',
        loading: true,
        sending: false,
        error: '',
        recipientId: recipientId,
        currentUserId: @json(Auth::id()),

        init() {
            this.loadMessages();
        },

        async loadMessages() {
            try {
                const response = await fetch(`/api/messages/${this.recipientId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                this.messages = data.messages || [];
                this.scrollToBottom();
            } catch (error) {
                console.error('Error loading messages:', error);
                this.error = 'Error loading messages';
            } finally {
                this.loading = false;
            }
        },

        async sendMessage() {
            if (!this.newMessageBody.trim()) return;

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
                    body: JSON.stringify({
                        recipient_id: this.recipientId,
                        body: this.newMessageBody
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    this.messages.push(data.message);
                    this.newMessageBody = '';
                    this.scrollToBottom();
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

        scrollToBottom() {
            this.$nextTick(() => {
                const container = document.getElementById('messagesContainer');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        formatTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
    }
}
</script>
@endsection
