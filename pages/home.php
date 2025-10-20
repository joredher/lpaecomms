<?php
require_once 'includes/config.php';
require_once APP_PATH . '/services/HomeContentService.php';

$homeContentService = new HomeContentService();
$homeContent = $homeContentService->getHomeContent();
$hero = $homeContent['hero'];
$featureBlurbs = $homeContent['featureBlurbs'];
$featureImages = $homeContent['featureImages'];
$searchPageLink = $hero['search']['action'] ?? '';
?>

<div class="frame-parent">
    <div class="frame-group">
        <div class="explore-logic-peripherals-au-parent">
            <div class="explore-logic-peripherals"><?php echo htmlspecialchars($hero['title'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="frame-container">
                <?php foreach ($hero['stats'] as $index => $stat): ?>
                    <div class="parent">
                        <div class="div"><?php echo htmlspecialchars($stat['value'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="peripherals-parts"><?php echo htmlspecialchars($stat['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <?php if ($index < count($hero['stats']) - 1): ?>
                        <div class="frame-child"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="what-are-you-looking-for-parent">
            <form method="get"<?php if (!empty($searchPageLink)) { ?> action="<?php echo htmlspecialchars($searchPageLink, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>>
                <label>
                    <input
                        type="text"
                        name="query"
                        placeholder="<?php echo htmlspecialchars($hero['search']['placeholder'], ENT_QUOTES, 'UTF-8'); ?>"
                        class="what-are-you"
                        style="border: none; outline: none; background: transparent; height: 100%; padding-left: 18px;"
                    >
                </label>
                <button type="submit" class="frame-item" style="border: none; cursor: pointer;">
                    <img class="search-icon" src="<?php echo htmlspecialchars($hero['search']['icon'], ENT_QUOTES, 'UTF-8'); ?>" alt="Search Icon">
                </button>
            </form>
        </div>
    </div>
    <div class="frame-inner">
    </div>
    <img class="image-icon" alt="" src="<?php echo htmlspecialchars($hero['images']['hero'], ENT_QUOTES, 'UTF-8'); ?>">
    <img class="vector-icon" alt="" src="<?php echo htmlspecialchars($hero['images']['vector'], ENT_QUOTES, 'UTF-8'); ?>">
    <img class="frame-child1" alt="" src="<?php echo htmlspecialchars($hero['images']['vector_overlay'], ENT_QUOTES, 'UTF-8'); ?>">
</div>

<div class="about-us-section">
    <div class="about-us-container">
        <div class="about-us-item">
            <b class="feature-title">About us</b>
            <div class="about-us-subtitle">Proudly designed for Australia by Logic Peripherals</div>
        </div>
        <div class="features-list">
            <?php foreach ($featureBlurbs as $feature): ?>
                <div class="about-us-item">
                    <div class="feature-item">
                        <img class="feature-icon" alt="" src="<?php echo htmlspecialchars($feature['icon'], ENT_QUOTES, 'UTF-8'); ?>">
                        <b class="feature-title"><?php echo htmlspecialchars($feature['title'], ENT_QUOTES, 'UTF-8'); ?></b>
                    </div>
                    <div class="feature-description"><?php echo htmlspecialchars($feature['description'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="features-section">
    <div class="feature-grid">
        <?php foreach ($featureImages as $image): ?>
            <div class="feature-card">
                <img src="<?php echo htmlspecialchars($image['src'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($image['alt'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        <?php endforeach; ?>
    </div>
</div>
