<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function shouldRun(): bool
    {
        // Csak teszt környezetben fusson le
        return app()->environment('testing') || config('app.env') === 'local_testing';
    }
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('second_name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('routes', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('agency_id')->nullable();
            $table->string('short_name')->nullable();
            $table->string('long_name')->nullable();
            $table->integer('type')->default(0);
            $table->string('desc')->nullable();
            $table->string('color')->nullable();
            $table->string('text_color')->nullable();
            $table->integer('sort_order')->default(0);
        });

        Schema::create('trips', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('route_id');
            $table->string('service_id');
            $table->string('trip_headsign')->nullable();
            $table->integer('direction_id')->default(0);
            $table->string('block_id')->nullable();
            $table->string('shape_id')->nullable();
            $table->integer('wheelchair_accessible')->default(0);
            $table->integer('bikes_allowed')->default(0);
        });

        Schema::create('stops', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name')->nullable();
            $table->double('lat')->nullable();
            $table->double('lon')->nullable();
            $table->string('code')->nullable();
            $table->integer('location_type')->default(0);
            $table->string('location_sub_type')->nullable();
            $table->string('parent_station')->nullable();
            $table->integer('wheelchair_boarding')->default(0);
        });

        Schema::create('stop_times', function (Blueprint $table) {
            $table->string('trip_id');
            $table->string('stop_id');
            $table->integer('stop_sequence');
            $table->integer('arrival_time')->nullable();
            $table->integer('departure_time')->nullable();
            $table->string('stop_headsign')->nullable();
            $table->integer('pickup_type')->default(0);
            $table->integer('drop_off_type')->default(0);
            $table->double('shape_dist_traveled')->nullable();
            $table->primary(['trip_id', 'stop_id', 'stop_sequence']);
        });

        Schema::create('shapes', function (Blueprint $table) {
            $table->string('id');
            $table->integer('pt_sequence');
            $table->double('pt_lat')->nullable();
            $table->double('pt_lon')->nullable();
            $table->double('dist_traveled')->nullable();
            $table->primary(['id', 'pt_sequence']);
        });

        Schema::create('calendar_dates', function (Blueprint $table) {
            $table->string('service_id');
            $table->integer('date');
            $table->integer('exception_type');
            $table->primary(['service_id', 'date']);
        });

        Schema::create('favourites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('route_id');
            $table->string(('time'));
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favourites');
        Schema::dropIfExists('calendar_dates');
        Schema::dropIfExists('shapes');
        Schema::dropIfExists('stop_times');
        Schema::dropIfExists('stops');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('routes');
        Schema::dropIfExists('users');
    }
};
