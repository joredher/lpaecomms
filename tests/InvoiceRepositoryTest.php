<?php
declare(strict_types=1);

loadRepo('repositories/invoice/InvoiceRepository.php');
loadRepo('repositories/ProductRepository.php');
loadRepo('repositories/client/ClientRepository.php');
require_once __DIR__ . '/RepositoryTestCase.php';

final class InvoiceRepositoryTest extends RepositoryTestCase
{
    private InvoiceRepository $invoiceRepo;
    private ProductRepository $productRepo;
    private ClientRepository $clientRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceRepo = new InvoiceRepository();
        $this->productRepo = new ProductRepository();
        $this->clientRepo = new ClientRepository();
    }

    public function testCreateInvoicesAddItemsAndUpdateStatus(): void
    {
        // Create products
        $prod1Name = 'Invoice Prod 1 ' . uniqid();
        $prod2Name = 'Invoice Prod 2 ' . uniqid();

        $this->productRepo->createProduct([
            'name' => $prod1Name,
            'price' => 10.0,
            'onhand' => 100,
        ]);
        $product1 = $this->productRepo->findWhere('lpa_stock_name', $prod1Name);

        $this->productRepo->createProduct([
            'name' => $prod2Name,
            'price' => 5.0,
            'onhand' => 50,
        ]);
        $product2 = $this->productRepo->findWhere('lpa_stock_name', $prod2Name);

        // Create clients
        $this->clientRepo->create([
            'lpa_clients_firstname' => 'Client',
            'lpa_clients_lastname' => 'One',
            'lpa_client_email' => 'client1_' . uniqid() . '@example.com',
            'lpa_client_address' => 'Addr 1',
        ]);
        $client1 = (int)$this->db->lastInsertId();

        $this->clientRepo->create([
            'lpa_clients_firstname' => 'Client',
            'lpa_clients_lastname' => 'Two',
            'lpa_client_email' => 'client2_' . uniqid() . '@example.com',
            'lpa_client_address' => 'Addr 2',
        ]);
        $client2 = (int)$this->db->lastInsertId();

        $this->clientRepo->create([
            'lpa_clients_firstname' => 'Client',
            'lpa_clients_lastname' => 'Three',
            'lpa_client_email' => 'client3_' . uniqid() . '@example.com',
            'lpa_client_address' => 'Addr 3',
        ]);
        $client3 = (int)$this->db->lastInsertId();

        // Invoice 1: Pending -> Active
        $total1 = (float)$product1['lpa_stock_price'] * 1 + (float)$product2['lpa_stock_price'] * 2;
        $inv1 = $this->invoiceRepo->createInvoice([
            'total' => $total1,
            'client_name' => 'Client One',
            'status' => 'P',
            'client_id' => $client1,
            'address' => 'Addr 1',
        ]);
        $this->assertIsInt($inv1);
        $this->invoiceRepo->addInvoiceItem([
            'invoice_id' => $inv1,
            'stock_id' => $product1['lpa_stock_ID'],
            'name' => $prod1Name,
            'quantity' => 1,
            'price' => $product1['lpa_stock_price'],
            'amount' => $product1['lpa_stock_price'] * 1,
        ]);
        $this->invoiceRepo->addInvoiceItem([
            'invoice_id' => $inv1,
            'stock_id' => $product2['lpa_stock_ID'],
            'name' => $prod2Name,
            'quantity' => 2,
            'price' => $product2['lpa_stock_price'],
            'amount' => $product2['lpa_stock_price'] * 2,
        ]);
        $invoice1 = $this->invoiceRepo->findById($inv1);
        $this->assertEquals($total1, (float)$invoice1['lpa_inv_amount']);
        $this->assertSame('P', $invoice1['lpa_inv_status']);
        $this->invoiceRepo->updateStatus($inv1, 'A');
        $updated1 = $this->invoiceRepo->findById($inv1);
        $this->assertSame('A', $updated1['lpa_inv_status']);

        $stmt = $this->db->prepare('SELECT SUM(lpa_invitem_stock_amount) total, COUNT(*) cnt FROM lpa_invoice_items WHERE lpa_fk_invoices_ID = :id');
        $stmt->execute([':id' => $inv1]);
        $summary1 = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($total1, (float)$summary1['total']);
        $this->assertEquals(2, (int)$summary1['cnt']);

        // Invoice 2: Active -> Cancelled
        $total2 = (float)$product1['lpa_stock_price'] * 3 + (float)$product2['lpa_stock_price'] * 1;
        $inv2 = $this->invoiceRepo->createInvoice([
            'total' => $total2,
            'client_name' => 'Client Two',
            'status' => 'A',
            'client_id' => $client2,
            'address' => 'Addr 2',
        ]);
        $this->assertIsInt($inv2);
        $this->invoiceRepo->addInvoiceItem([
            'invoice_id' => $inv2,
            'stock_id' => $product1['lpa_stock_ID'],
            'name' => $prod1Name,
            'quantity' => 3,
            'price' => $product1['lpa_stock_price'],
            'amount' => $product1['lpa_stock_price'] * 3,
        ]);
        $this->invoiceRepo->addInvoiceItem([
            'invoice_id' => $inv2,
            'stock_id' => $product2['lpa_stock_ID'],
            'name' => $prod2Name,
            'quantity' => 1,
            'price' => $product2['lpa_stock_price'],
            'amount' => $product2['lpa_stock_price'] * 1,
        ]);
        $invoice2 = $this->invoiceRepo->findById($inv2);
        $this->assertEquals($total2, (float)$invoice2['lpa_inv_amount']);
        $this->assertSame('A', $invoice2['lpa_inv_status']);
        $this->invoiceRepo->updateStatus($inv2, 'C');
        $updated2 = $this->invoiceRepo->findById($inv2);
        $this->assertSame('C', $updated2['lpa_inv_status']);
        $stmt->execute([':id' => $inv2]);
        $summary2 = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($total2, (float)$summary2['total']);
        $this->assertEquals(2, (int)$summary2['cnt']);

        // Invoice 3: Cancelled -> Pending
        $total3 = (float)$product1['lpa_stock_price'] * 2 + (float)$product2['lpa_stock_price'] * 3;
        $inv3 = $this->invoiceRepo->createInvoice([
            'total' => $total3,
            'client_name' => 'Client Three',
            'status' => 'C',
            'client_id' => $client3,
            'address' => 'Addr 3',
        ]);
        $this->assertIsInt($inv3);
        $this->invoiceRepo->addInvoiceItem([
            'invoice_id' => $inv3,
            'stock_id' => $product1['lpa_stock_ID'],
            'name' => $prod1Name,
            'quantity' => 2,
            'price' => $product1['lpa_stock_price'],
            'amount' => $product1['lpa_stock_price'] * 2,
        ]);
        $this->invoiceRepo->addInvoiceItem([
            'invoice_id' => $inv3,
            'stock_id' => $product2['lpa_stock_ID'],
            'name' => $prod2Name,
            'quantity' => 3,
            'price' => $product2['lpa_stock_price'],
            'amount' => $product2['lpa_stock_price'] * 3,
        ]);
        $invoice3 = $this->invoiceRepo->findById($inv3);
        $this->assertEquals($total3, (float)$invoice3['lpa_inv_amount']);
        $this->assertSame('C', $invoice3['lpa_inv_status']);
        $this->invoiceRepo->updateStatus($inv3, 'P');
        $updated3 = $this->invoiceRepo->findById($inv3);
        $this->assertSame('P', $updated3['lpa_inv_status']);
        $stmt->execute([':id' => $inv3]);
        $summary3 = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($total3, (float)$summary3['total']);
        $this->assertEquals(2, (int)$summary3['cnt']);
    }
}
