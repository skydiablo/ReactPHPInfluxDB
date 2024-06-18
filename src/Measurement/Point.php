<?php
declare(strict_types=1);

namespace SkyDiablo\ReactphpInfluxDB\Measurement;

/**
 * Class Point
 * Heavily inspired by https://github.com/influxdata/influxdb-client-php/blob/master/src/InfluxDB2/Point.php
 * @package SkyDiablo\ReactphpInfluxDB\Measurement
 * @author Volker von Hoeßlin [skydiablo@gmx.net]
 */
class Point
{
    private string $measurement;
    private array $tags;
    private array $fields;
    private int|float|null|\DateTimeInterface $time;
    private TimePrecision $precision;

    /** Create DataPoint instance for specified measurement name.
     *
     * @param string $measurement
     * @param array $tags
     * @param array $fields
     * @param int|null $time
     * @param TimePrecision $precision
     */
    public function __construct(
        string                            $measurement,
        array                             $tags = [],
        array                             $fields = [],
        \DateTimeInterface|float|int|null $time = null,
        TimePrecision                     $precision = TimePrecision::NanoSeconds
    )
    {
        $this->setMeasurement($measurement);
        $this->tags = $tags;
        $this->fields = $fields;
        $this->precision = $precision;
        $this->setTime($time);
    }

    public static function measurement(string $measurement): static
    {
        return new static($measurement);
    }

    /**
     * @param string $name
     * @return Point
     */
    public function setMeasurement(string $name): Point
    {
        $this->measurement = $name;
        return $this;
    }

    public function getMeasurement(): string
    {
        return $this->measurement;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    /**
     * @return TimePrecision
     */
    public function getPrecision(): TimePrecision
    {
        return $this->precision;
    }

    public static function create(string $measurement): static
    {
        return new Point($measurement);
    }

    /** Adds or replaces a tag value for a point.
     *
     * @param string $key
     * @param string $value
     * @return Point
     */
    public function setTag(string $key, string $value): static
    {
        $this->tags[$key] = $value;
        return $this;
    }

    public function setTags(array $tags): static
    {
        $this->tags = $tags + $this->tags;
        return $this;
    }

    /** Adds or replaces a field value for a point.
     *
     * @param string $key
     * @param int|float|bool|string $value
     * @return Point
     */
    public function setField(string $key, int|float|bool|string $value): static
    {
        $this->fields[$key] = $value;
        return $this;
    }

    public function setFields(array $fields): static
    {
        $this->fields = $fields + $this->fields;
        return $this;
    }

    public function unsetTag(string ...$tags): static
    {
        array_map(function (string $tag) {
            unset($this->tags[$tag]);
        }, $tags);
        return $this;
    }

    /** Updates the timestamp for the point.
     *
     * @param int|\DateTimeInterface|float|null $time
     * @return Point
     * @todo: refactor to store time in a more precision format
     */
    public function setTime(null|int|\DateTimeInterface|float $time): static
    {
        switch (true) {
            case is_null($time):
            case $time instanceof \DateTimeInterface:
                $this->time = $time;
                break;
            case is_int($time):
            case is_float($time):
                $time = match ($this->getPrecision()) {
                    TimePrecision::Seconds => $time,
                    TimePrecision::MilliSeconds => $time / 1000,
                    TimePrecision::MicroSeconds => $time / 1000000,
                    TimePrecision::NanoSeconds => $time / 1000000000,
                };
                $this->time = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'))->setTimestamp($time);
                break;
        }
        return $this;
    }

    /**
     * @param TimePrecision $precision
     * @return $this
     */
    public function setPrecision(TimePrecision $precision): static
    {
        $this->precision = $precision;
        return $this;
    }

    /** If there is no field then return null.
     *
     * @return string|null representation of the point
     */

}