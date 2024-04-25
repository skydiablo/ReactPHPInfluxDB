<?php

namespace SkyDiablo\ReactphpInfluxDB\Protocol;

use SkyDiablo\ReactphpInfluxDB\Measurement\Point;
use SkyDiablo\ReactphpInfluxDB\Measurement\TimePrecision;

class PointToLine
{
    public function toLine(Point $point): ?string
    {
        $measurement = $this->escapeKey($point->getMeasurement(), false);
        $lineProtocol = $measurement;

        $tags = $this->appendTags($point);

        if (!$this->isNullOrEmptyString($tags)) {
            $lineProtocol .= $tags;
        } else {
            $lineProtocol .= ' ';
        }

        $fields = $this->appendFields($point);

        if ($this->isNullOrEmptyString($fields)) {
            return null;
        }

        $lineProtocol .= $fields;

        $time = $this->appendTime($point);

        if (!$this->isNullOrEmptyString($time)) {
            $lineProtocol .= $time;
        }

        return $lineProtocol;
    }

    private function appendTags(Point $point): ?string
    {
        $result = '';
        if (!($tags = $point->getTags())) {
            return null;
        }

        ksort($tags);

        foreach (array_keys($tags) as $key) {
            $value = $tags[$key];

            if ($this->isNullOrEmptyString($key) || $this->isNullOrEmptyString($value)) {
                continue;
            }

            $result .= ',' . $this->escapeKey($key) . '=' . $this->escapeKey($value);
        }

        $result .= ' ';
        return $result;
    }

    private function appendFields(Point $point): ?string
    {
        $result = '';

        if (!($fields = $point->getFields())) {
            return null;
        }

        ksort($fields);

        foreach (array_keys($fields) as $key) {
            $value = $fields[$key];

            if (!isset($value)) {
                continue;
            }

            $result .= $this->escapeKey($key) . '=';

            if (is_integer($value) || is_long($value)) {
                $result .= $value . 'i';
            } elseif (is_string($value)) {
                $result .= '"' . $this->escapeValue($value) . '"';
            } elseif (is_bool($value)) {
                $result .= $value ? 'true' : 'false';
            } else {
                $result .= $value;
            }

            $result .= ',';
        }

        return rtrim($result, ',');
    }

    private function appendTime(Point $point): ?string
    {
        if (($time = $point->getTime()) === null) {
            return null;
        }

        if (is_double($time) || is_float($time)) {
            $time = round($time);
        } elseif ($time instanceof \DateTimeInterface) {
            $seconds = $time->getTimestamp();

            $time = match ($point->getPrecision()) {
                TimePrecision::Seconds => strval($seconds),
                TimePrecision::MilliSeconds => round($seconds) . '000',
                TimePrecision::MicroSeconds => round($seconds) . '000000',
                TimePrecision::NanoSeconds => round($seconds) . '000000000',
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