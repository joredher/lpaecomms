<?php
/**
 * Simple pagination builder that avoids modifying the URL.
 * Links include a data-page attribute so JS can intercept navigation.
 */
function renderPagination($currentPage, $totalPages): string
{
    if ($totalPages <= 1) return '';

    $html = '<nav aria-label="Page navigation">';
    $html .= '<ul class="pagination justify-content-center pagination-color">';

    // Previous button
    $prevPage = max(1, $currentPage - 1);
    $prevDisabled = $currentPage <= 1 ? 'disabled d-none' : '';
    $html .= "<li class='page-item $prevDisabled'>"
           . "<a class='page-link' href='#' data-page='$prevPage'>"
           . "<svg xmlns='http://www.w3.org/2000/svg' width='16px' height='16px' viewBox='0 3 24 24' fill='none' stroke='currentColor' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'><polyline points='15 18 9 12 15 6'></polyline></svg>"
           . "</a></li>";

    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= "<li class='page-item $active'>"
               . "<a class='page-link' href='#' data-page='$i'>$i</a>"
               . "</li>";
    }

    // Next button
    $nextPage = min($totalPages, $currentPage + 1);
    $nextDisabled = $currentPage >= $totalPages ? 'disabled d-none' : '';
    $html .= "<li class='page-item $nextDisabled'>"
           . "<a class='page-link' href='#' data-page='$nextPage'>"
           . "<svg xmlns='http://www.w3.org/2000/svg' width='16px' height='16px' viewBox='0 3 24 24' fill='none' stroke='currentColor' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'><polyline points='9 18 15 12 9 6'></polyline></svg>"
           . "</a></li>";

    $html .= '</ul></nav>';

    return $html;
}
