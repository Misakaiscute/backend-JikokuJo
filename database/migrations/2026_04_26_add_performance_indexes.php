<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * These indexes are created based on analysis of query patterns used throughout the application:
     * - Service filtering (calendar_dates)
     * - Trip schedule lookups (stop_times with time ranges)
     * - Route and stop searches
     * - User favorites retrieval
     */
    public function up(): void
    {
        // Calendar dates: Most frequent filter in transit searches
        // Queries: WHERE date = ? AND exception_type = 1
        Schema::table('calendar_dates', function (Blueprint $table) {
            $table->index(['date', 'exception_type']);
        });

        // Stop times: Critical for schedule lookups
        // Queries: WHERE departure_time BETWEEN ? AND ? with trip_id filtering
        Schema::table('stop_times', function (Blueprint $table) {
            $table->index('departure_time');
            $table->index(['trip_id', 'stop_sequence']);
            $table->index(['stop_id', 'departure_time']);
        });

        // Trips: Route and service filtering
        // Queries: WHERE route_id = ? AND service_id IN (...)
        Schema::table('trips', function (Blueprint $table) {
            $table->index('route_id');
            $table->index('service_id');
            $table->index(['route_id', 'service_id']);
        });

        // Stops: Name-based search and coordinate-based proximity searches
        // Queries: WHERE name LIKE ? OR WHERE id IN (...)
        Schema::table('stops', function (Blueprint $table) {
            $table->index('name');
            $table->index(['lat', 'lon']);
        });

        // Routes: Agency grouping and type filtering for search results
        // Queries: WHERE agency_id = ? and grouping by type
        Schema::table('routes', function (Blueprint $table) {
            $table->index('agency_id');
            $table->index('type');
        });

        // Shapes: Polyline coordinate retrieval
        // Queries: WHERE id = ? ORDER BY pt_sequence
        Schema::table('shapes', function (Blueprint $table) {
            $table->index('id');
        });

        // Favourites: User favorite routes retrieval
        // Queries: WHERE user_id = ? and toggle operations
        Schema::table('favourites', function (Blueprint $table) {
            $table->index('user_id');
            $table->index(['user_id', 'route_id']);
        });

        // Users: Email lookup for authentication
        // Queries: WHERE email = ?
        Schema::table('users', function (Blueprint $table) {
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_dates', function (Blueprint $table) {
            $table->dropIndex(['date', 'exception_type']);
        });

        Schema::table('stop_times', function (Blueprint $table) {
            $table->dropIndex(['departure_time']);
            $table->dropIndex(['trip_id', 'stop_sequence']);
            $table->dropIndex(['stop_id', 'departure_time']);
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->dropIndex(['route_id']);
            $table->dropIndex(['service_id']);
            $table->dropIndex(['route_id', 'service_id']);
        });

        Schema::table('stops', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['lat', 'lon']);
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->dropIndex(['agency_id']);
            $table->dropIndex(['type']);
        });

        Schema::table('shapes', function (Blueprint $table) {
            $table->dropIndex(['id']);
        });

        Schema::table('favourites', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['user_id', 'route_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
        });
    }
};
