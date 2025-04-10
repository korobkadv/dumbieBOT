<?php
function quoteCommand($chatId) { // chatId залишається для потенційної майбутньої логіки, специфічної для чату
    $filePath = './data/quote.json';
    if (!file_exists($filePath)) {
        error_log("quoteCommand: File not found: " . $filePath);
        // Повертаємо помилку у стандартному форматі для обробки в api.php та dumbiebot.php
        return ['type' => 'error', 'content' => 'Помилка: Файл з цитатами не знайдено.'];
    }

    $data = file_get_contents($filePath);
    if ($data === false) {
        error_log("quoteCommand: Failed to read file: " . $filePath);
        return ['type' => 'error', 'content' => 'Помилка читання файлу з цитатами.'];
    }

    $allQuotes = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
         error_log("quoteCommand: Failed to decode JSON from " . $filePath . " Error: " . json_last_error_msg());
        return ['type' => 'error', 'content' => 'Помилка обробки файлу з цитатами.'];
    }

    if (empty($allQuotes)) {
         error_log("quoteCommand: No quotes found in " . $filePath);
        return ['type' => 'error', 'content' => 'У файлі немає цитат.'];
    }

    // Випадковий вибір цитати
    try {
        $randomQuoteIndex = random_int(0, count($allQuotes) - 1);
        $quoteData = $allQuotes[$randomQuoteIndex];
        $quote = $quoteData['quote'] ?? 'Цитата відсутня';
        $title = $quoteData['title'] ?? 'Невідоме джерело';

        // Повертаємо *неформатовані* дані
        return [
            'type' => 'quote_data', // Новий тип для розрізнення
            'quote' => $quote,
            'title' => $title
        ];
    } catch (Exception $e) {
        error_log("quoteCommand: Error selecting random quote: " . $e->getMessage());
         // Повертаємо помилку у стандартному форматі
        return ['type' => 'error', 'content' => 'Помилка вибору випадкової цитати.'];
    }
}
?>