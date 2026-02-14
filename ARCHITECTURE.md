# Private Messaging System Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          PRIVATE MESSAGING SYSTEM                            │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                              FRONTEND LAYER                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────────┐  │
│  │  messages/layout     │  │  messages/index      │  │  messages/show   │  │
│  │  - Base Layout       │  │  - Conversation List │  │  - Chat View     │  │
│  │  - Navigation        │  │  - New Message Modal │  │  - Send Message  │  │
│  │  - Alpine.js Setup   │  │  - Unread Counts     │  │  - Real-time UI  │  │
│  └──────────────────────┘  └──────────────────────┘  └──────────────────┘  │
│                                                                              │
│  Technologies: Blade Templates, Alpine.js, Tailwind CSS                     │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       │ HTTP/AJAX Requests
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                              ROUTING LAYER                                   │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  Web Routes (routes/web.php)         API Routes (routes/api.php)            │
│  ┌────────────────────────┐         ┌────────────────────────────┐         │
│  │ GET  /messages         │         │ GET    /api/messages        │         │
│  │ GET  /messages/{user}  │         │ GET    /api/messages/{user} │         │
│  └────────────────────────┘         │ POST   /api/messages        │         │
│                                     │ PATCH  /api/messages/{id}/read        │
│  Middleware:                        │ DELETE /api/messages/{id}   │         │
│  - auth                             │ GET    /api/messages/users  │         │
│  - verified                         │ GET    /api/messages/unread-count     │
│                                     └────────────────────────────┘         │
│                                     Middleware: auth:sanctum                │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                            CONTROLLER LAYER                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │               MessageController                                       │   │
│  ├─────────────────────────────────────────────────────────────────────┤   │
│  │ index()         - List all conversations                             │   │
│  │ show($user)     - Get conversation with user                         │   │
│  │ store()         - Send new message (with encryption)                 │   │
│  │ markAsRead()    - Mark message as read                               │   │
│  │ destroy()       - Delete message                                     │   │
│  │ users()         - Get list of users                                  │   │
│  │ unreadCount()   - Get unread count                                   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│  Features: Validation, Authorization, Encryption/Decryption                 │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                          AUTHORIZATION LAYER                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                    MessagePolicy                                      │   │
│  ├─────────────────────────────────────────────────────────────────────┤   │
│  │ viewAny()   - Always allow (authenticated users)                     │   │
│  │ view()      - Only sender or recipient                               │   │
│  │ create()    - All authenticated users                                │   │
│  │ update()    - Only sender                                            │   │
│  │ delete()    - Sender or recipient                                    │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                              MODEL LAYER                                     │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────────────┐                    ┌──────────────────────┐       │
│  │     Message          │                    │       User           │       │
│  ├──────────────────────┤                    ├──────────────────────┤       │
│  │ Attributes:          │                    │ Relationships:       │       │
│  │ - id                 │◄───────────────────┤ - sentMessages()     │       │
│  │ - sender_id         │                    │ - receivedMessages() │       │
│  │ - recipient_id      │◄───────────────────┤                      │       │
│  │ - body (encrypted)  │                    │                      │       │
│  │ - read_at           │                    │                      │       │
│  │ - timestamps        │                    │                      │       │
│  ├──────────────────────┤                    └──────────────────────┘       │
│  │ Relationships:       │                                                   │
│  │ - sender()          │                                                   │
│  │ - recipient()       │                                                   │
│  ├──────────────────────┤                                                   │
│  │ Methods:             │                                                   │
│  │ - markAsRead()      │                                                   │
│  │ - isRead()          │                                                   │
│  ├──────────────────────┤                                                   │
│  │ Scopes:              │                                                   │
│  │ - between()         │                                                   │
│  │ - unread()          │                                                   │
│  └──────────────────────┘                                                   │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                            DATABASE LAYER                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                         messages table                                │   │
│  ├─────────────────────────────────────────────────────────────────────┤   │
│  │ id (bigint, primary key)                                             │   │
│  │ sender_id (bigint, FK -> users.id, cascade delete)                  │   │
│  │ recipient_id (bigint, FK -> users.id, cascade delete)               │   │
│  │ body (text, encrypted)                                               │   │
│  │ read_at (timestamp, nullable)                                        │   │
│  │ created_at (timestamp)                                               │   │
│  │ updated_at (timestamp)                                               │   │
│  ├─────────────────────────────────────────────────────────────────────┤   │
│  │ Indexes:                                                             │   │
│  │ - (sender_id, recipient_id)                                          │   │
│  │ - recipient_id                                                       │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                            SECURITY FEATURES                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │  Encryption  │  │Authorization │  │ Validation   │  │     CSRF     │   │
│  │              │  │              │  │              │  │              │   │
│  │ Laravel Crypt│  │ Policies     │  │ Form Rules   │  │ Token Check  │   │
│  │ AES-256-CBC  │  │ Gate Checks  │  │ Type Checks  │  │ Middleware   │   │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘   │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                              TESTING LAYER                                   │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────────────┐                    ┌──────────────────────┐       │
│  │   MessageTest.php    │                    │ MessageApiTest.php   │       │
│  ├──────────────────────┤                    ├──────────────────────┤       │
│  │ - Model creation     │                    │ - API endpoints      │       │
│  │ - Encryption/decrypt │                    │ - Authentication     │       │
│  │ - Relationships      │                    │ - Authorization      │       │
│  │ - Scopes            │                    │ - Validation         │       │
│  │ - Read receipts     │                    │ - CRUD operations    │       │
│  └──────────────────────┘                    └──────────────────────┘       │
│                                                                              │
│  Total: 16 test cases covering all functionality                            │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                            DATA FLOW EXAMPLE                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  1. User submits message via form (/messages/{user})                        │
│                    ↓                                                         │
│  2. Alpine.js sends POST /api/messages with CSRF token                      │
│                    ↓                                                         │
│  3. Laravel validates authentication (Sanctum)                              │
│                    ↓                                                         │
│  4. MessageController validates input                                       │
│                    ↓                                                         │
│  5. MessagePolicy checks authorization                                      │
│                    ↓                                                         │
│  6. Message body encrypted with Crypt::encryptString()                      │
│                    ↓                                                         │
│  7. Message saved to database                                               │
│                    ↓                                                         │
│  8. Response sent with decrypted message for display                        │
│                    ↓                                                         │
│  9. Alpine.js updates UI with new message                                   │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

## File Structure

```
liberusoftware/boilerplate-laravel
├── app/
│   ├── Http/Controllers/
│   │   └── MessageController.php          (Main API controller)
│   ├── Models/
│   │   ├── Message.php                    (Message model)
│   │   └── User.php                       (Extended with relationships)
│   └── Policies/
│       └── MessagePolicy.php              (Authorization logic)
├── database/
│   ├── factories/
│   │   └── MessageFactory.php             (Test data factory)
│   └── migrations/
│       └── 2026_02_14_122604_create_messages_table.php
├── resources/views/messages/
│   ├── layout.blade.php                   (Base layout)
│   ├── index.blade.php                    (Conversation list)
│   └── show.blade.php                     (Chat interface)
├── routes/
│   ├── api.php                            (API routes)
│   └── web.php                            (Web routes)
├── tests/Feature/
│   ├── MessageTest.php                    (Model tests)
│   └── MessageApiTest.php                 (API tests)
└── Documentation/
    ├── MESSAGING.md                       (API reference)
    ├── SETUP_MESSAGING.md                 (Setup guide)
    ├── IMPLEMENTATION_SUMMARY.md          (Implementation details)
    └── README.md                          (Main readme - updated)
```
