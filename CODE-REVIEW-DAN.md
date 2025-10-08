# Problems

Issues identified with codebase.

## Critical Issues
1. SQL Injection Vulnerability (CRITICAL)
`app/Http/Controllers/ReportController.php:21`

2. Missing Input Validation
`app/Http/Controllers/ReportController.php:14-19`

3. Missing Authentication/Authorization on API
`routes/api.php:11`

4. No Rate Limiting


## Performance Issues

1. N+1 Query Problems
- Line 25: Task::all() - loads ALL tasks 
- Line 33: User::find() in loop 
- Line 36: User::find() in loop 
- Line 41: TaskComment::where()->get() in loop 
- Line 52: Task::all() AGAIN in loop (already have it!)
- Lines 61, 72, 86, 95, 104, 122: Multiple additional Task::all() calls

2. Inefficient Data Processing
- Nested loops over entire dataset for each task 
- Recalculating same statistics repeatedly 
- Loading all tasks multiple times

3. Missing Database Indexes
`database/migrations/2025_09_22_120326_create_tasks_table.php`

4. Artificial Delays
- Sleep and usleep calls?

5. Memory Issues
- $tasks = Task::all();      // 10,000 records
- $users = User::all();      // 50 records

We can pluck these instead?

## Architecture Issues

1. Missing Service layer
2. Missing API Resources
3. No Repository pattern 
4. No DTOs
5. No Caching (despite importing Cache facade)

## Code Quality Issues
1. Missing and inconsistent use of type hints
2. Magic numbers and strings
3. No Model Scopes
4. Variable naming could be improved
5. Inconsistent array structures

## Database Design Issues

1. JSON columns without structure
2. Soft deletes not implemented (degrade audit trail)
3. Category as string (typo issues)
4. 


## Testing Issues
1. Only boilerplate example tests exist, nothing solid, business logic, database etc


## Best Practices
1. No API Versioning
2. No try-catch
3. Not much logging
4. No Request/Response Documentation
5. No Caching Strategy
6. No Pagination
7. No CORS Configuration

## Configuration Issues
1. TaskFactory.php: runs query during factory definition, assumes users exist.
2. No Environment Configuration (.env)


## Some good stuff

- Proper use of Eloquent relationships in models 
- Migration files are well-structured with proper foreign keys and cascades 
- Factory definitions are comprehensive with realistic fake data 
- PHPUnit configuration is correct 
- Proper namespace organization 
- Follows PSR standards for class naming and structure


Checked 8th October 2025 - Daniel Harding
