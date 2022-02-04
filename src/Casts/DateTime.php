<?php

namespace Loxi5\Framework\Casts;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

class DateTime implements CastsAttributes
{
    /**
     * Determine if the given value is a standard date format.
     *
     * @param  string  $value
     * @return bool
     */
    protected function isStandardDateFormat($value)
    {
        return preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value);
    }

    public function get($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) {
            return null;
        }

        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
        if ($value instanceof CarbonInterface) {
            return Date::instance($value->setTimezone((auth()->check() && auth()->user()->timezone) ? auth()->user()->timezone : config('app.timezone')));
        }

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof DateTimeInterface) {
            return Date::parse(
                $value->format('Y-m-d H:i:s.u'), $value->setTimezone((auth()->check() && auth()->user()->timezone) ? auth()->user()->timezone : config('app.timezone'))
            );
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        if (is_numeric($value)) {
            return Date::createFromTimestamp($value)->setTimezone((auth()->check() && auth()->user()->timezone) ? auth()->user()->timezone : config('app.timezone'));
        }

        // If the value is in simply year, month, day format, we will instantiate the
        // Carbon instances from that format. Again, this provides for simple date
        // fields on the database, while still supporting Carbonized conversion.
        if ($this->isStandardDateFormat($value)) {
            return Date::instance(Carbon::createFromFormat('Y-m-d', $value)->startOfDay())->setTimezone((auth()->check() && auth()->user()->timezone) ? auth()->user()->timezone : config('app.timezone'));
        }

        $format = 'Y-m-d H:i:s';

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        try {
            $date = Date::createFromFormat($format, $value)->setTimezone((auth()->check() && auth()->user()->timezone) ? auth()->user()->timezone : config('app.timezone'));
        } catch (\Exception $e) {
            $date = false;
        }

        return $date ?: Date::parse($value)->setTimezone((auth()->check() && auth()->user()->timezone) ? auth()->user()->timezone : config('app.timezone'));
    }

    public function set($model, string $key, $value, array $attributes)
    {
        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
        if ($value instanceof CarbonInterface) {
            return $value->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s.u');
        }

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof DateTimeInterface) {
            return $value->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s.u');
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        if (is_numeric($value)) {
            return Date::createFromTimestamp($value)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s.u');
        }

        return $value;
    }
}
