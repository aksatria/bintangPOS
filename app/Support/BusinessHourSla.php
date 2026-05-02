<?php

namespace App\Support;

use Carbon\CarbonInterface;

class BusinessHourSla
{
    /**
     * Hitung menit kerja antara 2 waktu berdasarkan aturan jam kerja.
     *
     * @param  array{start:string,end:string,workdays:array<int,int>}  $rules
     */
    public static function diffInBusinessMinutes(?CarbonInterface $startAt, ?CarbonInterface $endAt, array $rules): int
    {
        if (! $startAt || ! $endAt) {
            return 0;
        }
        if ($endAt->lessThanOrEqualTo($startAt)) {
            return 0;
        }

        [$startHour, $startMinute] = self::parseHm((string) ($rules['start'] ?? '08:00'));
        [$endHour, $endMinute] = self::parseHm((string) ($rules['end'] ?? '22:00'));
        $workdays = collect((array) ($rules['workdays'] ?? [1, 2, 3, 4, 5, 6, 7]))
            ->map(fn ($d) => (int) $d)
            ->filter(fn ($d) => $d >= 1 && $d <= 7)
            ->values()
            ->all();
        if (count($workdays) === 0) {
            $workdays = [1, 2, 3, 4, 5, 6, 7];
        }

        $cursor = $startAt->copy();
        $end = $endAt->copy();
        $minutes = 0;

        while ($cursor->lt($end)) {
            $dayStart = $cursor->copy()->setTime($startHour, $startMinute, 0);
            $dayEnd = $cursor->copy()->setTime($endHour, $endMinute, 0);
            $isWorkday = in_array((int) $cursor->dayOfWeekIso, $workdays, true);

            if (! $isWorkday || $dayEnd->lte($dayStart)) {
                $cursor = $cursor->copy()->addDay()->startOfDay();
                continue;
            }

            $segmentStart = $cursor->greaterThan($dayStart) ? $cursor : $dayStart;
            $segmentEnd = $end->lessThan($dayEnd) ? $end : $dayEnd;

            if ($segmentEnd->gt($segmentStart)) {
                $minutes += $segmentStart->diffInMinutes($segmentEnd);
            }

            $cursor = $cursor->copy()->addDay()->startOfDay();
        }

        return max(0, (int) $minutes);
    }

    /**
     * @return array{0:int,1:int}
     */
    private static function parseHm(string $value): array
    {
        $parts = explode(':', trim($value));
        $h = isset($parts[0]) ? max(0, min(23, (int) $parts[0])) : 8;
        $m = isset($parts[1]) ? max(0, min(59, (int) $parts[1])) : 0;
        return [$h, $m];
    }
}

