<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property string $id
 * @property string $name
 * @property string $url
 * @property string $time_zone
 * @property string $lang
 * @property string $phone
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Route> $routes
 * @property-read int|null $routes_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereTimeZone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereUrl($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAgency {}
}

namespace App\Models{
/**
 * @property string $service_id
 * @property int $date
 * @property int $exception_type
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarDate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarDate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarDate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarDate whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarDate whereExceptionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarDate whereServiceId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCalendarDate {}
}

namespace App\Models{
/**
 * @property string $id
 * @property int $mode
 * @property bool $is_bidirectional
 * @property string $from_stop_id
 * @property string $to_stop_id
 * @property int $traversal_time
 * @property-read \App\Models\Stop $fromStop
 * @property-read \App\Models\Stop $toStop
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway whereFromStopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway whereIsBidirectional($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway whereMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway whereToStopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway whereTraversalTime($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPathway {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $agency_id
 * @property string|null $short_name
 * @property string|null $long_name
 * @property int $type
 * @property string $desc
 * @property string $color
 * @property string $text_color
 * @property int $sort_order
 * @property-read \App\Models\Agency $agency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Trip> $trips
 * @property-read int|null $trips_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereLongName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereShortName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereTextColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereType($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperRoute {}
}

namespace App\Models{
/**
 * @property string $id
 * @property int $pt_sequence
 * @property float $pt_lat
 * @property float $pt_lon
 * @property float $dist_traveled
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Shape> $shapePoints
 * @property-read int|null $shape_points_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Trip> $trips
 * @property-read int|null $trips_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape whereDistTraveled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape wherePtLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape wherePtLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape wherePtSequence($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperShape {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $name
 * @property float $lat
 * @property float $lon
 * @property string $code
 * @property int $location_type
 * @property string $location_sub_type
 * @property string $parent_station
 * @property int $wheelchair_boarding
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereLocationSubType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereLocationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereParentStation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereWheelchairBoarding($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStop {}
}

namespace App\Models{
/**
 * @property string $trip_id
 * @property string $stop_id
 * @property int $arrival_time
 * @property int $departure_time
 * @property int $stop_sequence
 * @property string|null $stop_headsign
 * @property int $pickup_type
 * @property int $drop_off_type
 * @property float $shape_dist_traveled
 * @property-read \App\Models\Stop $stop
 * @property-read \App\Models\Trip $trip
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime whereArrivalTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime whereDepartureTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime whereDropOffType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime wherePickupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime whereShapeDistTraveled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime whereStopHeadsign($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime whereStopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime whereStopSequence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime whereTripId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStopTime {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $route_id
 * @property string $service_id
 * @property string $trip_headsign
 * @property int $direction_id
 * @property string $block_id
 * @property string $shape_id
 * @property int $wheelchair_accessible
 * @property int $bikes_allowed
 * @property-read \App\Models\Route $route
 * @property-read \App\Models\Shape $shape
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shape> $shapePoints
 * @property-read int|null $shape_points_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StopTime> $stopTimes
 * @property-read int|null $stop_times_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Stop> $stops
 * @property-read int|null $stops_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereBikesAllowed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereBlockId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereDirectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereRouteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereShapeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereTripHeadsign($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereWheelchairAccessible($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperTrip {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $first_name
 * @property string|null $second_name
 * @property string|null $email
 * @property string|null $email_verified_at
 * @property string|null $password
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSecondName($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}

