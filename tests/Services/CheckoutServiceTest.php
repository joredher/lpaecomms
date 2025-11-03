<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../services/CheckoutService.php';

class CheckoutServiceTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testProcessReturnsValidationErrorWhenAddressCacheMissing(): void
    {
        $clientRepo = $this->createMock(ClientRepository::class);
        $clientRepo->method('findIfTheAddressValid')
            ->willReturn([
                'data' => null,
                'isValid' => false,
                'missing' => true,
            ]);

        $invoiceRepo = $this->createMock(InvoiceRepository::class);
        $workflow = $this->createMock(InvoiceWorkflow::class);
        $addressService = $this->createMock(AddressService::class);

        $service = new CheckoutService($clientRepo, $invoiceRepo, $workflow, $addressService);

        $_SESSION['user'] = ['id' => 7];
        $_SESSION['cart'] = [['product_id' => 1, 'quantity' => 1]];

        $result = $service->process([
            'firstname' => 'Jane',
            'street' => '123 Sample St',
            'city' => 'Sydney',
            'phone' => '0123456789',
            'email' => 'jane@example.com',
            'payment_method' => 'cod',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(422, $result['status']);
        $this->assertArrayHasKey('code', $result);
        $this->assertSame(CheckoutService::ERROR_ADDRESS_CACHE_MISSING, $result['code']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('address', $result['errors']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testProcessSucceedsWhenAddressCached(): void
    {
        $clientRepo = $this->createMock(ClientRepository::class);
        $clientRepo->method('findIfTheAddressValid')
            ->willReturn([
                'data' => [
                    'lpa_full_address' => '123 Sample St, Sydney',
                    'lpa_fk_client_ID' => 55,
                ],
                'isValid' => true,
                'missing' => false,
            ]);

        $invoiceRepo = $this->createMock(InvoiceRepository::class);
        $invoiceRepo->method('getInvoiceWithItems')
            ->willReturn(null);

        $workflow = $this->createMock(InvoiceWorkflow::class);
        $workflow->method('handle')
            ->willReturn(321);

        $addressService = $this->createMock(AddressService::class);

        $service = new CheckoutService($clientRepo, $invoiceRepo, $workflow, $addressService);

        $_SESSION['user'] = ['id' => 7];
        $_SESSION['cart'] = [['product_id' => 1, 'quantity' => 1]];
        $_SESSION['total'] = 99.95;

        $result = $service->process([
            'firstname' => 'Jane',
            'street' => '123 Sample St',
            'city' => 'Sydney',
            'phone' => '0123456789',
            'email' => 'jane@example.com',
            'payment_method' => 'cod',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(201, $result['status']);
        $this->assertSame(321, $result['data']['order_id']);
        $this->assertSame(321, $_SESSION['last_invoice_id']);
        $this->assertArrayNotHasKey('cart', $_SESSION);
    }

    /**
     * @runInSeparateProcess
     */
    public function testProcessCreatesAddressWhenMissingCacheAndIdProvided(): void
    {
        $clientRepo = $this->createMock(ClientRepository::class);
        $clientRepo->method('findIfTheAddressValid')
            ->willReturn([
                'data' => null,
                'isValid' => false,
                'missing' => true,
            ]);
        $clientRepo->method('createFromBillingForm')
            ->willReturn(88);
        $clientRepo->expects($this->once())
            ->method('addLpaUserClientAddressValid')
            ->with($this->arrayHasKey('lpa_fk_client_ID'));

        $invoiceRepo = $this->createMock(InvoiceRepository::class);
        $invoiceRepo->method('getInvoiceWithItems')
            ->willReturn(null);

        $workflow = $this->createMock(InvoiceWorkflow::class);
        $workflow->method('handle')
            ->willReturn(444);

        $addressService = $this->createMock(AddressService::class);
        $addressService->method('getStructuredAddress')
            ->with('PID123')
            ->willReturn([
                'sla' => '10 Test St Sydney NSW 2000',
                'streetNumberFrom' => '10',
                'streetNumberTo' => null,
                'streetName' => 'Test',
                'streetType' => 'St',
                'suburb' => 'Sydney',
                'typeApt' => '',
                'unitNumber' => '',
                'state' => 'NSW',
                'postcode' => '2000',
            ]);

        $service = new CheckoutService($clientRepo, $invoiceRepo, $workflow, $addressService);

        $_SESSION['user'] = ['id' => 9];
        $_SESSION['cart'] = [
            1 => ['name' => 'Test', 'price' => 15.0, 'quantity' => 1],
        ];
        $_SESSION['total'] = 15.0;

        $result = $service->process([
            'firstname' => 'Sam',
            'street' => '10 Test St',
            'city' => 'Sydney',
            'phone' => '0123456789',
            'email' => 'sam@example.com',
            'payment_method' => 'cod',
            'address-id' => 'PID123',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(201, $result['status']);
        $this->assertSame(444, $result['data']['order_id']);
        $this->assertSame(444, $_SESSION['last_invoice_id']);
    }
}
