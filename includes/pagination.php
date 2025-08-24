<?php
/**
 * Render Bootstrap pagination links.
 *
 * @param int   $currentPage    Current page number
 * @param int   $totalPages     Total number of pages
 * @param array $baseUrlParams  Query parameters to preserve
 * @param string|null $baseUrl  Base URL for pagination links (defaults to current script)
 *
 * @return string
 */
function renderPagination($currentPage, $totalPages, array $baseUrlParams = [], ?string $baseUrl = null): string
{
    if ($totalPages <= 1) return '';

    $baseUrl ??= $_SERVER['PHP_SELF'];

    $html = '<nav aria-label="Page navigation">';
    $html .= '<ul class="pagination justify-content-center pagination-color">';

    // Previous button
    $prevPage = max(1, $currentPage - 1);
    $prevDisabled = $currentPage <= 1 ? 'disabled d-none' : '';
    $html .= "<li class='page-item $prevDisabled'>
                <a class='page-link' href='" . buildPageUrl($prevPage, $baseUrlParams, $baseUrl) . "'>
                    <svg xmlns='http://www.w3.org/2000/svg' width='16px' height='16px' viewBox='0 3 24 24' fill='none' stroke='currentColor' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'><polyline points='15 18 9 12 15 6'></polyline></svg>
                </a>
              </li>";

    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= "<li class='page-item $active'>
                    <a class='page-link' href='" . buildPageUrl($i, $baseUrlParams, $baseUrl) . "'>$i</a>
                  </li>";
    }

    // Next button
    $nextPage = min($totalPages, $currentPage + 1);
    $nextDisabled = $currentPage >= $totalPages ? 'disabled d-none' : '';
    $html .= "<li class='page-item $nextDisabled'>
                <a class='page-link' href='" . buildPageUrl($nextPage, $baseUrlParams, $baseUrl) . "'>
                    <svg xmlns='http://www.w3.org/2000/svg' width='16px' height='16px' viewBox='0 3 24 24' fill='none' stroke='currentColor' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'><polyline points='9 18 15 12 9 6'></polyline></svg>
                </a>
              </li>";

    $html .= '</ul></nav>';

    return $html;
}

/**
 * Build a URL for a specific page preserving existing parameters.
 *
 * @param int $page    Target page number
 * @param array $params Existing query parameters
 * @param string $baseUrl Base path for links
 *
 * @return string
 */
function buildPageUrl($page, array $params, string $baseUrl): string
{
    $params['page_num'] = $page;
    return $baseUrl . '?' . http_build_query($params);
}
