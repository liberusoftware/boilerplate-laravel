# Advanced Search Functionality

This document describes the advanced search functionality implemented for users, posts, and groups.

## Overview

The advanced search system provides powerful filtering capabilities across three main entities:
- **Users**: Search by name, email, verification status, role, and creation date
- **Posts**: Search by title, content, status, author, and publication date
- **Groups**: Search by name, description, type, owner, and creation date

## API Endpoints

All search endpoints are accessible via the `/api/search/` prefix:

### 1. Search Users
**Endpoint**: `GET /api/search/users`

**Query Parameters**:
- `query` (string, optional): Search term for name or email
- `role` (string, optional): Filter by user role
- `verified` (boolean, optional): Filter by email verification status (0 = unverified, 1 = verified)
- `created_from` (date, optional): Filter users created after this date
- `created_to` (date, optional): Filter users created before this date
- `order_by` (string, optional): Sort field (name, email, created_at) - default: created_at
- `order_direction` (string, optional): Sort direction (asc, desc) - default: desc
- `per_page` (integer, optional): Results per page (1-100) - default: 15

**Example Requests**:
```bash
# Search for users with "john" in name or email
GET /api/search/users?query=john

# Search for verified users
GET /api/search/users?verified=1

# Search for users created in the last 30 days, sorted by name
GET /api/search/users?created_from=2026-01-15&order_by=name&order_direction=asc

# Combined filters
GET /api/search/users?query=developer&verified=1&role=admin&per_page=20
```

### 2. Search Posts
**Endpoint**: `GET /api/search/posts`

**Query Parameters**:
- `query` (string, optional): Search term for title or content
- `status` (string, optional): Filter by status (draft, published, archived)
- `author_id` (integer, optional): Filter by author user ID
- `published_from` (date, optional): Filter posts published after this date
- `published_to` (date, optional): Filter posts published before this date
- `include_drafts` (boolean, optional): Include draft posts (default: false - only published posts)
- `order_by` (string, optional): Sort field (title, published_at, created_at) - default: published_at
- `order_direction` (string, optional): Sort direction (asc, desc) - default: desc
- `per_page` (integer, optional): Results per page (1-100) - default: 15

**Example Requests**:
```bash
# Search for posts containing "Laravel"
GET /api/search/posts?query=Laravel

# Search for published posts by specific author
GET /api/search/posts?author_id=123&status=published

# Search for posts published in the last week
GET /api/search/posts?published_from=2026-02-07

# Include draft posts in search
GET /api/search/posts?query=tutorial&include_drafts=1

# Combined filters
GET /api/search/posts?query=API&status=published&author_id=5&order_by=title
```

### 3. Search Groups
**Endpoint**: `GET /api/search/groups`

**Query Parameters**:
- `query` (string, optional): Search term for name or description
- `type` (string, optional): Filter by type (public, private, restricted)
- `owner_id` (integer, optional): Filter by owner user ID
- `created_from` (date, optional): Filter groups created after this date
- `created_to` (date, optional): Filter groups created before this date
- `order_by` (string, optional): Sort field (name, created_at) - default: created_at
- `order_direction` (string, optional): Sort direction (asc, desc) - default: desc
- `per_page` (integer, optional): Results per page (1-100) - default: 15

**Example Requests**:
```bash
# Search for groups containing "developer"
GET /api/search/groups?query=developer

# Search for public groups
GET /api/search/groups?type=public

# Search for groups owned by specific user
GET /api/search/groups?owner_id=456

# Combined filters
GET /api/search/groups?query=Laravel&type=public&order_by=name&order_direction=asc
```

### 4. Search All Entities
**Endpoint**: `GET /api/search/all`

**Query Parameters**:
- `query` (string, optional): Search term applied to all entity types
- `types` (array, optional): Limit search to specific types (users, posts, groups) - default: all types
- `per_page` (integer, optional): Results per page for each type (1-100) - default: 5

**Example Requests**:
```bash
# Search all entities for "Laravel"
GET /api/search/all?query=Laravel

# Search only posts and groups
GET /api/search/all?query=tutorial&types[]=posts&types[]=groups

# Limit results per type
GET /api/search/all?query=developer&per_page=10
```

