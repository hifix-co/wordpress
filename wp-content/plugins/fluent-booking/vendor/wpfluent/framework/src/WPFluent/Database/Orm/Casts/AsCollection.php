<?php

namespace FluentBooking\Framework\Database\Orm\Casts;

use InvalidArgumentException;
use FluentBooking\Framework\Support\Collection;
use FluentBooking\Framework\Database\Orm\Castable;
use FluentBooking\Framework\Database\Orm\CastsAttributes;

class AsCollection implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \FluentBooking\Framework\Contracts\Database\Orm\CastsAttributes<\FluentBooking\Framework\Support\Collection<array-key, mixed>, iterable>
     */
    public static function castUsing(array $arguments)
    {
        return new class($arguments) implements CastsAttributes
        {
            protected $arguments = [];

            public function __construct(array $arguments)
            {
                $this->arguments = $arguments;
            }

            public function get($model, $key, $value, $attributes)
            {
                if (!isset($attributes[$key])) {
                    return null;
                }

                $data = Json::decode($attributes[$key]);

                $collectionClass = $this->arguments[0] ?? Collection::class;

                if (!is_a($collectionClass, Collection::class, true)) {
                    throw new InvalidArgumentException(
                        'The provided class must extend [' . Collection::class . '].'
                    );
                }

                return is_array($data) ? new $collectionClass($data) : null;
            }

            public function set($model, $key, $value, $attributes)
            {
                return [$key => Json::encode($value)];
            }
        };
    }

    /**
     * Specify the collection for the cast.
     *
     * @param  class-string  $class
     * @return string
     */
    public static function using($class)
    {
        return static::class.':'.$class;
    }
}
