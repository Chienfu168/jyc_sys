<?php

namespace App\Domain\Calendar;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * iCalendar(.ics)解析器(純邏輯,不依賴資料庫或網路)。
 *
 * 將訂閱來的 ICS 文字展開成指定時間範圍內的事件清單,供行事曆唯讀顯示。
 * 支援:折行還原、VALUE=DATE 全天事件、UTC(Z)與 TZID 時區轉換、
 * 常見 RRULE(DAILY/WEEKLY/MONTHLY/YEARLY,含 INTERVAL/COUNT/UNTIL/BYDAY)、
 * 以及 EXDATE 例外日期。複雜的 BYSETPOS 等進階規則不在支援範圍。
 */
final class ICalParser
{
    /** 展開時每個事件最多產生的重複次數上限,避免異常規則造成無限迴圈。 */
    private const MAX_OCCURRENCES = 750;

    private const WEEKDAYS = ['SU' => 0, 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6];

    /**
     * 將 ICS 文字展開為 [$rangeStart, $rangeEnd] 範圍內的事件。
     *
     * @return array<int, array{uid: string, title: string, location: string, description: string,
     *     starts_at: string, ends_at: string, all_day: bool}>
     */
    public static function expand(
        string $ics,
        DateTimeImmutable $rangeStart,
        DateTimeImmutable $rangeEnd,
        DateTimeZone $displayTz
    ): array {
        $events = [];
        foreach (self::vevents(self::unfold($ics)) as $vevent) {
            foreach (self::expandEvent($vevent, $rangeStart, $rangeEnd, $displayTz) as $event) {
                $events[] = $event;
            }
        }

        usort($events, static fn (array $a, array $b): int => strcmp($a['starts_at'], $b['starts_at']));

        return $events;
    }

    /** 還原折行:以空白或 tab 開頭的行接續上一行。 */
    private static function unfold(string $ics): string
    {
        $ics = str_replace(["\r\n", "\r"], "\n", $ics);
        return preg_replace('/\n[ \t]/', '', $ics) ?? $ics;
    }

    /**
     * 取出所有 VEVENT 區塊,每塊為屬性行陣列。
     *
     * @return array<int, array<int, string>>
     */
    private static function vevents(string $ics): array
    {
        $lines = explode("\n", $ics);
        $blocks = [];
        $current = null;
        foreach ($lines as $line) {
            $trimmed = rtrim($line);
            if ($trimmed === 'BEGIN:VEVENT') {
                $current = [];
                continue;
            }
            if ($trimmed === 'END:VEVENT') {
                if ($current !== null) {
                    $blocks[] = $current;
                }
                $current = null;
                continue;
            }
            if ($current !== null && $trimmed !== '') {
                $current[] = $trimmed;
            }
        }

        return $blocks;
    }

    /**
     * 展開單一 VEVENT。
     *
     * @param array<int, string> $vevent
     * @return array<int, array<string, mixed>>
     */
    private static function expandEvent(
        array $vevent,
        DateTimeImmutable $rangeStart,
        DateTimeImmutable $rangeEnd,
        DateTimeZone $displayTz
    ): array {
        $props = [];
        foreach ($vevent as $line) {
            [$name, $params, $value] = self::parseLine($line);
            if ($name === '') {
                continue;
            }
            // 同名屬性(如 EXDATE)可重複,保留全部。
            $props[$name][] = ['params' => $params, 'value' => $value];
        }

        if (!isset($props['DTSTART'])) {
            return [];
        }

        $startRaw = $props['DTSTART'][0];
        $allDay = self::isDateOnly($startRaw['params'], $startRaw['value']);
        $start = self::toDateTime($startRaw['params'], $startRaw['value'], $displayTz, $allDay);
        if (!$start) {
            return [];
        }

        // 事件長度:優先用 DTEND,否則全天=1 天、定時=0。
        $duration = self::durationSeconds($props, $start, $displayTz, $allDay);

        $title = self::text($props['SUMMARY'][0]['value'] ?? '');
        $location = self::text($props['LOCATION'][0]['value'] ?? '');
        $description = self::text($props['DESCRIPTION'][0]['value'] ?? '');
        $uid = self::text($props['UID'][0]['value'] ?? '');

        $exdates = self::collectExdates($props, $displayTz);

        $starts = isset($props['RRULE'])
            ? self::expandRule($props['RRULE'][0]['value'], $start, $rangeStart, $rangeEnd, $allDay)
            : [$start];

        $events = [];
        foreach ($starts as $occurrenceStart) {
            $occurrenceEnd = $occurrenceStart->add(new DateInterval('PT' . max(0, $duration) . 'S'));

            // 落在範圍外或屬於例外日期則略過。
            if ($occurrenceEnd < $rangeStart || $occurrenceStart > $rangeEnd) {
                continue;
            }
            if (in_array($occurrenceStart->format('Y-m-d'), $exdates, true)) {
                continue;
            }

            $displayEnd = $occurrenceEnd;
            if ($allDay) {
                // 全天事件的 DTEND 為排他,顯示用的結束日往前一天。
                $displayEnd = $occurrenceEnd->sub(new DateInterval('P1D'));
                if ($displayEnd < $occurrenceStart) {
                    $displayEnd = $occurrenceStart;
                }
            }

            $events[] = [
                'uid' => $uid,
                'title' => $title !== '' ? $title : '(無標題)',
                'location' => $location,
                'description' => $description,
                'starts_at' => $occurrenceStart->format('Y-m-d H:i:s'),
                'ends_at' => $displayEnd->format('Y-m-d H:i:s'),
                'all_day' => $allDay,
            ];
        }

        return $events;
    }

