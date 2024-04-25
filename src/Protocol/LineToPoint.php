<?php

namespace SkyDiablo\ReactphpInfluxDB\Protocol;

use SkyDiablo\ReactphpInfluxDB\Measurement\Point;

class LineToPoint
{

    private const string PART_DELIMITER = ' '; # space

    public function toPoint(string $rawLine): ?Point
    {
        if (str_starts_with($rawLine, '#')) { // skip comment lines
            return null;
        }

        list($rawTags, $rawFields, $timestamp) = explode(self::PART_DELIMITER, $rawLine, 3);

        if (!($rawTags && $rawFields)) {
            return null;
        }

        $tagsTuple = $this->parseCSV($rawTags);
        $point = Point::create(array_shift($tagsTuple));
        return $point
            ->addTags($this->parseTagsTuple($tagsTuple))
            ->addFields($this->parseFields($rawFields))
            ->setTime($timestamp ? (int)$timestamp : null);
    }

    protected function parseTagsTuple(array $tagsTuple): array
    {
        $tags = [];
        foreach ($tagsTuple as $tuple) {
            list($name, $value) = explode('=', $tuple, 2);
            $tags[$name] = $value;
        }
        return $tags;
    }

    protected function parseFields(string $rawFields): array
    {
        $fields = [];
        foreach ($this->parseCSV($rawFields) as $tuple) {
            list($name, $value) = explode('=', $tuple, 2);
            switch (true) {
                case str_ends_with($value, 'i'):
                    $value = (int)$value;
                    break;
                case is_numeric($value):
                    $value = (float)$value;
                    break;
                case (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'")):
                    $value = substr($value, 1, -1);
                    break;
                case in_array(strtolower($value), ['t', 'true']):
                    $value = true;
                    break;
                case in_array(strtolower($value), ['f', 'false']):
                    $value = false;
                    break;
            }
            $fields[$name] = $value;
        }
        return $fields;
    }

    protected function parseCSV(string $input): array
    {
        return str_getcsv($input, ',', '"', "\\");
    }
}