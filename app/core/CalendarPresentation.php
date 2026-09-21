<?php

/**
 * Menyimpan aturan presentasi kalender agar warna ruangan konsisten
 * antara event, filter, dan legenda.
 */
class CalendarPresentation {
    private const ROOM_COLORS = [
        '#1a73e8',
        '#d93025',
        '#188038',
        '#9334e6',
        '#e8710a',
        '#039be5',
        '#c5221f',
        '#5f6368',
    ];

    public static function roomColor(int $roomId): string {
        $index = max(0, $roomId - 1) % count(self::ROOM_COLORS);
        return self::ROOM_COLORS[$index];
    }

    public static function decorateRooms(array $rooms): array {
        return array_map(static function (array $room): array {
            $room['calendar_color'] = self::roomColor((int)$room['id']);
            return $room;
        }, $rooms);
    }
}
