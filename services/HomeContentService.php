<?php

require_once __DIR__ . '/../includes/config.php';

class HomeContentService
{
    private const BODY_IMAGE_BASE_PATH = '../assets/images/body_page/';

    public function getHero(): array
    {
        return [
            'title' => 'Explore Logic Peripherals AU',
            'stats' => [
                ['value' => '50+', 'label' => 'Peripherals Parts'],
                ['value' => '100+', 'label' => 'Customers'],
            ],
            'search' => [
                'action' => '',
                'placeholder' => 'What are you looking for?',
                'icon' => $this->asset('Search.svg'),
            ],
            'images' => [
                'hero' => $this->asset('image.png'),
                'vector' => $this->asset('Vector 186.svg'),
                'vector_overlay' => $this->asset('Vector 187.svg'),
            ],
        ];
    }

    public function getFeatureBlurbs(): array
    {
        return [
            [
                'icon' => $this->asset('Bulb1.svg'),
                'title' => 'Large Assortment',
                'description' => 'we offer many different types of products with fewer variations in each category.',
            ],
            [
                'icon' => $this->asset('Box1.svg'),
                'title' => 'Fast & Free Shipping',
                'description' => '4-day or less delivery time, free shipping and an expedited delivery option.',
            ],
            [
                'icon' => $this->asset('TelephoneOutbound1.svg'),
                'title' => '24/7 Support',
                'description' => 'answers to any business related inquiry 24/7 and in real-time.',
            ],
        ];
    }

    public function getFeatureImages(): array
    {
        return [
            ['src' => $this->asset('assortment.png'), 'alt' => 'Large Assortment'],
            ['src' => $this->asset('fast_free_shipping.png'), 'alt' => 'Fast & Free Shipping'],
            ['src' => $this->asset('24_7_support.png'), 'alt' => '24/7 Support'],
            ['src' => $this->asset('proudly_australian.png'), 'alt' => 'Proudly Australian'],
        ];
    }

    public function getHomeContent(): array
    {
        return [
            'hero' => $this->getHero(),
            'featureBlurbs' => $this->getFeatureBlurbs(),
            'featureImages' => $this->getFeatureImages(),
        ];
    }

    private function asset(string $file): string
    {
        return self::BODY_IMAGE_BASE_PATH . ltrim($file, '/');
    }
}
