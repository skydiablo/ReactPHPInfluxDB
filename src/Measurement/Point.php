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
    private string $name;
    private array $tags;
    private array $fields;
    private int|float|null|\DateTimeInterface $time;
    private WritePrecision $precision;

    /** Create DataPoint instance for specified measurement name.
     *
     * @param string $name
     * @param array $tags
     * @param array $fields
     * @param int|null $time
     * @param WritePrecision $precision
     */
    public function __construct(
        string         $name,
        array          $tags = [],
        array          $fields = [],
        ?int           $time = null,
        WritePrecision $precision = WritePrecision::S
    )
    {
        $this->name = $name;
        $this->tags = $tags;
        $this->fields = $fields;
        $this->time = $time;
        $this->precision = $precision;
    }

    /**
     * @return WritePrecision
     */
    public function getPrecision(): WritePrecision
    {
        return $this->precision;
    }

    public static function measurement(string $name): static
    {
        return new Point($name);
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

    /** Adds or replaces a field value for a point.
     *
     * @param string $key
     * @param string $value
     * @return Point
     */
    public function addField(string $key, int|float|bool|string $value): static
    {
        $this->fields[$key] = $value;
        return $this;
    }

    /** Updates the timestamp for the point.
     *
     * @param int|\DateTimeInterface|float|null $time
     * @return Point
     */
    public function time(null|int|\DateTimeInterface|float $time): static
    {
        $this->time = $time;
        return $this;
    }

    /**
     * @param WritePrecision $precision
     * @return $this
     */
    public function precision(WritePrecision $precision): static
    {
        $this->precision = $precision;
        return $this;
    }

    /** If there is no field then return null.
     *
     * @return string|null representation of the point
     */
    public function toLineProtocol(): ?string
    {
        $measurement = $this->escapeKey($this->name, false);
        $lineProtocol = $measurement;

        $tags = $this->appendTags();

        if (!$this->isNullOrEmptyString($tags)) {
            $lineProtocol .= $tags;
        } else {
            $lineProtocol .= ' ';
        }

        $fields = $this->appendFields();

        if ($this->isNullOrEmptyString($fields)) {
            return null;
        }

        $lineProtocol .= $fields;

        $time = $this->appendTime();

        if (!$this->isNullOrEmptyString($time)) {
            $lineProtocol .= $time;
        }

        return $lineProtocol;
    }

    private function appendTags(): ?string
    {
        $tags = '';

        if ($this->tags == null) {
            return null;
        }

        ksort($this->tags);

        foreach (array_keys($this->tags) as $key) {
            $value = $this->tags[$key];

            if ($this->isNullOrEmptyString($key) || $this->isNullOrEmptyString($value)) {
                continue;
            }

            $tags .= ',' . $this->escapeKey($key) . '=' . $this->escapeKey($value);
        }

        $tags .= ' ';
        return $tags;
    }

    private function appendFields(): ?string
    {
        $fields = '';

        if ($this->fields == null) {
            return null;
        }

        ksort($this->fields);

        foreach (array_keys($this->fields) as $key) {
            $value = $this->fields[$key];

            if (!isset($value)) {
                continue;
            }

            $fields .= $this->escapeKey($key) . '=';

            if (is_integer($value) || is_long($value)) {
                $fields .= $value . 'i';
            } elseif (is_string($value)) {
                $fields .= '"' . $this->escapeValue($value) . '"';
            } elseif (is_bool($value)) {
                $fields .= $value ? 'true' : 'false';
            } else {
                $fields .= $value;
            }

            $fields .= ',';
        }

        return rtrim($fields, ',');
    }

    private function appendTime(): ?string
    {
        if (!isset($this->time)) {
            return null;
        }

        $time = $this->time;

        if (is_double($time) || is_float($time)) {
            $time = round($time);
        } elseif ($time instanceof \DateTimeInterface) {
            $seconds = $time->getTimestamp();

            $time = match ($this->precision) {
                WritePrecision::MS => strval(round($seconds) . '000'),
                WritePrecision::S => strval($seconds),
                WritePrecision::US => strval(round($seconds) . '000000'),
                WritePrecision::NS => strval(round($seconds) . '000000000'),
            };
        }

        return ' ' . $time;
    }

    private function escapeKey($key, $escapeEqual = true): string
    {
        $escapeKeys = array(' ' => '\\ ', ',' => '\\,', "\\" => '\\\\',
            "\n" => '\\n', "\r" => '\\r', "\t" => '\\t');

        if ($escapeEqual) {
            $escapeKeys['='] = '\\=';
        }

        return \strtr($key, $escapeKeys);
    }

    private function escapeValue($value): string
    {
        $escapeValues = array('"' => '\\"', "\\" => '\\\\');
        return \strtr($value, $escapeValues);
    }

    private function isNullOrEmptyString($str): bool
    {
        return (!isset($str) || trim($str) === '');
    }
}