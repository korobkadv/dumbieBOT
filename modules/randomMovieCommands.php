<?php

// Переконуємося, що константи API доступні
if (!defined('MOVIE_AUTH_KEY') || !defined('APP_ENV')) {
     error_log("Movie API Key or APP_ENV not defined!");
     // Можна повернути помилку або вийти, залежно від контексту виклику
     // Для функцій нижче це призведе до помилки при спробі виклику API
}

require_once __DIR__ . '/../helpers/escapeMarkdownV2.php';
require_once __DIR__ . '/../helpers/randomIndex.php';

define('TMDB_API_BASE_URL', 'https://api.themoviedb.org/3');

/**
 * Отримує список жанрів фільмів з TMDb API українською.
 * @return array Масив жанрів [['id' => int, 'name' => string], ...] або порожній масив у разі помилки.
 */
function getMovieGenres(): array {
    $apiKey = MOVIE_AUTH_KEY; // Використовуємо ключ TMDb
    $url = TMDB_API_BASE_URL . "/genre/movie/list?api_key={$apiKey}&language=uk-UA";

    try {
        $json = @file_get_contents($url);
        if ($json === false) {
            error_log("Failed to fetch movie genres from TMDb: " . $url);
            return [];
        }
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['genres']) || !is_array($data['genres'])) {
            error_log("Failed to decode JSON or invalid format for movie genres. Error: " . json_last_error_msg());
            return [];
        }
        // TMDb повертає ID в 'id', а назву в 'name'
        return $data['genres']; // Повертаємо масив як є

    } catch (Exception $e) {
        error_log("Exception fetching/processing movie genres: " . $e->getMessage());
        return [];
    }
}

/**
 * Отримує дані випадкового фільму за жанром або будь-якого.
 * @param int|null $genreId ID жанру або null для будь-якого жанру.
 * @param bool $escapeOutput Чи екранувати текстові поля для MarkdownV2.
 * @return array Масив з даними фільму або ['type' => 'error', 'content' => string] у разі помилки.
 */
