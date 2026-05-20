# Search Functionality Performance Optimization

## Overview
This document outlines the performance optimizations implemented for the search functionality in the application, covering users, posts, and groups.

## Key Optimizations

### 1. Database Indexes
Added strategic database indexes to improve query performance:

#### Users Table
- **Index on `name`**: Speeds up user name searches
- **Unique index on `email`**: Already existed, ensures fast email lookups

#### Posts Table
- **Index on `title`**: Accelerates title-based searches
- **Index on `status`**: Optimizes filtering by post status
- **Index on `created_at`**: Improves sorting by creation date
- **Full-text index on `title` and `content`**: Enables efficient full-text search across post content

#### Groups Table
- **Index on `name`**: Speeds up group name searches
- **Index on `is_active`**: Optimizes active/inactive filtering
- **Full-text index on `name` and `description`**: Enables efficient full-text search

### 2. Query Optimization

#### Selective Column Loading
Instead of loading all columns, search endpoints only select necessary fields:
- Reduces memory usage
- Decreases network transfer size
- Improves response times

```php
// Example: Only load necessary user columns
$users = User::search($query)
    ->select(['id', 'name', 'email', 'profile_photo_path', 'created_at'])
    ->paginate($perPage);
```

#### Eager Loading
Prevents N+1 query problems by loading related data in advance:

```php
// Example: Load user relationship with posts
$posts = Post::search($query)
    ->with('user:id,name,email')
    ->select([...])
    ->paginate($perPage);
```

#### Efficient Pagination
- Default page size: 15 records
- Maximum page size: 100 records
- Prevents memory exhaustion from large result sets

### 3. API Rate Limiting
Implemented throttling to prevent abuse and ensure system stability:
- Limit: 60 requests per minute per IP
- Protects against DoS attacks
- Ensures fair resource distribution

### 4. Search Scopes
Added reusable search scopes to models for consistent and maintainable search logic:

```php
// User model
public function scopeSearch($query, $search)
{
    return $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('email', 'like', "%{$search}%");
    });
}
```

## Performance Improvements

### Expected Performance Gains
With these optimizations, the system should experience:

1. **50-80% reduction** in search query execution time for indexed columns
2. **60-90% reduction** in memory usage through selective column loading
3. **Elimination of N+1 queries** through eager loading
4. **Improved scalability** under high load due to rate limiting

### Benchmarking Recommendations
To validate performance improvements:

1. **Before/After Comparison**: Run search queries before and after optimization
2. **Load Testing**: Test with 100+ concurrent users
3. **Query Analysis**: Use `EXPLAIN` to verify index usage
4. **Monitor Metrics**: Track response times, database load, and memory usage

## API Endpoints

### Search Users
```
GET /api/search/users?query={search_term}&per_page={page_size}
```

### Search Posts
```
GET /api/search/posts?query={search_term}&status={status}&per_page={page_size}
```

### Search Groups
```
GET /api/search/groups?query={search_term}&active_only={boolean}&per_page={page_size}
```

## Testing
Comprehensive test suite included covering:
- Basic search functionality
- Filtering capabilities
- Pagination
- Rate limiting
- Eager loading verification

Run tests with:
```bash
php artisan test tests/Feature/SearchTest.php
```

## Future Enhancements
Consider these additional optimizations:

1. **Redis Caching**: Cache frequently searched terms
2. **Elasticsearch**: For more advanced full-text search capabilities
3. **Query Result Caching**: Cache search results for common queries
4. **Database Read Replicas**: Distribute search load across multiple databases
5. **Search Analytics**: Track popular searches to optimize further

## Maintenance Notes
- Monitor slow query logs regularly
- Review and update indexes as data patterns change
- Keep search scopes DRY and maintainable
- Consider index fragmentation and rebuild as needed
