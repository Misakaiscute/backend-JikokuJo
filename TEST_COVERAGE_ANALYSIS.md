# Test Coverage Analysis Report

**Generated:** May 13, 2026  
**Project:** JikokuJo Backend - PHP/Laravel  
**Analyzed Files:** 7 test files + 9 implementation files

---

## 📋 Executive Summary

The test suite covers basic happy-path scenarios but has significant gaps in:
- **Functional testing** (many tests verify structure, not behavior)
- **Complete endpoint coverage** (multiple endpoints untested)
- **Response payload validation** (tests check status codes, not actual data)
- **Actual helper function usage** (helper tests recreate logic instead of calling functions)
- **Broadcasting/WebSocket functionality** (only structural tests)

---

## 1️⃣ HelperFunctionsTest.php

### ❌ Issues Found

#### **Tests don't call actual helper functions**
- ✗ `test_remove_dead_stops_identifies_unused_stops()` manually recreates the query instead of calling `remove_dead_stops()`
- ✗ `test_sanitize_files_pattern()` is a placeholder testing string operations, not the actual helper function
- ✓ `test_stops_without_associated_routes()` and `test_orphan_stop_times_detection()` test database state but not helpers

#### **Missing helper function tests**
The `app/Helpers/sanitize_files.php` contains:
- `get_storage_path()` - **UNTESTED**
- `switch_commas()` - **UNTESTED**

The `remove_dead_stops.php` function:
- Disables foreign key checks - **NEVER TESTED**
- Removes pathways related to dead stops - **NEVER TESTED**
- Re-enables foreign key checks - **NEVER TESTED**

#### **Missing edge cases**
- [ ] What happens when `remove_dead_stops()` is called with active services still referencing stops?
- [ ] How does `switch_commas()` handle edge cases like empty strings, only quotes, only commas?
- [ ] Does `get_storage_path()` handle null/empty addition parameter correctly?

### ✅ Tests that work
- `test_stop_times_ordered_by_sequence()` - correctly verifies database state
- `test_orphan_stop_times_detection()` - validates relationship integrity

### 📌 Recommendations

1. **Refactor tests to call actual helper functions:**
   ```php
   public function test_remove_dead_stops_deletes_unused_stops()
   {
       // Create test data
       $deadStop = Stop::factory()->create(['id' => 'DEAD1']);
       
       // Call actual function
       remove_dead_stops();
       
       // Assert result
       $this->assertDatabaseMissing('stops', ['id' => 'DEAD1']);
   }
   ```

2. **Add tests for missing helpers:**
   - Test `get_storage_path()` with various inputs
   - Test `switch_commas()` with CSV strings containing quoted values

3. **Test actual behavior, not recreated logic**
   - Verify function output, not reimplemented logic

---

## 2️⃣ ModelsTest.php

### ❌ Issues Found

#### **Tests only verify relationships, not business logic**
- ✓ Routes have many trips (relationship tested)
- ✗ But: no tests for trip filtering, sorting, or scope methods
- ✗ No tests for model accessors/mutators
- ✗ No tests for eager loading optimization

#### **Missing critical model tests**

**Trip Model composite key handling:**
- ✓ `test_trip_composite_key_same_id_different_services()` exists
- ✗ But doesn't test the `setKeysForSaveQuery()` method behavior
- ✗ Doesn't verify update/delete operations with composite keys

**Favourite Model relationships:**
- ✗ **NO TESTS** for Favourite model
- ✗ No tests for pivot data (time field storage)
- ✗ No tests for cascade delete when user is deleted

**Shape Model:**
- ✓ Basic relationship tested
- ✗ No tests for sequence ordering in complex queries
- ✗ No tests for `dist_traveled` sorting

**CalendarDate Model:**
- ✗ Only basic filtering tested
- ✗ No tests for date validation
- ✗ No tests for service_id uniqueness per date

**User Model:**
- ✗ No tests for deviceTokens relationship
- ✗ No tests for password hashing/verification
- ✗ No tests for email validation

#### **Missing eager loading tests**
- [ ] Does loading trips with stopTimes avoid N+1 queries?
- [ ] Are relationships optimized with `with()`?

