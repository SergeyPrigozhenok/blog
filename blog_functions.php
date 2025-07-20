<?php

// Формат отображения числа просмотров
function formatViews($views)
{
    if ($views <= 1000) {
        return number_format($views);
    } elseif ($views > 1000 && $views < 1000000) {
        return number_format($views / 1000, 1) . " k";
    } else {
        return number_format($views / 1000000, 1) . " m";
    }
    return number_format($views);
}

// Формат даты
function formatDate($dateString, $format = 'd F Y в H:i')
{

    $timestamp = strtotime($dateString);
    $formatted = date($format, $timestamp);
    return $formatted;
}

// Безопасно получить параметр из GET
function getParam($name, $default = '', $type = 'string')
{
    if (!isset($_GET[$name])) {
        return $default;
    }
    $value = $_GET[$name];
    switch ($type) {
        case 'int':
            return (int) $value;
        case 'float':
            return (float) $value;
        case 'bool':
            return (bool) $value;
        case 'string':
        default:
            return trim(htmlspecialchars($value));
    }
}

// Проверка ID на валидность
function isValidId($id)
{
    return is_numeric($id) && $id > 0;
}

// Безопасное получение статьи по ID
function getArticleSafely($articleID)
{
    if (!isValidId($articleID)) {
        return null;
    }
    return getArticleWithRelations($articleID);
}

// Функция поиска статей
function searchArticles($query, $categoryId = 0, $tagSlug = '', $authorId = 0)
{
    global $articles;

    $results = [];

    foreach ($articles as $article) {
        $fullArticle = getArticleWithRelations($article['id']);
        $matches = true;

        // Поиск по тексту
        if ($query && $matches) {
            $searchText = $fullArticle['title'] . ' ' . $fullArticle['content'] . ' ' . $fullArticle['excerpt'];
            $matches = stripos($searchText, $query) !== false;
        }

        // Фильтр по категории
        if ($categoryId && $matches) {
            $matches = $fullArticle['category_id'] === $categoryId;
        }

        // Фильтр по тегу
        if ($tagSlug && $matches) {
            $tagSlugs = array_column($fullArticle['tags'], 'slug');
            $matches = in_array($tagSlug, $tagSlugs);
        }

        // Фильтр по автору
        if ($authorId && $matches) {
            $matches = $fullArticle['author_id'] === $authorId;
        }

        if ($matches) {
            $results[] = $fullArticle;
        }
    }

    return $results;
}

// Сортировка статей по релевантности (просмотрам)
function sortArticlesByRelevance($articles)
{
    usort($articles, function ($a, $b) {
        return $b['meta']['views'] - $a['meta']['views'];
    });
    return $articles;
}

// Сортируем по дате публикации (новые сначала)
function sortArticlesByDate($articles, $order = 'desc')
{
    usort($articles, function ($a, $b) use ($order) {
        $result = strtotime($b['dates']['published']) - strtotime($a['dates']['published']);
        return $order === 'desc' ? $result : -$result;
    });
    return $articles;
}

// Общая статистика
function calculateBlogStats($articles)
{
    $totalViews = 0;
    foreach ($articles as $article) {
        $totalViews += $article['meta']['views'];
    }
    return [
        'total_articles' => count($articles),
        'total_views' => $totalViews,
        'total_authors' => count($GLOBALS['authors']),
        'total_categories' => count($GLOBALS['categories'])
    ];
}

// Генерация HTML для тегов
function generateTagsHtml($tags, $linkable = false)
{
    $html = '<div class="article-tags">';
    foreach ($tags as $tag) {
        if ($linkable) {
            $html .= '<a href="search.php?tag=' . urlencode($tag['slug']) . '" class="tag">';
            $html .= '#' . htmlspecialchars($tag['name']);
            $html .= '</a>';
        } else {
            $html .= '<span class = "tag">#' . htmlspecialchars($tag['name']) . '</span>';
        }
    }
    $html .= '</div>';
    return $html;
}

// Генерация мета-информации статьи
function generateArticleMeta($article, $showCategory = true)
{
    $html = '<div class="article-meta">';
    $html .= '<span>👤 ' . htmlspecialchars($article['author']['name']) . '</span>';
    if ($showCategory) {
        $html .= '<span class="category-badge">' . htmlspecialchars($article['category']['name']) . '</span>;';
    } else {
        $html .= '<span>📅 ' . date('d.m.Y', strtotime($article['dates']['published'])) . '</span>';
    }
    $html .= '</div>';
    return $html;
}

// Генерация статистики для статьи
function generateStatsHtml($meta)
{
    $html = '<div class="article-stats">';
    $html .= '<span>👁️ ' . formatViews($meta['views']) . '</span>';
    $html .= '<span>❤️ ' . $meta['likes'] . '</span>';

    if (isset($meta['comments_count'])) {
        $html .= '<span>💬 ' . $meta['comments_count'] . '</span>';
    }

    $html .= '<span>⏱️ ' . $meta['reading_time'] . ' мин</span>';
    $html .= '</div>';

    return $html;
}

// Генерация карточек
function generateArticleCard($article, $featured = false)
{
    $html = '<article class="article-card">';
    $html .= '<div class="article-image">';
    $html .= $featured ? '📖 ' . htmlspecialchars($article['title']) : '📄 ' . htmlspecialchars($article['category']['name']);
    $html .= '</div>';
    $html .= '<div class="article-content">';
    $html .= '<h3 class="article-title">' . htmlspecialchars($article['title']) . '</h3>';
    $html .= '<p class="article-excerpt">' . htmlspecialchars($article['excerpt']) . '</p>';
    $html .= generateArticleMeta($article, !$featured);
    $html .= generateTagsHtml($article['tags']);
    $html .= generateStatsHtml($article['meta']);
    $html .= '<a href="article.php?id=' . $article['id'] . '" class="read-more">Читать далее →</a>';
    $html .= '</div>';
    $html .= '</article>';
    return $html;
}
