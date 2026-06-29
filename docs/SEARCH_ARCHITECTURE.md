# Advanced Search Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                         Client / Frontend                        │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 │ HTTP Requests
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                         API Routes                               │
│  /api/search/users   /api/search/posts                          │
│  /api/search/groups  /api/search/all                            │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 │ Route to Controller
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                      SearchController                            │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ users()    - Validate filters, call service              │  │
│  │ posts()    - Validate filters, call service              │  │
│  │ groups()   - Validate filters, call service              │  │
│  │ all()      - Validate filters, call service              │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 │ Validated Filters
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                       SearchService                              │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ searchUsers($filters)   - Build query with filters       │  │
│  │ searchPosts($filters)   - Build query with filters       │  │
│  │ searchGroups($filters)  - Build query with filters       │  │
│  │ searchAll($filters)     - Combine all searches           │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
                    ▼            ▼            ▼
         ┌─────────────┐  ┌─────────┐  ┌──────────┐
         │ User Model  │  │  Post   │  │  Group   │
         │             │  │  Model  │  │  Model   │
         │ • search()  │  │ • search│  │ • search │
         │ • role()    │  │ • status│  │ • type() │
         └─────────────┘  │ • byAuth│  │ • byOwne │
                          └─────────┘  └──────────┘
                                 │
                                 │ Query Database
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                          Database                                │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │  users       │  │   posts      │  │   groups     │          │
│  │  • name ⚡   │  │   • title ⚡  │  │   • name ⚡   │          │
│  │  • email     │  │   • content  │  │   • desc     │          │
│  │  • verified  │  │   • status⚡ │  │   • type ⚡   │          │
│  │  • created_at│  │   • author⚡ │  │   • owner ⚡  │          │
│  └──────────────┘  │   • publish⚡│  └──────────────┘          │
│                    └──────────────┘                              │
│  ⚡ = Indexed for performance                                    │
└─────────────────────────────────────────────────────────────────┘
```

## Search Flow

### 1. User Search Flow
```
Request: GET /api/search/users?query=john&verified=1
   │
   ├─> Validate: query, verified, created_from, created_to, etc.
   │
   ├─> SearchService.searchUsers()
   │   ├─> User::query()
   │   ├─> ->search('john')           // WHERE name LIKE '%john%' OR email LIKE '%john%'
   │   ├─> ->whereNotNull('email_verified_at')
   │   └─> ->paginate(15)
   │
   └─> Response: Paginated user results with metadata
```

### 2. Post Search Flow
```
Request: GET /api/search/posts?query=laravel&status=published&author_id=5
   │
   ├─> Validate: query, status, author_id, dates, etc.
   │
   ├─> SearchService.searchPosts()
   │   ├─> Post::query()->with('author')
   │   ├─> ->search('laravel')        // WHERE title LIKE '%laravel%' OR content LIKE '%laravel%'
   │   ├─> ->status('published')      // WHERE status = 'published'
   │   ├─> ->byAuthor(5)              // WHERE author_id = 5
   │   ├─> ->published()              // Only published posts (status + date check)
   │   └─> ->paginate(15)
   │
   └─> Response: Paginated posts with author relationship
```

### 3. Group Search Flow
```
Request: GET /api/search/groups?query=developer&type=public
   │
   ├─> Validate: query, type, owner_id, dates, etc.
   │
   ├─> SearchService.searchGroups()
   │   ├─> Group::query()->with('owner')
   │   ├─> ->search('developer')      // WHERE name LIKE '%developer%' OR description LIKE '%developer%'
   │   ├─> ->type('public')           // WHERE type = 'public'
   │   └─> ->paginate(15)
   │
   └─> Response: Paginated groups with owner relationship
```

### 4. Combined Search Flow
```
Request: GET /api/search/all?query=laravel&types[]=posts&types[]=groups
   │
   ├─> Validate: query, types array, per_page
   │
   ├─> SearchService.searchAll()
   │   ├─> searchPosts(['query' => 'laravel', 'per_page' => 5])
   │   ├─> searchGroups(['query' => 'laravel', 'per_page' => 5])
   │   └─> Combine results into single response
   │
   └─> Response: Object with separate paginated results for each type
       {
         "posts": { "data": [...], "total": 10 },
         "groups": { "data": [...], "total": 3 }
       }
```

## Filter Types Supported

### User Filters
- `query`: Name or email search
- `role`: Filter by role name
- `verified`: Email verification status (boolean)
- `created_from/to`: Date range
- `order_by`: name, email, created_at
- `order_direction`: asc, desc
- `per_page`: 1-100

### Post Filters
- `query`: Title or content search
- `status`: draft, published, archived
- `author_id`: Author user ID
- `published_from/to`: Publication date range
- `include_drafts`: Include draft posts (boolean)
- `order_by`: title, published_at, created_at
- `order_direction`: asc, desc
- `per_page`: 1-100

### Group Filters
- `query`: Name or description search
- `type`: public, private, restricted
- `owner_id`: Owner user ID
- `created_from/to`: Date range
- `order_by`: name, created_at
- `order_direction`: asc, desc
- `per_page`: 1-100

## Performance Optimizations

1. **Database Indexes**
   - All searchable fields indexed
   - Foreign keys indexed
   - Date fields indexed

2. **Query Optimizations**
   - Eager loading relationships (with())
   - Pagination to limit result sets
   - Scoped queries for reusability

3. **Best Practices**
   - Service layer for business logic
   - Request validation to prevent bad queries
   - Eloquent ORM for SQL injection protection
   - Soft deletes to preserve data integrity
