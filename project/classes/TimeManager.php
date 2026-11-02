<?php 

class TimeManager {
    private const CA1 = 'America/Los_Angeles';

    public static function toDateTimeCA1(?string $stamp): ?DateTimeImmutable {
        if ($stamp === null) return null;
        $utcTime = new DateTimeImmutable(
            $stamp,
            new DateTimeZone('UTC')
        );

        return $utcTime->setTimezone(
            new DateTimeZone(self::CA1)
        );
    }

    public static function toSQLUTC(?DateTimeImmutable $dateTime): ?string
    {
        if ($dateTime === null) return null;
        return $dateTime
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');
    }

    public static function toSQLUTCDayOnly(?DateTimeImmutable $dateTime): ?string
    {
        if ($dateTime === null) return null;
        return $dateTime
            ->setTimezone(new DateTimeZone('UTC'))
            ->setTime(0,0,0)
            ->format('Y-m-d H:i:s');
    }
}