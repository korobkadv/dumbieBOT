<?php

/**
 * Завантажує дії, фільтрує (опціонально) та повертає текст однієї випадкової дії.
 * @param string|null $range Рядок діапазону ID (напр., "101-200") або null для всіх дій.
 * @param bool $escapeOutput Чи потрібно екранувати вихідний текст для MarkdownV2 (за замовчуванням true).
 * @return array Масив ['type' => 'action_text', 'content' => string] або ['type' => 'error', 'content' => string]
 */
function getActionData($range = null, $escapeOutput = true): array {
    // 1. Завантажуємо всі дії
    try {
        $filePath = './data/actions.json';
        if (!file_exists($filePath)) {
            return ['type' => 'error', 'content' => "Файл `actions.json` не знайдено."];
        }
        $data = file_get_contents($filePath);
        $allActions = json_decode($data, true);
        if ($allActions === null || !is_array($allActions)) {
            $jsonError = json_last_error_msg();
             error_log("getActionData JSON Error: {$jsonError} in {$filePath}");
            return ['type' => 'error', 'content' => "Помилка читання або некоректний формат файлу `actions.json`."];
        }
    } catch (Exception $e) {
        error_log("getActionData Exception: " . $e->getMessage());
        return ['type' => 'error', 'content' => "Помилка завантаження дій."];
    }

    // 2. Фільтруємо дії, якщо вказано діапазон
    $filteredActions = [];
    if ($range !== null) {
        $rangeParts = explode('-', $range);
        if (count($rangeParts) === 2 && is_numeric($rangeParts[0]) && is_numeric($rangeParts[1])) {
            $min = (int)$rangeParts[0];
            $max = (int)$rangeParts[1];
            foreach ($allActions as $action) {
                if (isset($action['id']) && $action['id'] >= $min && $action['id'] <= $max) {
                    $filteredActions[] = $action;
                }
            }
        } else {
            // Некоректний формат діапазону - ігноруємо його, використовуємо всі дії
            $filteredActions = $allActions;
            error_log("Некоректний формат діапазону отримано в getActionData: $range");
        }
    } else {
        // Діапазон не вказано - використовуємо всі дії
        $filteredActions = $allActions;
    }

    // 3. Вибираємо випадкову дію з відфільтрованого списку
    if (empty($filteredActions)) {
        $rangeText = $range ? " у діапазоні [{$range}]" : "";
        return ['type' => 'error', 'content' => "На жаль, дій{$rangeText} не знайдено."];
    }
    
    try {
        $randomActionIndex = randomIndex(0, count($filteredActions)); // Використовуємо randomIndex
        $action = $filteredActions[$randomActionIndex];
    } catch (Exception $e) {
         error_log("getActionData random index Exception: " . $e->getMessage());
         return ['type' => 'error', 'content' => "Помилка вибору випадкової дії."];
    }
    
    // 4. Формуємо текст
    $messageText = "Дія #" . ($action['id'] ?? '?') . ": " . ($action['action'] ?? 'Невідома дія');
    
    // Екрануємо тільки якщо потрібно
    $finalContent = $escapeOutput ? escapeMarkdownV2($messageText) : $messageText;

    return ['type' => 'action_text', 'content' => $finalContent];
}


/**
 * Обробляє початкову команду /action, надсилаючи клавіатуру для вибору.
 */
function actionCommand($chatId) {
    // 1. Визначаємо клавіатуру 
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🎲 Випадкова дія', 'callback_data' => 'random_action'] // Змінив текст для ясності
            ],
             // Рядки з діапазонами
             [
                 ['text' => '1-100', 'callback_data' => 'action_range|1-100'],
                 ['text' => '101-200', 'callback_data' => 'action_range|101-200']
             ],
             [
                 ['text' => '201-300', 'callback_data' => 'action_range|201-300'],
                 ['text' => '301-400', 'callback_data' => 'action_range|301-400']
             ],
             [
                 ['text' => '401-500', 'callback_data' => 'action_range|401-500'],
                 ['text' => '501-600', 'callback_data' => 'action_range|501-600']
             ],
             [
                 ['text' => '601-700', 'callback_data' => 'action_range|601-700'],
                 ['text' => '701-800', 'callback_data' => 'action_range|701-800']
             ],
             [
                 ['text' => '801-900', 'callback_data' => 'action_range|801-900'],
                 ['text' => '901-1000', 'callback_data' => 'action_range|901-1000']
             ]
        ]
    ];
    
    // 2. Формуємо текст-запрошення
    $inviteText = escapeMarkdownV2("🎲 Оберіть випадкову дію або діапазон:");

    // 3. Надсилаємо повідомлення ТІЛЬКИ з текстом і клавіатурою
    sendMessage($chatId, $inviteText, 'text', $keyboard);
}

// --- Старий код видалено або закоментовано, він більше не потрібен --- 
/* ... */
?>