    /**
     * 解析一行屬性為 [名稱, 參數陣列, 值]。
     *
     * @return array{0: string, 1: array<string, string>, 2: string}
     */
    private static function parseLine(string $line): array
    {
        $colon = strpos($line, ':');
        if ($colon === false) {
            return ['', [], ''];
        }

        $head = substr($line, 0, $colon);
        $value = substr($line, $colon + 1);

        $parts = explode(';', $head);
        $name = strtoupper(array_shift($parts));
        $params = [];
        foreach ($parts as $param) {
            $eq = strpos($param, '=');
            if ($eq === false) {
                continue;
            }
            $params[strtoupper(substr($param, 0, $eq))] = trim(substr($param, $eq + 1), '"');
        }

        return [$name, $params, $value];
    }

    /** @param array<string, string> $params */
    private static function isDateOnly(array $params, string $value): bool
    {
        if (($params['VALUE'] ?? '') === 'DATE') {
            return true;
        }
        return (bool) preg_match('/^\d{8}$/', trim($value));
    }

    /** @param array<string, string> $params */
    private static function toDateTime(array $params, string $value, DateTimeZone $displayTz, bool $allDay): ?DateTimeImmutable
    {
        $value = trim($value);

        try {
            if ($allDay) {
                $date = DateTimeImmutable::createFromFormat('!Ymd', substr($value, 0, 8), $displayTz);
                return $date ?: null;
            }

            // UTC(結尾 Z)。
            if (str_ends_with($value, 'Z')) {
                $utc = DateTimeImmutable::createFromFormat('Ymd\THis\Z', $value, new DateTimeZone('UTC'));
                return $utc ? $utc->setTimezone($displayTz) : null;
            }

            // 指定 TZID(視為 IANA 時區名稱)。
            if (!empty($params['TZID'])) {
                try {
                    $tz = new DateTimeZone($params['TZID']);
                } catch (Throwable) {
                    $tz = $displayTz;
                }
                $local = DateTimeImmutable::createFromFormat('Ymd\THis', $value, $tz);
                return $local ? $local->setTimezone($displayTz) : null;
            }

            // 浮動時間:以顯示時區解讀。
            $floating = DateTimeImmutable::createFromFormat('Ymd\THis', $value, $displayTz);
            return $floating ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * 計算事件長度(秒)。
     *
     * @param array<string, array<int, array{params: array<string,string>, value: string}>> $props
     */
    private static function durationSeconds(array $props, DateTimeImmutable $start, DateTimeZone $displayTz, bool $allDay): int
    {
        if (isset($props['DTEND'])) {
            $endRaw = $props['DTEND'][0];
            $endAllDay = self::isDateOnly($endRaw['params'], $endRaw['value']);
            $end = self::toDateTime($endRaw['params'], $endRaw['value'], $displayTz, $endAllDay);
            if ($end) {
                return max(0, $end->getTimestamp() - $start->getTimestamp());
            }
        }

        if (isset($props['DURATION'])) {
            $seconds = self::durationToSeconds($props['DURATION'][0]['value']);
            if ($seconds !== null) {
                return $seconds;
            }
        }

        return $allDay ? 86400 : 0;
    }

    /** 將 ICS DURATION(如 PT1H30M、P1D)轉為秒。 */
    private static function durationToSeconds(string $value): ?int
    {
        if (!preg_match('/^(-)?P(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/', trim($value), $m)) {
            return null;
        }
        $seconds = ((int) ($m[2] ?? 0)) * 604800
            + ((int) ($m[3] ?? 0)) * 86400
            + ((int) ($m[4] ?? 0)) * 3600
            + ((int) ($m[5] ?? 0)) * 60
            + ((int) ($m[6] ?? 0));

        return ($m[1] ?? '') === '-' ? -$seconds : $seconds;
    }

    /**
     * 收集 EXDATE 例外日期(以 Y-m-d 表示)。
     *
     * @param array<string, array<int, array{params: array<string,string>, value: string}>> $props
     * @return array<int, string>
     */
    private static function collectExdates(array $props, DateTimeZone $displayTz): array
    {
        if (!isset($props['EXDATE'])) {
            return [];
        }

        $dates = [];
        foreach ($props['EXDATE'] as $entry) {
            foreach (explode(',', $entry['value']) as $value) {
                $allDay = self::isDateOnly($entry['params'], $value);
                $dt = self::toDateTime($entry['params'], $value, $displayTz, $allDay);
                if ($dt) {
                    $dates[] = $dt->format('Y-m-d');
                }
            }
        }

        return $dates;
    }

    /**
     * 依 RRULE 展開重複事件的起始時間清單(限定於範圍內)。
     *
     * @return array<int, DateTimeImmutable>
     */
    private static function expandRule(
        string $rule,
        DateTimeImmutable $start,
        DateTimeImmutable $rangeStart,
        DateTimeImmutable $rangeEnd,
        bool $allDay
    ): array {
        $parts = [];
        foreach (explode(';', $rule) as $chunk) {
            $eq = strpos($chunk, '=');
            if ($eq !== false) {
                $parts[strtoupper(substr($chunk, 0, $eq))] = strtoupper(substr($chunk, $eq + 1));
            }
        }

        $freq = $parts['FREQ'] ?? '';
        $interval = max(1, (int) ($parts['INTERVAL'] ?? 1));
        $count = isset($parts['COUNT']) ? (int) $parts['COUNT'] : null;
        $until = null;
        if (!empty($parts['UNTIL'])) {
            $until = self::toDateTime([], $parts['UNTIL'], $start->getTimezone(), $allDay);
        }

        $byDays = [];
        if (!empty($parts['BYDAY'])) {
            foreach (explode(',', $parts['BYDAY']) as $day) {
                $code = substr($day, -2);
                if (isset(self::WEEKDAYS[$code])) {
                    $byDays[] = self::WEEKDAYS[$code];
                }
            }
        }

        $step = match ($freq) {
            'DAILY' => 'P' . $interval . 'D',
            'WEEKLY' => 'P' . ($interval * 7) . 'D',
            'MONTHLY' => 'P' . $interval . 'M',
            'YEARLY' => 'P' . $interval . 'Y',
            default => null,
        };
        if ($step === null) {
            return [$start];
        }

        $results = [];
        $generated = 0;
        $cursor = $start;

        // 週重複且指定 BYDAY:以「週」為單位,展開該週符合的星期。
        if ($freq === 'WEEKLY' && $byDays !== []) {
            $weekStart = $start->modify('-' . (int) $start->format('w') . ' days');
            while ($generated < self::MAX_OCCURRENCES) {
                foreach ($byDays as $dow) {
                    $day = $weekStart->add(new DateInterval('P' . $dow . 'D'))
                        ->setTime((int) $start->format('H'), (int) $start->format('i'), (int) $start->format('s'));
                    if ($day < $start) {
                        continue;
                    }
                    if ($until && $day > $until) {
                        return $results;
                    }
                    if ($count !== null && $generated >= $count) {
                        return $results;
                    }
                    if ($day <= $rangeEnd && $day >= $rangeStart->sub(new DateInterval('P1D'))) {
                        $results[] = $day;
                    }
                    $generated++;
                    if ($generated >= self::MAX_OCCURRENCES) {
                        break;
                    }
                }
                $weekStart = $weekStart->add(new DateInterval('P' . ($interval * 7) . 'D'));
                if ($weekStart > $rangeEnd) {
                    break;
                }
            }

            return $results;
        }

        while ($generated < self::MAX_OCCURRENCES) {
            if ($until && $cursor > $until) {
                break;
            }
            if ($count !== null && $generated >= $count) {
                break;
            }
            if ($cursor > $rangeEnd) {
                break;
            }
            if ($cursor >= $rangeStart->sub(new DateInterval('P1D'))) {
                $results[] = $cursor;
            }
            $cursor = $cursor->add(new DateInterval($step));
            $generated++;
        }

        return $results;
    }

    /** 還原 ICS 內文的跳脫字元。 */
    private static function text(string $value): string
    {
        $value = str_replace(['\\n', '\\N'], "\n", $value);
        $value = str_replace(['\\,', '\\;', '\\\\'], [',', ';', '\\'], $value);
        return trim($value);
    }
}
