<?php

require_once __DIR__ . '/bootstrap.php';
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
    str_contains($calendarView, "listDayFormat: { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' }")
        && str_contains($calendarView, 'listDaySideFormat: false')
        && str_contains($calendarView, "args.view.type.startsWith('list')")
        && str_contains($calendarView, "new Intl.DateTimeFormat('id-ID'")
        && str_contains($calendarView, "locale: 'id'")
        && str_contains($calendarView, '@fullcalendar/core@6.1.8/locales-all.global.min.js'),
    'Header Agenda merender nama hari dan tanggal lengkap secara eksplisit dalam bahasa Indonesia.'
);
expectCalendarUi(
    !str_contains($calendarView, 'calendar-event-date'),
    'Tanggal tidak diulang pada setiap baris Agenda.'
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
