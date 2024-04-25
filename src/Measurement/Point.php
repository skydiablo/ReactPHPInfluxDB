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
        string         $measurement,
        array          $tags = [],
        array          $fields = [],
        ?int           $time = null,
        TimePrecision $precision = TimePrecision::Seconds
    )
    {
        $this->measurement($measurement);
        $this->tags = $tags;
        $this->fields = $fields;
        $this->time = $time;
        $this->precision = $precision;
    }

    /**
     * @param string $name
     * @return Point
     */
    public function measurement(string $name): Point
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

    public function getTime(): float|\DateTimeInterface|int|null
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
    public function addTag(string $key, string $value): static
    {
        $this->tags[$key] = $value;
        return $this;
    }

    public function addTags(array $tags): static
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
    public function addField(string $key, int|float|bool|string $value): static
    {
        $this->fields[$key] = $value;
        return $this;
    }

    public function addFields(array $fields): static
    {
        $this->fields = $fields + $this->fields;
        return $this;
    }

    /** Updates the timestamp for the point.
     *
     * @param int|\DateTimeInterface|float|null $time
     * @return Point
     */
    public function setTime(null|int|\DateTimeInterface|float $time): static
    {
        $this->time = $time;
        return $this;
    }

    /**
     * @param TimePrecision $precision
     * @return $this
     */
    public function precision(TimePrecision $precision): static
    {
        $this->precision = $precision;
        return $this;
    }

    /** If there is no field then return null.
     *
     * @return string|null representation of the point
     */

}