**Response Format**:
```json
{
  "users": {
    "data": [...],
    "current_page": 1,
    "total": 5
  },
  "posts": {
    "data": [...],
    "current_page": 1,
    "total": 12
  },
  "groups": {
    "data": [...],
    "current_page": 1,
    "total": 3
  }
}
```

## Database Schema

### Posts Table
```
- id (primary key)
- title (string, indexed)
- content (text)
- author_id (foreign key to users, indexed)
- status (enum: draft, published, archived, indexed)
- published_at (datetime, nullable, indexed)
- created_at, updated_at, deleted_at (timestamps)
```

### Groups Table
```
- id (primary key)
- name (string, indexed)
- description (text, nullable)
- owner_id (foreign key to users, indexed)
- type (enum: public, private, restricted, indexed)
- created_at, updated_at, deleted_at (timestamps)
```

## Models

### Post Model
Located at: `app/Models/Post.php`

**Relationships**:
- `author()`: BelongsTo User

**Query Scopes**:
- `published()`: Only published posts
- `status($status)`: Filter by status
- `byAuthor($authorId)`: Filter by author
- `dateRange($from, $to)`: Filter by publication date range
- `search($query)`: Search by title or content

### Group Model
Located at: `app/Models/Group.php`

**Relationships**:
- `owner()`: BelongsTo User

**Query Scopes**:
- `type($type)`: Filter by type
- `byOwner($ownerId)`: Filter by owner
- `search($query)`: Search by name or description

### User Model (Extended)
Located at: `app/Models/User.php`

**New Relationships**:
- `posts()`: HasMany Post
- `groups()`: HasMany Group

**New Query Scopes**:
- `search($query)`: Search by name or email

## Service Layer

### SearchService
Located at: `app/Services/SearchService.php`

**Methods**:
- `searchUsers(array $filters)`: Search users with advanced filters
- `searchPosts(array $filters)`: Search posts with advanced filters
- `searchGroups(array $filters)`: Search groups with advanced filters
- `searchAll(array $filters)`: Search all entities simultaneously

## Testing

Comprehensive test suites are located in `tests/Feature/`:

### SearchUsersTest.php
Tests for user search functionality including:
- Search by name
- Search by email
- Partial matching
- Filtering by verification status
- Date range filtering
- Sorting
- Pagination
- Validation

### SearchPostsTest.php
Tests for post search functionality including:
- Search by title
- Search by content
- Filtering by status
- Filtering by author
- Date range filtering
- Draft exclusion/inclusion
- Sorting
- Pagination
- Validation

### SearchGroupsTest.php
Tests for group search functionality including:
- Search by name
- Search by description
- Filtering by type
- Filtering by owner
- Date range filtering
- Sorting
- Pagination
- Validation

### SearchAllTest.php
Tests for combined search functionality including:
- Search across all entities
- Filtering by specific entity types
- Per-page limits
- Empty results handling

## Running Tests

To run all search tests:
```bash
php artisan test --filter=Search
```

To run specific test suites:
```bash
php artisan test tests/Feature/SearchUsersTest.php
php artisan test tests/Feature/SearchPostsTest.php
php artisan test tests/Feature/SearchGroupsTest.php
php artisan test tests/Feature/SearchAllTest.php
```

## Setup Instructions

1. **Run Migrations**:
   ```bash
   php artisan migrate
   ```

2. **Seed Test Data** (optional):
   ```bash
   php artisan db:seed --class=PostSeeder
   php artisan db:seed --class=GroupSeeder
   ```

3. **Test the API**:
   Use a tool like Postman, curl, or your application's frontend to test the endpoints.

## Performance Considerations

- All searchable fields are indexed for optimal performance
- Pagination is enforced with a maximum of 100 results per page
- The search uses LIKE queries with wildcards, suitable for small to medium datasets
- For larger datasets, consider integrating Laravel Scout with Meilisearch or Algolia

## Future Enhancements

Potential improvements:
- Full-text search integration (Laravel Scout)
- Search highlighting
- Saved searches/filters
- Search analytics
- Elasticsearch integration for advanced search
- Real-time search suggestions
- Advanced boolean search operators
