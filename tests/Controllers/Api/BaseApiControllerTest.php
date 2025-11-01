<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../controllers/api/BaseApiController.php';

class TestableBaseApiController extends BaseApiController
{
    public function exposeRequireCustomer(): ?array
    {
        return $this->requireCustomer();
    }
}

class BaseApiControllerTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testRequireCustomerReturns401WhenNotAuthenticated(): void
    {
        $controller = new TestableBaseApiController();
        $_SESSION = [];

        $result = $controller->exposeRequireCustomer();

        $this->assertIsArray($result);
        $this->assertSame(401, $result['status']);
        $this->assertFalse($result['success']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRequireCustomerReturns403ForNonCustomerAccount(): void
    {
        $controller = new TestableBaseApiController();
        session_start();
        $_SESSION['user'] = ['id' => 1, 'group' => 1];

        $result = $controller->exposeRequireCustomer();

        $this->assertIsArray($result);
        $this->assertSame(403, $result['status']);
        $this->assertFalse($result['success']);
        $this->assertSame('forbidden', $result['error']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRequireCustomerAllowsCustomerAccounts(): void
    {
        $controller = new TestableBaseApiController();
        session_start();
        $_SESSION['user'] = ['id' => 99, 'group' => 2];

        $result = $controller->exposeRequireCustomer();

        $this->assertNull($result);
    }
}
