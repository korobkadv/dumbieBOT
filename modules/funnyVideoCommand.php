<?php
function funnyVideoCommand($chatId) {
    $filePath = './data/funnyVideo.json';
    if (!file_exists($filePath)) {
        error_log("funnyVideoCommand: File not found: " . $filePath);
        return ['type' => 'error', 'content' => '❗ Помилка: Файл з відео не знайдено.'];
    }

    $data = file_get_contents($filePath);
    if ($data === false) {
        error_log("funnyVideoCommand: Failed to read file: " . $filePath);
        return ['type' => 'error', 'content' => '❗ Помилка читання файлу з відео.'];
    }

    $allFunnyVideo = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
         error_log("funnyVideoCommand: Failed to decode JSON from " . $filePath . " Error: " . json_last_error_msg());
        return ['type' => 'error', 'content' => '❗ Помилка обробки файлу з відео.'];
    }

    if (empty($allFunnyVideo)) {
         error_log("funnyVideoCommand: No videos found in " . $filePath);
        return ['type' => 'error', 'content' => '❗ У файлі немає відео.'];
    }

    try {
        $randomVideoIndex = random_int(0, count($allFunnyVideo) - 1);
        if (!isset($allFunnyVideo[$randomVideoIndex]['url'])) {
            error_log("funnyVideoCommand: Key 'url' not found for index " . $randomVideoIndex . " in " . $filePath);
             return ['type' => 'error', 'content' => '❗ Помилка даних: відсутнє посилання на відео.'];
        }
    $urlVideo = $allFunnyVideo[$randomVideoIndex]['url'];

        // Повертаємо URL відео
        return [
            'type' => 'video_url', // Використовуємо той самий тип
            'url' => $urlVideo
        ];
    } catch (Exception $e) {
        error_log("funnyVideoCommand: Error selecting random video: " . $e->getMessage());
        return ['type' => 'error', 'content' => '❗ Помилка вибору випадкового відео.'];
    }
}
?>