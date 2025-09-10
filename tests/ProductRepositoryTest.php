<?php
declare(strict_types=1);

loadRepo('repositories/ProductRepository.php');
require_once __DIR__ . '/RepositoryTestCase.php';

final class ProductRepositoryTest extends RepositoryTestCase
{
    private ProductRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ProductRepository();
    }

    public function testCreateProductsWithVariedInventoryAndStatuses(): void
    {
        $zeroName = 'Zero stock ' . uniqid();
        $lowName = 'Low stock ' . uniqid();
        $highName = 'High stock ' . uniqid();

        $this->repo->createProduct([
            'name' => $zeroName,
            'onhand' => 0,
            'status' => 'P',
        ]);
        $this->repo->createProduct([
            'name' => $lowName,
            'onhand' => 5,
            'status' => 'S',
            'publish_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
        ]);
        $this->repo->createProduct([
            'name' => $highName,
            'onhand' => 100,
            'status' => 'U',
        ]);

        $zero = $this->repo->findWhere('lpa_stock_name', $zeroName);
        $this->assertSame('0', $zero['lpa_stock_onhand']);
        $this->assertSame('P', $zero['lpa_stock_status']);

        $low = $this->repo->findWhere('lpa_stock_name', $lowName);
        $this->assertSame('5', $low['lpa_stock_onhand']);
        $this->assertSame('S', $low['lpa_stock_status']);

        $high = $this->repo->findWhere('lpa_stock_name', $highName);
        $this->assertSame('100', $high['lpa_stock_onhand']);
        $this->assertSame('U', $high['lpa_stock_status']);

        $unpublished = $this->repo->findPaginated(0, 50, '', 'U');
        $names = array_column($unpublished, 'lpa_stock_name');
        $this->assertContains($highName, $names);
    }

    public function testCreateProductWithoutOptionalFields(): void
    {
        $name = 'Minimal product ' . uniqid();
        $this->repo->createProduct([
            'name' => $name,
        ]);

        $prod = $this->repo->findWhere('lpa_stock_name', $name);
        $this->assertNull($prod['lpa_stock_desc']);
        $this->assertNull($prod['lpa_stock_features']);
        $this->assertNull($prod['lpa_stock_onhand']);
        $this->assertEquals('0.00', $prod['lpa_stock_price']);
        $this->assertNull($prod['lpa_stock_image']);
        $this->assertSame('P', $prod['lpa_stock_status']);
        $this->assertNull($prod['lpa_stock_publish_at']);
        $this->assertEquals(0, $prod['lpa_fk_category_ID']);
        $this->assertEquals(0, $prod['lpa_fk_type_ID']);
    }
}