### ✅ Tests that work
- `test_route_has_many_trips()`
- `test_trip_belongs_to_route()`
- `test_stop_times_ordered_by_sequence()` - correctly verifies Eloquent scopes

### 📌 Recommendations

1. **Add missing model tests:**
   ```php
   public function test_favourite_stores_route_and_time()
   {
       $user = User::factory()->create();
       $route = Route::factory()->create();
       
       $user->favourites()->attach($route->id, ['time' => '0800']);
       
       $this->assertDatabaseHas('favourites', [
           'user_id' => $user->id,
           'route_id' => $route->id,
           'time' => '0800',
       ]);
   }
   ```

2. **Test composite key behavior:**
   ```php
   public function test_trip_update_with_composite_key()
   {
       $trip = Trip::factory()->create(['id' => 'T1', 'service_id' => 'S1']);
       
       $trip->update(['trip_headsign' => 'Updated']);
       
       $this->assertDatabaseHas('trips', [
           'id' => 'T1',
           'service_id' => 'S1',
           'trip_headsign' => 'Updated',
       ]);
   }
   ```

3. **Test model scopes and accessors**
4. **Verify query optimization**

---

## 3️⃣ UserControllerTest.php

### ❌ Issues Found

#### **Login endpoint incomplete testing**
- ✗ `test_login_returns_token_for_valid_credentials()` doesn't verify token content
- ✗ No assertion that returned token is a valid Sanctum token
- ✗ Doesn't test token structure or claims

#### **Missing endpoint tests**
- **`POST /api/user/logout`** - **NOT TESTED**
  - Should verify token is deleted
  - Should verify token is invalidated immediately
  
- **`POST /api/user/device-token`** - **NOT TESTED**
  - Should test token storage
  - Should test platform parameter handling
  - Should test updateOrCreate behavior

- **`GET /api/user/favourites`** - **NOT TESTED**
  - Should verify route data is returned correctly
  - Should verify time data is preserved
  - Should handle empty favourites

#### **Incomplete test coverage**
- ✗ `test_update_updates_authenticated_user()` doesn't test password update scenario
- ✗ `test_destroy_deletes_authenticated_user()` doesn't verify related records are handled

