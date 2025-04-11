<?php

// Включаємо хелпери відносно поточного файлу
require_once __DIR__ . '/../helpers/escapeMarkdownV2.php';
require_once __DIR__ . '/../helpers/randomIndex.php';

/**
 * Отримує список жанрів аніме з Jikan API.
 * @return array Масив жанрів [['id' => int, 'name' => string], ...] або порожній масив у разі помилки.
 */
function getAnimeGenres(): array {
    $url = JIKAN_API_BASE_URL . '/genres/anime';
    try {
        $json = @file_get_contents($url); // Використовуємо @ для придушення попереджень у разі помилки
        if ($json === false) {
            error_log("Failed to fetch anime genres from: " . $url);
            return [];
        }
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['data']) || !is_array($data['data'])) {
             error_log("Failed to decode JSON or invalid format for anime genres. Error: " . json_last_error_msg());
            return [];
        }
        
        // Повертаємо тільки потрібні поля
        $genres = [];
        foreach ($data['data'] as $genre) {
            if (isset($genre['mal_id']) && isset($genre['name'])) {
                $genres[] = ['id' => $genre['mal_id'], 'name' => $genre['name']];
            }
        }
        return $genres;

    } catch (Exception $e) {
        error_log("Exception fetching/processing anime genres: " . $e->getMessage());
        return [];
    }
}

/**
 * Отримує дані випадкового аніме за заданим ID жанру.
 * Враховує пагінацію для вибору зі всіх сторінок.
 * @param int $genreId ID жанру.
 * @return array Масив з даними аніме або ['type' => 'error', 'content' => string] у разі помилки.
 */
function getRandomAnimeByGenre(int $genreId): array {
    // 1. Отримуємо загальну кількість та кількість сторінок
    $urlCount = JIKAN_API_BASE_URL . "/anime?genres={$genreId}&limit=1"; // Запит для отримання пагінації
    try {
        $jsonCount = @file_get_contents($urlCount);
        if ($jsonCount === false) {
            error_log("Failed to fetch anime count for genre ID {$genreId} from: " . $urlCount);
            return ['type' => 'error', 'content' => '❌ Не вдалося отримати інформацію про кількість аніме цього жанру.'];
        }
        $dataCount = json_decode($jsonCount, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($dataCount['pagination']['items']['total'])) {
            error_log("Failed to decode JSON or get total items for genre ID {$genreId}. Error: " . json_last_error_msg());
             // Можливо, жанр просто не існує або немає аніме
            if (isset($dataCount['status']) && $dataCount['status'] == 404) {
                 return ['type' => 'error', 'content' => '❌ Жанр не знайдено або в ньому немає аніме.'];
            }
             return ['type' => 'error', 'content' => '❌ Помилка отримання даних пагінації для жанру.'];
        }

        $totalItems = (int)$dataCount['pagination']['items']['total'];
        $itemsPerPage = (int)($dataCount['pagination']['items']['per_page'] ?? 25);

        if ($totalItems === 0) {
            return ['type' => 'error', 'content' => '🤷 На жаль, аніме цього жанру не знайдено.'];
        }

        $totalPages = ceil($totalItems / $itemsPerPage);
        
        // 2. Вибираємо випадкову сторінку
        $randomPage = randomIndex(1, $totalPages); // Використовуємо наш хелпер randomIndex

        // 3. Завантажуємо дані з випадкової сторінки
        $urlPage = JIKAN_API_BASE_URL . "/anime?genres={$genreId}&page={$randomPage}&limit={$itemsPerPage}";
        $jsonPage = @file_get_contents($urlPage);
         if ($jsonPage === false) {
            error_log("Failed to fetch anime page {$randomPage} for genre ID {$genreId} from: " . $urlPage);
            return ['type' => 'error', 'content' => '❌ Не вдалося завантажити сторінку з аніме.'];
        }
        $dataPage = json_decode($jsonPage, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($dataPage['data']) || !is_array($dataPage['data']) || empty($dataPage['data'])) {
             error_log("Failed to decode JSON or no data found on page {$randomPage} for genre ID {$genreId}. Error: " . json_last_error_msg());
             return ['type' => 'error', 'content' => '❌ Помилка отримання даних зі сторінки аніме.'];
        }

        // 4. Вибираємо випадкове аніме зі сторінки, перевіряючи валідність
        $animeListOnPage = $dataPage['data'];
        shuffle($animeListOnPage); // Перемішуємо результати на сторінці
        
        $validAnimeFound = null; // Змінна для зберігання валідного аніме
        
        foreach ($animeListOnPage as $potentialAnime) {
            // Перевіряємо конкретний запис на наявність основних полів
            if (isset($potentialAnime['mal_id'], $potentialAnime['images']['jpg']['image_url']) && 
                (isset($potentialAnime['title']) || isset($potentialAnime['title_english'])))
            {
                // Знайшли валідний запис!
                $validAnimeFound = $potentialAnime;
                break; // Виходимо з циклу, бо знайшли те, що шукали
            }
        }

        // 5. Перевіряємо, чи знайшли валідне аніме на сторінці
        if ($validAnimeFound === null) {
             error_log("No valid anime found on page {$randomPage} for genre ID {$genreId} after checking " . count($animeListOnPage) . " entries.");
             return ['type' => 'error', 'content' => '❌ Не вдалося знайти повні дані для випадкового аніме цього жанру на цій сторінці. Спробуйте ще раз.'];
        }
        
        // 6. Повертаємо дані знайденого валідного аніме
        $randomAnime = $validAnimeFound; // Використовуємо знайдене аніме

        return [
            'type' => 'anime_data', // Спеціальний тип для подальшої обробки
            'id' => $randomAnime['mal_id'],
            'title' => $randomAnime['title_english'] ?? $randomAnime['title'] ?? 'N/A', // Пріоритет англійській назві
            'image_url' => $randomAnime['images']['jpg']['image_url'],
            'score' => $randomAnime['score'] ?? 'N/A',
            'year' => $randomAnime['aired']['prop']['from']['year'] ?? $randomAnime['year'] ?? 'N/A',
             // 'episodes' => $randomAnime['episodes'] ?? 'N/A', // Більше не використовуємо
            'url' => $randomAnime['url'] ?? null
        ];

    } catch (Exception $e) {
        error_log("Exception in getRandomAnimeByGenre for genre ID {$genreId}: " . $e->getMessage());
        return ['type' => 'error', 'content' => '❌ Внутрішня помилка при пошуку випадкового аніме.'];
    }
}

