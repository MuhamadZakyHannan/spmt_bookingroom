<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/BookingModel.php';

function assertPaginationTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('[FAIL] ' . $message);
    }
    echo '[PASS] ' . $message . PHP_EOL;
}

echo "Memulai pengujian Server-Side AJAX Pagination & Debounce & Calendar Search..." . PHP_EOL;

$bookingModel = new BookingModel();

// 1. Verifikasi countAllBookings
$totalAll = $bookingModel->countAllBookings();
assertPaginationTest($totalAll >= 0, "countAllBookings() mengembalikan nilai integer total data valid: {$totalAll}.");

// 2. Verifikasi countAllBookings dengan filter status
$totalConfirmed = $bookingModel->countAllBookings('', 'confirmed');
assertPaginationTest($totalConfirmed >= 0 && $totalConfirmed <= $totalAll, "countAllBookings('', 'confirmed') mengembalikan total confirmed valid: {$totalConfirmed}.");

// 3. Verifikasi getAllBookings dengan limit dan offset
$limit = 5;
$page1 = $bookingModel->getAllBookings('', '', $limit, 0);
assertPaginationTest(count($page1) <= $limit, "getAllBookings(limit=5, offset=0) membatasi hasil maksimal 5 baris (didapat: " . count($page1) . ").");

if ($totalAll > 5) {
    $page2 = $bookingModel->getAllBookings('', '', $limit, 5);
    assertPaginationTest(count($page2) <= $limit, "getAllBookings(limit=5, offset=5) halaman 2 berhasil diambil.");
    if (count($page1) > 0 && count($page2) > 0) {
        assertPaginationTest($page1[0]['id'] !== $page2[0]['id'], "Baris pertama halaman 1 berbeda dengan baris pertama halaman 2 (offset bekerja).");
    }
}

// 4. Verifikasi pencarian agenda kalender
$calendarEventsAll = $bookingModel->getCalendarEvents(0, '');
assertPaginationTest(is_array($calendarEventsAll), "getCalendarEvents() mengembalikan array agenda.");

if (count($calendarEventsAll) > 0) {
    $firstTitle = $calendarEventsAll[0]['title'];
    $searchSubstring = mb_substr($firstTitle, 0, 3);
    $filteredEvents = $bookingModel->getCalendarEvents(0, $searchSubstring);
    assertPaginationTest(count($filteredEvents) >= 1, "Pencarian agenda kalender dengan query '{$searchSubstring}' mengembalikan hasil yang relevan.");
}

// 5. Verifikasi kontrak arsitektur file api/admin_bookings_live.php
$apiLiveSource = (string) file_get_contents(__DIR__ . '/../api/admin_bookings_live.php');
assertPaginationTest(
    str_contains($apiLiveSource, '$maxCeiling = 500')
        && str_contains($apiLiveSource, '$offset = ($page - 1) * $limit')
        && str_contains($apiLiveSource, "'total_pages' =>")
        && str_contains($apiLiveSource, '$totalMatching = $bookingModel->countAllBookings'),
    "API endpoint admin_bookings_live menerapkan pembatasan memory ceiling (max 500), pagination server-side, dan total_pages."
);

// 6. Verifikasi proteksi debounce pada public/js/admin-bookings.js
$adminJsSource = (string) file_get_contents(__DIR__ . '/../public/js/admin-bookings.js');
assertPaginationTest(
    str_contains($adminJsSource, 'function debounce(')
        && str_contains($adminJsSource, 'handleSearchInput = debounce('),
    "Aset admin-bookings.js menerapkan fungsi debounce pada input pencarian live."
);

// 7. Verifikasi perbaikan search kalender pada app/views/calendar/index.php
$calendarIndexSource = (string) file_get_contents(__DIR__ . '/../app/views/calendar/index.php');
assertPaginationTest(
    !str_contains($calendarIndexSource, 'calendarInstance.rerenderEvents()')
        && str_contains($calendarIndexSource, 'calendarInstance.refetchEvents()')
        && str_contains($calendarIndexSource, 'cachedCalendarEvents')
        && str_contains($calendarIndexSource, 'searchDebounceTimer'),
    "View kalender menggantikan rerenderEvents yang usang dengan refetchEvents, caching memori lokal, dan debounce timer."
);

echo PHP_EOL . "Hasil: Semua pengujian Server-Side AJAX Pagination & Calendar Search LULUS 100%!" . PHP_EOL;