function getRandomMovie($genreId = null, $escapeOutput = true): array {
    $apiKey = MOVIE_AUTH_KEY;
    $baseUrl = TMDB_API_BASE_URL . "/discover/movie?api_key={$apiKey}&language=uk-UA&include_adult=false&vote_count.gte=50"; // Додамо фільтр за кількістю голосів
    $urlParams = "";

    if ($genreId !== null && is_numeric($genreId)) {
        $urlParams .= "&with_genres=" . (int)$genreId;
        // Додатково можна сортувати за популярністю в жанрі
        $urlParams .= "&sort_by=popularity.desc";
    } else {
        // Для "будь-якого" жанру сортуємо за популярністю, щоб отримати більше сторінок
        $urlParams .= "&sort_by=popularity.desc";
    }

    // 1. Отримуємо загальну кількість сторінок (TMDb обмежує 500 сторінками для discover)
    $urlCount = $baseUrl . $urlParams . "&page=1";
    try {
        $jsonCount = @file_get_contents($urlCount);
        if ($jsonCount === false) {
            error_log("Failed to fetch movie count/page 1. Genre: " . ($genreId ?? 'any') . ". URL: " . $urlCount);
            return ['type' => 'error', 'content' => '❌ Не вдалося отримати інформацію про фільми.'];
        }
        $dataCount = json_decode($jsonCount, true);
         if (json_last_error() !== JSON_ERROR_NONE || !isset($dataCount['total_pages']) || !isset($dataCount['results'])) {
            error_log("Failed to decode JSON or get pagination for movies. Genre: " . ($genreId ?? 'any') . ". Error: " . json_last_error_msg());
            return ['type' => 'error', 'content' => '❌ Помилка отримання даних пагінації для фільмів.'];
        }

        $totalItems = (int)($dataCount['total_results'] ?? 0);
        if ($totalItems === 0) {
             $genreText = $genreId ? " цього жанру" : "";
             return ['type' => 'error', 'content' => "🤷 На жаль, фільмів{$genreText} не знайдено (за поточними фільтрами)."];
        }

        // TMDb повертає максимум 500 сторінок через API для discover
        $totalPages = min((int)$dataCount['total_pages'], 500);
        if ($totalPages <= 0) $totalPages = 1; // На випадок, якщо total_pages = 0, але є результати

        // 2. Вибираємо випадкову сторінку
        $randomPage = randomIndex(1, $totalPages);

        // 3. Завантажуємо дані з випадкової сторінки
        $urlPage = $baseUrl . $urlParams . "&page={$randomPage}";
        $jsonPage = @file_get_contents($urlPage);
         if ($jsonPage === false) {
            error_log("Failed to fetch movie page {$randomPage}. Genre: " . ($genreId ?? 'any') . ". URL: " . $urlPage);
            return ['type' => 'error', 'content' => '❌ Не вдалося завантажити сторінку з фільмами.'];
        }
        $dataPage = json_decode($jsonPage, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($dataPage['results']) || !is_array($dataPage['results']) || empty($dataPage['results'])) {
             error_log("Failed to decode JSON or no movie data found on page {$randomPage}. Genre: " . ($genreId ?? 'any') . ". Error: " . json_last_error_msg());
             return ['type' => 'error', 'content' => '❌ Помилка отримання даних зі сторінки фільмів.'];
        }

        // 4. Вибираємо випадковий фільм зі сторінки, перевіряючи валідність
        $movieListOnPage = $dataPage['results'];
        shuffle($movieListOnPage);

        $validMovieFound = null;
        foreach ($movieListOnPage as $potentialMovie) {
            // Перевіряємо наявність основних полів (ID, Назва, Опис, Постер)
            if (isset($potentialMovie['id'], $potentialMovie['title'], $potentialMovie['overview'], $potentialMovie['poster_path']) &&
                !empty($potentialMovie['title']) && !empty($potentialMovie['overview']) && !empty($potentialMovie['poster_path']))
             {
                $validMovieFound = $potentialMovie;
                break;
            }
        }

        // 5. Перевіряємо, чи знайшли валідне аніме на сторінці
        if ($validMovieFound === null) {
             error_log("No valid movie found on page {$randomPage}. Genre: " . ($genreId ?? 'any') . " after checking " . count($movieListOnPage) . " entries.");
             return ['type' => 'error', 'content' => '❌ Не вдалося знайти повні дані для випадкового фільму на цій сторінці. Спробуйте ще раз.'];
        }

        // 6. Повертаємо дані знайденого фільму
        $movie = $validMovieFound;
        $title = $movie['title']; // Використовуємо оригінальну назву (локалізовану)
        $overview = $movie['overview'];
        $rating = $movie['vote_average'] ?? 'N/A';
        $releaseDate = $movie['release_date'] ?? null;
        $year = $releaseDate ? substr($releaseDate, 0, 4) : 'N/A';
        $posterPath = $movie['poster_path']; // Вже перевірили наявність

        return [
            'type'          => 'movie_data',
            'id'            => $movie['id'],
            'title'         => $escapeOutput ? escapeMarkdownV2($title) : $title,
            'overview'      => $escapeOutput ? escapeMarkdownV2($overview) : $overview,
            'poster_path'   => $posterPath, // Повертаємо лише шлях
            'vote_average'  => $escapeOutput ? escapeMarkdownV2(sprintf("%.1f", $rating)) : sprintf("%.1f", $rating), // Форматуємо рейтинг
            'release_year'  => $escapeOutput ? escapeMarkdownV2($year) : $year,
        ];

    } catch (Exception $e) {
        error_log("Exception in getRandomMovie. Genre: " . ($genreId ?? 'any') . ". Error: " . $e->getMessage());
        return ['type' => 'error', 'content' => '❌ Внутрішня помилка при пошуку випадкового фільму.'];
    }
}

?>