/**
 * Отримує дані абсолютно випадкового аніме (незалежно від жанру).
 * Використовує ендпоінт /random/anime Jikan API.
 * @return array Масив з даними аніме або ['type' => 'error', 'content' => string] у разі помилки.
 */
function getTrulyRandomAnime(): array {
    $url = JIKAN_API_BASE_URL . "/random/anime";
    try {
        $json = @file_get_contents($url);
        if ($json === false) {
            error_log("Failed to fetch random anime from: " . $url);
            return ['type' => 'error', 'content' => '❌ Не вдалося отримати випадкове аніме.'];
        }
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['data']) || !is_array($data['data'])) {
             error_log("Failed to decode JSON or invalid format for random anime. Error: " . json_last_error_msg());
             return ['type' => 'error', 'content' => '❌ Помилка обробки даних випадкового аніме.'];
        }

        $randomAnime = $data['data'];

        // Перевіряємо наявність основних полів: ID, зображення та хоча б однієї назви
        if (!isset($randomAnime['mal_id'], $randomAnime['images']['jpg']['image_url']) || 
            (!isset($randomAnime['title']) && !isset($randomAnime['title_english'])))
        {
             error_log("Missing essential data (id/image/title) in truly random anime. Anime ID: " . ($randomAnime['mal_id'] ?? 'N/A'));
             return ['type' => 'error', 'content' => '❌ Не вдалося обробити дані випадкового аніме (відсутні ID, зображення або назва).'];
        }

        return [
            'type' => 'anime_data',
            'id' => $randomAnime['mal_id'],
            'title' => $randomAnime['title_english'] ?? $randomAnime['title'] ?? 'N/A',
            'image_url' => $randomAnime['images']['jpg']['image_url'],
            'score' => $randomAnime['score'] ?? 'N/A',
            'year' => $randomAnime['aired']['prop']['from']['year'] ?? $randomAnime['year'] ?? 'N/A',
            // 'episodes' => $randomAnime['episodes'] ?? 'N/A', // Більше не використовуємо
            'url' => $randomAnime['url'] ?? null
        ];

    } catch (Exception $e) {
        error_log("Exception in getTrulyRandomAnime: " . $e->getMessage());
        return ['type' => 'error', 'content' => '❌ Внутрішня помилка при пошуку випадкового аніме.'];
    }
}

// Стара функція randomAnimeCommand більше не потрібна, оскільки її логіка тепер розбита
// на getAnimeGenres (викликається з dumbiebot.php) та getRandomAnimeByGenre (викликається з dumbiebot.php).
/*
function randomAnimeCommand($chatId) { ... стара логіка ... }
*/

?>
