<?php
/**
 * @param $currentPage
 * @param $totalPages
 * @param array $baseUrlParams
 * @return string
 */
function renderPagination($currentPage, $totalPages, array $baseUrlParams = []): string
{
    if ($totalPages <= 1) return '';

    $html = '<nav aria-label="Page navigation">';
    $html .= '<ul class="pagination justify-content-center pagination-color">';

    // Previous button
    $prevPage = max(1, $currentPage - 1);
    $prevDisabled = $currentPage <= 1 ? 'disabled d-none' : '';
    $html .= "<li class='page-item $prevDisabled'>
                <a class='page-link' href='" . buildPageUrl($prevPage, $baseUrlParams) . "'>
                    <svg xmlns='http://www.w3.org/2000/svg' width='16px' height='16px' viewBox='0 3 24 24' fill='none' stroke='currentColor' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'><polyline points='15 18 9 12 15 6'></polyline></svg>
                </a>
              </li>";

    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= "<li class='page-item $active'>
                    <a class='page-link' href='" . buildPageUrl($i, $baseUrlParams) . "'>$i</a>
                  </li>";
    }

    // Next button
    $nextPage = min($totalPages, $currentPage + 1);
    $nextDisabled = $currentPage >= $totalPages ? 'disabled d-none' : '';
    $html .= "<li class='page-item $nextDisabled'>
                <a class='page-link' href='" . buildPageUrl($nextPage, $baseUrlParams) . "'>
                    <svg xmlns='http://www.w3.org/2000/svg' width='16px' height='16px' viewBox='0 3 24 24' fill='none' stroke='currentColor' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'><polyline points='9 18 15 12 9 6'></polyline></svg>
                </a>
              </li>";

    $html .= '</ul></nav>';

    return $html;
}

/**
 * @param $page
 * @param $params
 * @return string
 */
function buildPageUrl($page, $params) : string
{
    $params['page_num'] = $page;

    // Normalize and clean params for pretty URLs
    if (isset($params['route'])) unset($params['route']);

    $clean = [];
    foreach ($params as $key => $val) {
        // Drop empty or default sort
        if ($key === 'sort' && ($val === '' || $val === 'popular')) continue;

        // Remove empty price values
        if (($key === 'min_price' || $key === 'max_price') && ($val === '' || $val === null)) continue;

        // Normalize type[] (ensure '4' (All) is not carried)
        if ($key === 'type') {
            if (is_array($val)) {
                $val = array_values(array_filter($val, fn($v) => (string)$v !== '4'));
                if (empty($val)) continue; // nothing to include
            } else {
                if ((string)$val === '4' || $val === '') continue;
            }
        }

        // Keep others as-is
        $clean[$key] = $val;
    }

    $qs = http_build_query($clean);
    return '/products' . ($qs ? ('?' . $qs) : '');
}
