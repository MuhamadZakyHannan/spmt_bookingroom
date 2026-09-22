<?php

function expectCalendarUi(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('[FAIL] ' . $message);
    }
    echo '[PASS] ' . $message . PHP_EOL;
}

$calendarView = file_get_contents(__DIR__ . '/../app/views/calendar/index.php');
$calendarController = file_get_contents(__DIR__ . '/../app/controllers/CalendarController.php');
$bookingForm = file_get_contents(__DIR__ . '/../app/views/booking/_form_fields.php');
$bookingScript = file_get_contents(__DIR__ . '/../public/js/booking-form.js');

expectCalendarUi(
    str_contains($calendarView, "listDayFormat: { day: '2-digit', month: 'long', year: 'numeric' }")
        && str_contains($calendarView, "listDaySideFormat: { weekday: 'long' }"),
    'Header Agenda menampilkan tanggal lengkap dan nama hari.'
);
expectCalendarUi(
    str_contains($calendarView, 'calendar-event-date')
        && str_contains($calendarView, 'extendedProps.date_formatted'),
    'Setiap baris Agenda menampilkan tanggal lengkap.'
);
expectCalendarUi(
    str_contains($calendarController, "'date_formatted' => format_date(\$b['date'])"),
    'API kalender menyediakan tanggal yang sudah diformat.'
);
expectCalendarUi(
    !preg_match('/pengajuan bersaing|\bSAW\b/i', $bookingForm . $bookingScript),
    'Form booking pengguna tidak menampilkan istilah teknis SAW.'
);

echo PHP_EOL . 'Hasil: 4 lulus, 0 gagal.' . PHP_EOL;
