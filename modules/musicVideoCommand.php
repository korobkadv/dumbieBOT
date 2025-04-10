<?php
function musicVideoCommand($chatId) {
    $filePath = './data/musicVideo.json';
    if (!file_exists($filePath)) {
        error_log("musicVideoCommand: File not found: " . $filePath);
        return ['type' => 'error', 'content' => '❗ Помилка: Файл з музичними відео не знайдено.'];
    }

    $data = file_get_contents($filePath);
    if ($data === false) {
        error_log("musicVideoCommand: Failed to read file: " . $filePath);
        return ['type' => 'error', 'content' => '❗ Помилка читання файлу з музичними відео.'];
    }

    $allMusicVideo = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
         error_log("musicVideoCommand: Failed to decode JSON from " . $filePath . " Error: " . json_last_error_msg());
        return ['type' => 'error', 'content' => '❗ Помилка обробки файлу з музичними відео.'];
    }

    if (empty($allMusicVideo)) {
         error_log("musicVideoCommand: No videos found in " . $filePath);
        return ['type' => 'error', 'content' => '❗ У файлі немає музичних відео.'];
    }

    try {
        $randomVideoIndex = random_int(0, count($allMusicVideo) - 1);
        // Перевіряємо існування ключа 'url'
        if (!isset($allMusicVideo[$randomVideoIndex]['url'])) {
            error_log("musicVideoCommand: Key 'url' not found for index " . $randomVideoIndex . " in " . $filePath);
             return ['type' => 'error', 'content' => '❗ Помилка даних: відсутнє посилання на відео.'];
        }
        $urlVideo = $allMusicVideo[$randomVideoIndex]['url'];

        // Повертаємо URL відео
        return [
            'type' => 'video_url',
            'url' => $urlVideo
        ];
    } catch (Exception $e) {
        error_log("musicVideoCommand: Error selecting random video: " . $e->getMessage());
        return ['type' => 'error', 'content' => '❗ Помилка вибору випадкового відео.'];
    }
}
?>