#### **Missing error scenarios**
- [ ] Updating user with duplicate email (should fail)
- [ ] Updating user with invalid email format
- [ ] Password update validation
- [ ] Permission checks (user can't update other users)

### ✅ Tests that work
- `test_store_creates_user_and_returns_user()` - good coverage
- `test_get_returns_authenticated_user()` - correct
- `test_destroy_deletes_authenticated_user()` - functional

### 📌 Recommendations

1. **Add logout endpoint test:**
   ```php
   public function test_logout_invalidates_token()
   {
       $user = User::factory()->create();
       Sanctum::actingAs($user);
       
       $response = $this->postJson('/api/user/logout');
       
       $response->assertStatus(200);
       
       // Token should be invalid after logout
       $response = $this->getJson('/api/user');
       $response->assertStatus(401);
   }
   ```

2. **Add device token tests:**
   ```php
   public function test_save_device_token_stores_token()
   {
       $user = User::factory()->create();
       Sanctum::actingAs($user);
       
       $response = $this->postJson('/api/user/device-token', [
           'token' => 'device_token_xyz',
           'platform' => 'ios',
       ]);
       
       $this->assertDatabaseHas('device_tokens', [
           'user_id' => $user->id,
           'token' => 'device_token_xyz',
           'platform' => 'ios',
       ]);
   }
   ```

3. **Add favourites endpoint test:**
   ```php
   public function test_get_user_favourites_returns_all()
   {
       $user = User::factory()->create();
       $route1 = Route::factory()->create();
       $route2 = Route::factory()->create();
       
       $user->favourites()->attach($route1->id, ['time' => '0800']);
       $user->favourites()->attach($route2->id, ['time' => '1700']);
       
       Sanctum::actingAs($user);
       
       $response = $this->getJson('/api/user/favourites');
       
       $response->assertStatus(200);
       $this->assertCount(2, $response->json('data.favourites'));
   }
   ```

---

## 4️⃣ UserAuthenticationEdgeCasesTest.php

### ❌ Issues Found

#### **Incomplete tests**
- ✗ `test_login_fails_with_wrong_password()` is cut off in file (incomplete)
- ✗ `test_invalid_token_is_rejected()` is incomplete

#### **Missing login endpoint variants**
- [ ] Test login with different remember_user paths
  - Current: `POST /api/user/login` (default endpoint)
  - Code: `POST /api/user/login/{rememberUser}` (route accepts parameter)
  - ✗ Never tested with route parameter

#### **Missing token validation scenarios**
- [ ] Token expiration (1 day vs 14 days)
  - Tests check token is returned but not expiration time
  - ✗ Doesn't verify `expiresAt` is set correctly

- [ ] Multiple concurrent tokens
  - [ ] Can user have multiple active tokens?
  - [ ] Does logout clear only current token or all?

#### **Missing edge cases**
- [ ] SQL injection in email field
- [ ] Very long input strings
- [ ] Special characters in names
- [ ] Case sensitivity in email
- [ ] Whitespace handling

### ✅ Tests that work
- `test_register_rejects_duplicate_email()`
- `test_register_rejects_weak_password()`
- `test_register_rejects_mismatched_passwords()`
- `test_register_requires_all_fields()`
- Edge case tests for status codes

### 📌 Recommendations

1. **Complete incomplete tests**
2. **Add token expiration tests:**
   ```php
   public function test_login_token_expires_in_one_day_by_default()
   {
       $user = User::factory()->create([
           'password' => Hash::make('correctpassword'),
       ]);
       
       $response = $this->postJson('/api/user/login', [
           'email' => $user->email,
           'password' => 'correctpassword',
           'remember_user' => false,
       ]);
       
       $token = $response->json('data.token');
       
       // Verify token expires in ~1 day
       $personalToken = PersonalAccessToken::findToken($token);
       $expiryDiff = $personalToken->expires_at->diffInHours(now());
       
       $this->assertTrue($expiryDiff >= 23 && $expiryDiff <= 25);
   }
   ```

3. **Test concurrent tokens:**
   ```php
   public function test_user_can_have_multiple_active_tokens()
   {
       $user = User::factory()->create([
           'password' => Hash::make('password'),
       ]);
       
       // Create first token
       $response1 = $this->postJson('/api/user/login', [
           'email' => $user->email,
           'password' => 'password',
       ]);
       $token1 = $response1->json('data.token');
       
       // Create second token
       $response2 = $this->postJson('/api/user/login', [
           'email' => $user->email,
           'password' => 'password',
       ]);
       $token2 = $response2->json('data.token');
       
       // Both tokens should work
       $this->withHeader('Authorization', "Bearer $token1")
           ->getJson('/api/user')
           ->assertStatus(200);
       
       $this->withHeader('Authorization', "Bearer $token2")
           ->getJson('/api/user')
           ->assertStatus(200);
   }
   ```

4. **Test route parameter format:**
   ```php
   public function test_login_endpoint_with_path_parameter()
   {
       $user = User::factory()->create([
           'password' => Hash::make('password'),
       ]);
       
       // Test both paths work
       $response1 = $this->postJson('/api/user/login/false', [
           'email' => $user->email,
           'password' => 'password',
       ]);
       
       $response2 = $this->postJson('/api/user/login/true', [
           'email' => $user->email,
           'password' => 'password',
       ]);
       
       $this->assertEquals(200, $response1->getStatusCode());
       $this->assertEquals(200, $response2->getStatusCode());
   }
   ```

---

## 5️⃣ BroadcastingAndChannelsTest.php

### ❌ Issues Found

#### **Tests verify structure, not functionality**
- ✓ `test_trip_position_channel_structure()` - creates channel name string (not meaningful)
- ✓ `test_vehicle_position_event_structure()` - verifies array has keys (structure only)
- ✗ Doesn't test actual broadcasting
- ✗ Doesn't test actual channel subscription

#### **Missing actual broadcasting tests**
The `VehiclePositionUpdated` event is implemented but:
- ✗ Never tested to actually broadcast
- ✗ No tests for event being dispatched
- ✗ No tests for Redis operations
- ✗ No WebSocket connection tests

The `TripPositionChannel` class:
- ✗ `join()` method never called in tests
- ✗ Redis operations never verified
  - Sets expiry on `channel_activity:{channelName}`
  - Adds trip ID to `active_channels` set
  - **NEVER TESTED**

#### **Missing authorization tests**
- ✗ No test for authenticated vs unauthenticated users joining channel
- ✗ No test for channel privacy rules
- ✗ No test for guest access with uniqid

#### **Missing edge cases**
- [ ] What happens with null tripId?
- [ ] What happens with very long trip ID?
- [ ] Concurrent connections to same channel
- [ ] Channel cleanup after disconnection

### ✅ Tests that exist
- `test_broadcast_includes_trip_headsign()` - at least tests data structure

### 📌 Recommendations

1. **Add real event broadcasting test:**
   ```php
   public function test_vehicle_position_event_broadcasts()
   {
       $this->expectsEvents(VehiclePositionUpdated::class);
       
       event(new VehiclePositionUpdated(
           tripId: 'T1',
           lat: 47.5,
           lon: 19.0,
           speed: 45,
           bearing: 180,
       ));
       
       Event::assertDispatched(VehiclePositionUpdated::class);
   }
   ```

2. **Add channel authorization test:**
   ```php
   public function test_user_can_join_trip_presence_channel()
   {
       $user = User::factory()->create();
       $trip = Trip::factory()->create(['id' => 'T_AUTH']);
       
       $channel = new TripPositionChannel();
       $result = $channel->join($user, $trip->id);
       
       $this->assertEquals($user->id, $result['id']);
       $this->assertEquals($user->email, $result['name']);
   }
   
   public function test_guest_can_join_trip_channel()
   {
       $trip = Trip::factory()->create(['id' => 'T_GUEST']);
       
       $channel = new TripPositionChannel();
       $result = $channel->join(null, $trip->id);
       
       $this->assertTrue(str_starts_with($result['id'], 'guest_'));
   }
   ```

3. **Add Redis operation verification:**
   ```php
   public function test_join_stores_channel_activity_in_redis()
   {
       $channel = new TripPositionChannel();
       $channel->join(null, 'T_REDIS');
       
       // Verify Redis key was set
       $this->assertTrue(Redis::exists('channel_activity:presence-trip.T_REDIS'));
       
       // Verify TTL (90 seconds)
       $ttl = Redis::ttl('channel_activity:presence-trip.T_REDIS');
       $this->assertGreaterThan(85, $ttl);
       $this->assertLessThanOrEqual(90, $ttl);
   }
   ```

4. **Test event data structure:**
   ```php
   public function test_event_broadcasts_correct_data_structure()
   {
       $event = new VehiclePositionUpdated(
           tripId: 'T1',
           lat: 47.5,
           lon: 19.0,
           speed: 45.5,
           bearing: 180.0,
           message: 'On time',
       );
       
       $broadcastData = $event->broadcastWith();
       
       $this->assertEquals('T1', $broadcastData['trip_id']);
       $this->assertEquals(47.5, $broadcastData['lat']);
       $this->assertEquals(19.0, $broadcastData['lon']);
       $this->assertEquals(45.5, $broadcastData['speed']);
       $this->assertEquals(180.0, $broadcastData['bearing']);
       $this->assertEquals('On time', $broadcastData['message']);
   }
   ```

---

## 6️⃣ TripControllerTest.php

### ❌ Issues Found

#### **Very limited test coverage**
- Only 2 tests for a controller with 2 main endpoints
- ✗ No tests for actual response payload structure
- ✗ No tests for data ordering/sorting
- ✗ No tests for the complex stop filtering logic

#### **test_get_trips_by_route_id_returns_matching_trip()**
- ✗ Creates data but doesn't make API request
- ✗ Just verifies factory works (`$this->assertTrue(true)`)
- ✗ Doesn't test actual endpoint response

#### **test_get_trips_by_stop_id_returns_trips_for_stop_ids()**
- ✓ Makes API request
- ✗ Only checks status code (200 or 206)
- ✗ Doesn't verify:
  - Response contains correct trips
  - Response structure
  - Stop times are ordered
  - Trip headsign is included
  - Stops are correctly filtered

#### **Missing tests for complex business logic**
The controller implements:
- **Time window filtering** (±120 minutes from query time) - **NOT TESTED**
- **Service date filtering** (calendar_dates) - **NOT TESTED**
- **Stop sequence ordering** - **NOT TESTED**
- **Composite key handling for trips** - **NOT TESTED**
- **Response payload construction** - **NOT TESTED**

### ✅ Tests that exist
- `test_get_trips_by_stop_id_rejects_missing_ids()` - good validation test

### 📌 Recommendations

1. **Add comprehensive route trip test:**
   ```php
   public function test_get_trips_by_route_id_returns_full_payload()
   {
       $route = Route::factory()->create(['id' => 'R_FULL']);
       $trip = Trip::factory()->create([
           'id' => 'T_FULL',
           'route_id' => $route->id,
           'trip_headsign' => 'Main Terminal',
       ]);
       
       CalendarDate::factory()->create([
           'service_id' => $trip->service_id,
           'date' => '20260513',
           'exception_type' => 1,
       ]);
       
       $stop1 = Stop::factory()->create(['id' => 'STOP1', 'lat' => 47.5, 'lon' => 19.0]);
       $stop2 = Stop::factory()->create(['id' => 'STOP2', 'lat' => 47.51, 'lon' => 19.01]);
       
       StopTime::factory()->create([
           'trip_id' => $trip->id,
           'stop_id' => $stop1->id,
           'stop_sequence' => 1,
           'arrival_time' => 480,
           'departure_time' => 485,
       ]);
       
       StopTime::factory()->create([
           'trip_id' => $trip->id,
           'stop_id' => $stop2->id,
           'stop_sequence' => 2,
           'arrival_time' => 540,
           'departure_time' => 545,
       ]);
       
       $response = $this->postJson('/api/route/trip', [
           'date' => '20260513',
           'time' => '0800',
           'route_id' => $route->id,
       ]);
       
       $response->assertStatus(200);
       $response->assertJsonStructure([
           'data' => [
               'trips' => [
                   [
                       'id',
                       'route_id',
                       'headsign',
                       'stops' => [
                           [
                               'id',
                               'name',
                               'stop_sequence',
                               'arrival_time',
                               'location' => ['lat', 'lon'],
                           ]
                       ]
                   ]
               ]
           ],
           'errors'
       ]);
       
       $trips = $response->json('data.trips');
       $this->assertCount(1, $trips);
       $this->assertEquals('T_FULL', $trips[0]['id']);
       $this->assertEquals('Main Terminal', $trips[0]['headsign']);
   }
   ```

2. **Test time window filtering:**
   ```php
   public function test_time_window_filters_trips_correctly()
   {
       // Create trips departing at different times
       // Request at 8:00 AM - should include ±120 min window
   }
   ```

3. **Test stop payload construction:**
   ```php
   public function test_response_includes_first_and_last_stops_only()
   {
       // Create trip with 5 stops
       // Verify response only includes first and last
   }
   ```

---

## 7️⃣ TripEdgeCasesTest.php

### ❌ Issues Found

#### **Weak assertion patterns**
Most tests use vague assertions:
```php
$this->assertTrue(
    in_array($response->getStatusCode(), [200, 206])
);
```
- ✗ Doesn't verify actual functionality
- ✗ Too permissive (206 means no content found)
- ✗ Doesn't validate response data

#### **Incomplete test implementations**

**test_cannot_board_at_last_stop():**
- ✗ Tests setup but doesn't verify the logic
- ✗ Just checks response code
- ✗ Doesn't assert that last stop trips are excluded

**test_shape_points_ordered_by_sequence():**
- ✗ Creates shape points with dist_traveled
- ✗ Calls endpoint
- ✗ But doesn't verify ordering in response

#### **Missing validation tests**
- ✓ `test_invalid_date_format_returns_error()` - good
- ✓ `test_invalid_time_format_returns_error()` - good
- ✗ But no tests for missing route_id
- ✗ No tests for invalid route_id format

### ✅ Tests that work
- `test_invalid_date_format_returns_error()`
- `test_invalid_time_format_returns_error()`
- `test_time_format_edge_cases()` - decent edge cases

### 📌 Recommendations

1. **Strengthen vague assertions:**
   ```php
   public function test_cannot_board_at_last_stop()
   {
       // ... setup ...
       
       $response = $this->postJson('/api/stop/trip', [
           'ids' => 'STOP_L2', // Last stop
           'date' => '20260426',
       ]);
       
       $this->assertEquals(200, $response->getStatusCode());
       
       // Actually verify the endpoint filters out this trip
       $trips = $response->json('data.trips');
       $tripIds = collect($trips)->pluck('id')->toArray();
       
       // Trip should not be returned because query stop is the last stop
       $this->assertNotContains('T_LAST', $tripIds);
   }
   ```

2. **Verify shape ordering:**
   ```php
   public function test_shape_points_ordered_by_distance_traveled()
   {
       // ... setup ...
       
       $response = $this->postJson('/api/trip/shapes', [
           'trip_id' => $trip->id,
       ]);
       
       $response->assertStatus(200);
       
       $points = $response->json('data.points');
       
       // Verify ordering by distance_traveled
       $distances = collect($points)->pluck('distance_traveled')->toArray();
       $expectedOrder = [0, 150, 300];
       
       $this->assertEquals($expectedOrder, $distances);
   }
   ```

3. **Add missing validation tests:**
   ```php
   public function test_missing_route_id_returns_error()
   {
       $response = $this->postJson('/api/route/trip', [
           'date' => '20260426',
           'time' => '0800',
           // Missing route_id
       ]);
       
       $this->assertEquals(400, $response->getStatusCode());
   }
   ```

---

## 8️⃣ Untested Endpoints & Controllers

### ✗ ShapeController - Not Tested
**Endpoint:** `POST /api/trip/shapes`  
**Method:** `getShapesByTripId()`

Missing tests:
- [ ] Valid trip with shapes returns correct structure
- [ ] Trip without shape_id returns 404
- [ ] Non-existent trip returns 404
- [ ] Shape points are ordered by distance_traveled
- [ ] Location structure contains lat/lon as floats

### ✗ StopController - Not Tested
**Endpoint:** `POST /api/trip/stops`  
**Method:** `getStopsByTripId()`

Missing tests:
- [ ] Valid trip returns all stops
- [ ] Stops ordered by stop_sequence
- [ ] Stop location is included with lat/lon
- [ ] Non-existent trip returns 404 with correct error message
- [ ] Stop names default to "Ismeretlen megálló" when null

### ✗ UserController - Partial Coverage
**Missing endpoint tests:**

1. **`POST /api/user/logout`**
   - [ ] Token is invalidated after logout
   - [ ] Subsequent requests with same token fail
   - [ ] All tokens for user can be cleared

2. **`POST /api/user/device-token`**
   - [ ] Device token is stored
   - [ ] Platform defaults to 'android' when not provided
   - [ ] Duplicate token updates existing record

3. **`GET /api/user/favourites`**
   - [ ] Returns all user favourites
   - [ ] Includes route data (id, short_name, type, color)
   - [ ] Includes time data from pivot table
   - [ ] Handles empty favourites list

### ✗ SearchController - Partial Coverage
**Endpoint:** `GET /api/queryables`

Existing tests:
- ✓ Returns 404 when no data
- ✓ Groups stops by name
- ✓ Correct route type mapping
- ✓ Both stops and routes returned

Missing tests:
- [ ] Large dataset handling (performance)
- [ ] Duplicate stop names with different IDs
- [ ] All route type categories (1-8)
- [ ] Special characters in names
- [ ] Unicode character handling

---

## 9️⃣ Database Schema & Foreign Keys - Not Tested

The following relationships are not verified:
- [ ] Cascade delete when user is deleted (favourites cascade)
- [ ] Foreign key integrity for stop_times
- [ ] Trip composite key constraints (id + service_id)
- [ ] Shape composite key constraints (id + pt_sequence)

---

## 🔟 Integration Issues

### Favourite System
- UserControllerTest covers toggle/retrieve
- SearchAndRoutesTest covers some scenarios
- ✗ But: missing relationship tear-down test
- ✗ Missing: edge case with multiple times for same route

### Trip/Stop/Shape Pipeline
- TripController tests trip retrieval
- ✗ But: ShapeController endpoint never called from tests
- ✗ But: StopController endpoint never called from tests
- ✗ No end-to-end tests for full data retrieval pipeline

### Device Tokens
- Implemented in UserController
- ✗ Completely untested
- ✗ No integration with push notification system

---

## 📊 Summary Statistics

| File | Tests | Passing | Issues | Coverage |
|------|-------|---------|--------|----------|
| HelperFunctionsTest.php | 5 | ~3 | 2 high | 40% |
| ModelsTest.php | 9 | ~7 | 2 medium | 45% |
| UserControllerTest.php | 6 | 6 | 3 high | 50% |
| UserAuthenticationEdgeCasesTest.php | 11 | ~9 | 3 medium | 55% |
| BroadcastingAndChannelsTest.php | 6 | ~1 | 5 critical | 20% |
| TripControllerTest.php | 2 | 2 | 4 critical | 15% |
| TripEdgeCasesTest.php | 7 | ~4 | 3 medium | 35% |
| SearchControllerTest.php | 6 | 6 | 0 low | 80% |
| SearchAndRoutesTest.php | 4 | 4 | 1 medium | 70% |
| **TOTAL** | **56** | **~42** | **23 issues** | **~46%** |

**Untested Endpoints:** 3 (ShapeController, StopController.getStopsByTripId, partial UserController)

---

## 🎯 Priority Fixes

### 🔴 CRITICAL (Do First)
1. **BroadcastingAndChannelsTest.php** - Tests are structural only
   - Effort: **High**
   - Impact: **Critical** (broadcasting is core feature)

2. **TripControllerTest.php** - Missing payload validation
   - Effort: **Medium**
   - Impact: **Critical** (main API endpoint)

3. **ShapeController/StopController** - Completely untested
   - Effort: **Medium**
   - Impact: **High** (commonly used endpoints)

### 🟠 HIGH (Do Second)
4. **UserControllerTest.php** - Missing 3 endpoints
   - Effort: **Medium**
   - Impact: **High** (user-facing features)

5. **HelperFunctionsTest.php** - Not calling actual functions
   - Effort: **Low**
   - Impact: **High** (utility functions)

6. **ModelsTest.php** - Missing business logic tests
   - Effort: **Medium**
   - Impact: **Medium** (data integrity)

### 🟡 MEDIUM (Do Third)
7. **TripEdgeCasesTest.php** - Weak assertions
8. **UserAuthenticationEdgeCasesTest.php** - Incomplete implementations

---

## ✅ Tests That Can Be Removed

- ✓ `test_get_trips_by_route_id_returns_matching_trip()` - Just tests factory
  - Replace with: Actual API endpoint test with payload validation

- ✓ `test_sanitize_files_pattern()` - Doesn't test actual helper
  - Replace with: Real helper function tests

- ✓ Several placeholder tests in BroadcastingAndChannelsTest.php
  - Replace with: Actual event/channel tests

---

## 📝 Recommendations Summary

1. **Refactor tests to test actual behavior, not structure**
2. **Complete untested endpoint coverage**
3. **Fix incomplete test implementations**
4. **Add response payload validation**
5. **Add Redis/Broadcasting integration tests**
6. **Verify cascade deletes and FK constraints**
7. **Add performance/load testing considerations**
8. **Document test expectations clearly**

---

**Report Generated:** May 13, 2026  
**Total Issues Found:** 23 major + numerous minor  
**Estimated Effort to Fix:** 40-60 hours  
**Recommended Start Point:** BroadcastingAndChannelsTest + TripControllerTest
