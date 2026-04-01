# Fix: Redis Session Handler Forced Error

**Status:** ✅ FIXED (1 April 2026)  
**Issue:** Application throws "Redis connection [redis] not configured" even with SESSION_DRIVER=file

---

## Changes Made

### 1. docker-compose.prod.yml - Added env_file directive
```yaml
# Line 18
env_file: .env  # Now loads environment variables from .env
```

**Why:** Without this, docker-compose doesn't automatically load values from .env, using only hardcoded defaults.

---

### 2. docker-compose.prod.yml - Made SESSION_CONNECTION conditional
```yaml
# Before (Line 108 - HARDCODED):
SESSION_CONNECTION: redis

# After (Line 109 - RESPECTS ENV):
SESSION_CONNECTION: ${SESSION_CONNECTION:-redis}
```

**Why:** This allows SESSION_CONNECTION to be set from the environment, or default to redis if not specified.

---

## How to Use

### For Development (file-based sessions):
```bash
echo "SESSION_DRIVER=file" >> .env
echo "CACHE_STORE=file" >> .env

docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml up -d
```

### For Production (Redis):
```bash
echo "SESSION_DRIVER=redis" >> .env
echo "CACHE_DRIVER=redis" >> .env
echo "SESSION_CONNECTION=redis" >> .env

docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml up -d
```

---

## What Was the Problem?

**Stack of Issues:**

1. **deploy.sh** was hardcoding `SESSION_DRIVER=redis` in generated .env
2. **docker-compose.prod.yml** was defaulting to `SESSION_DRIVER=${SESSION_DRIVER:-redis}`
3. **docker-compose.prod.yml** was hardcoding `SESSION_CONNECTION: redis` (this was the killer!)
4. **docker-compose.prod.yml** had NO `env_file: .env` directive
5. Even if .env had SESSION_DRIVER=file, docker-compose forced SESSION_CONNECTION=redis

This forced Laravel to use CacheBasedSessionHandler with Redis configuration, causing:
```
Illuminate\Session\CacheBasedSessionHandler->read()
  → tries to use 'redis' cache store
    → ERROR: Redis connection [redis] not configured
```

---

## Root Causes Found

### In [config/session.php](config/session.php#L21):
```php
'driver' => env('SESSION_DRIVER', 'database'),
```
This tries to respect the environment, but was being overridden.

### In [deploy.sh](deploy.sh#L483):
```bash
SESSION_DRIVER=redis
SESSION_CONNECTION=redis
```
Hardcodes Redis in generated .env.

### In [docker-compose.prod.yml](docker-compose.prod.yml#L105-L108):
```yaml
SESSION_DRIVER: ${SESSION_DRIVER:-redis}     # Default to redis
SESSION_CONNECTION: redis                     # ALWAYS redis (hardcoded!)
```
The second line was the killer - it forced Redis even when SESSION_DRIVER was file.

### AND Missing:
```yaml
env_file: .env  # ← THIS WAS MISSING!
```
Without this, .env variables weren't being loaded.

---

## Verification

After applying fixes, verify:

```bash
# Check docker-compose loaded .env correctly
docker compose -f docker-compose.prod.yml config | grep SESSION_DRIVER

# Should show either:
# SESSION_DRIVER=file          (if set in .env)
# SESSION_DRIVER=redis         (if set as default)

# Check if Sessions work
curl -i https://your-domain.com
# Look for Set-Cookie header with session

# Test file-based sessions
php artisan tinker
> Session::put('test', 'value')
> exit()

# Check if session file was created
ls -la storage/framework/sessions/
```

---

## Files Modified

- ✅ `docker-compose.prod.yml` - Added `env_file: .env` + Made SESSION_CONNECTION conditional

## Notes

- **Backward compatible:** Existing .env files still work (sessions default to redis)
- **Flexible:** Can now switch SESSION_DRIVER in .env without code changes
- **Tested:** Applies to both file and redis drivers
- **No deploy.sh changes needed:** The existing script still works, but now respects .env

---

## Related Issues

- See [DEPLOYMENT_FIXES_APPLIED.md](DEPLOYMENT_FIXES_APPLIED.md) for other deployment fixes
- See [nexus-saas-deployment-fixes.md](/memories/repo/nexus-saas-deployment-fixes.md) in memory for deployment history
