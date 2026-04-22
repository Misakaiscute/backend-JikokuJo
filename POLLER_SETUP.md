# BKK Vehicle Position Poller Setup Guide

## Overview
The poller system has been refactored from job-based polling to a dedicated command-based poller that efficiently manages multiple active trip channels using Redis.

## How It Works

### 1. Channel Activation (When user joins)
```
User joins trip channel
    ↓
TripPositionChannel::join() called
    ↓
Redis::sadd('active_channels', $tripId)  // Add trip to active set
    ↓
Cache set: channel_activity:presence-trip.{$tripId}
```

### 2. Polling Loop (Continuous in PollerRun command)
```
PollerRun command reads Redis 'active_channels' set every 5 seconds
    ↓
Fetches VehiclePositions.pb from BKK API
    ↓
Broadcasts position data to all active trip channels
```

### 3. Channel Deactivation (When all watchers leave)
```
User(s) leave trip channel
    ↓
Reverb detects 0 subscriptions on channel
    ↓
PollerRun cleanup check (every 15 seconds) calls Reverb API
    ↓
Reverb returns 0 watchers
    ↓
Redis::srem('active_channels', $tripId)
    ↓
Polling for that trip stops in next cycle (within 5 seconds)
```

**Timing:** Automatic cleanup happens every 15 seconds. So worst-case, a trip will keep polling for 15-20 seconds after all users leave before being removed from Redis.

## Environment Configuration

Add these to your `.env` file:

```env
# Redis Configuration
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0

# BKK API
BKK_API_KEY=your_api_key_here

# Reverb (WebSocket)
REVERB_HOST=reverb
REVERB_PORT=8080
REVERB_SCHEME=http
```

## Docker Compose Setup

The `docker-compose.yaml` already includes:

```yaml
poller:
  image: local/jikokujo-poller
  container_name: poller
  restart: unless-stopped
  command: php /var/www/artisan poller:run
  environment:
    # All required env vars
  depends_on:
    - redis
    - app
  networks:
    - jikokujo

redis:
  image: redis:alpine
  container_name: redis
  restart: unless-stopped
  networks:
    - jikokujo
```

## Redis Commands Reference

### View active channels
```bash
docker-compose exec redis redis-cli SMEMBERS active_channels
```

### Clear all active channels (force stop polling)
```bash
docker-compose exec redis redis-cli DEL active_channels
```

### Check cache keys
```bash
docker-compose exec redis redis-cli KEYS "channel_activity:*"
```

## Running the System

### Start the entire stack
```bash
docker-compose up -d
```

### View poller logs
```bash
docker-compose logs -f poller
```

### Monitor Redis activity
```bash
docker-compose exec redis redis-cli MONITOR
```

## Testing

### 1. Start the poller
```bash
docker-compose up -d poller redis app reverb
```

### 2. Manually add a trip to Redis
```bash
docker-compose exec redis redis-cli SADD active_channels "trip_12345"
```

### 3. Check poller logs
```bash
docker-compose logs -f poller
```

Should see: `Polling 1 active trip(s)`

### 4. Stop monitoring and remove the trip
```bash
docker-compose exec redis redis-cli SREM active_channels "trip_12345"
```

## Key Files

| File | Purpose |
|------|---------|
| `app/Console/Commands/PollerRun.php` | Main polling loop command |
| `app/Services/VehiclePositionPoller.php` | Individual trip polling service |
| `app/Broadcasting/TripPositionChannel.php` | Channel authorization & Redis management |
| `app/Events/VehiclePositionUpdated.php` | Broadcast event |
| `routes/channels.php` | Channel definitions |

## Performance Considerations

1. **Poll Interval**: Currently 5 seconds (configurable in PollerRun)
2. **BKK API Calls**: One call per 5-second cycle for all active trips
3. **Cleanup Check**: Runs every 15 seconds (every 3 cycles) to avoid excessive Reverb API calls
4. **Redis Overhead**: Minimal (simple SMEMBERS, SADD, SREM operations)
5. **Memory**: Scales with number of active trips, not total trips in database

### Automatic Cleanup Timing

- **Every 15 seconds**, PollerRun checks each active trip via Reverb's HTTP API
- If a trip has **0 subscribers**, it's removed from Redis
- Polling stops within 5 seconds of removal
- **Maximum polling time after leaving**: ~20 seconds (worst case)

To modify cleanup frequency, change the condition in PollerRun:
```php
if ($this->cleanupCounter >= 3) {  // 3 cycles × 5 seconds = 15 seconds
    $this->cleanupInactiveChannels($trip_ids);
    $this->cleanupCounter = 0;
}
```

## Troubleshooting

### Poller not starting
- Check `BKK_API_KEY` is set correctly
- Verify Redis container is healthy: `docker-compose ps`
- Check logs: `docker-compose logs poller`

### Data not broadcasting
- Verify channel is in Redis: `redis-cli SMEMBERS active_channels`
- Check Reverb logs: `docker-compose logs reverb`
- Verify WebSocket connection on client side

### High CPU usage
- Check how many active channels are in Redis
- Verify BKK API responses aren't huge
- Reduce poll interval if needed

### Redis persistence
- Currently using ephemeral container storage
- To persist across restarts, mount a volume in docker-compose.yaml:
  ```yaml
  redis:
    volumes:
      - redis-volume:/data
  volumes:
    redis-volume:
  ```
