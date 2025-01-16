<?php

namespace Paylabs\Payment\Model\Order;

use Exception;
use Paylabs\Payment\Logger\PaylabsLogger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;

/**
 * Class InvoiceRepository
 *
 * Handles the creation and management of invoices for Paylabs Payment orders.
 * Provides functionality to generate invoices and save them to the Magento system.
 *
 * @package Paylabs\Payment\Model\Order
 */
class InvoiceRepository
{
    /**
     * @var OrderRepository
     * Repository for managing Paylabs Payment orders.
     */
    protected OrderRepository $paylabsOrderRepository;

    /**
     * @var PaylabsLogger
     * Logger instance for logging Paylabs Payment-specific operations.
     */
    private PaylabsLogger $logger;

    /**
     * @var Invoice
     * Model instance for working with Magento invoices.
     */
    protected Invoice $invoice;

    /**
     * @var InvoiceService
     * Service class for preparing and managing invoices.
     */
    protected InvoiceService $invoiceService;

    /**
     * @var InvoiceRepositoryInterface
     * Magento's invoice repository interface for saving invoice data.
     */
    private InvoiceRepositoryInterface $magentoInvoiceRepository;

    /**
     * @var MessageManagerInterface
     * Interface for managing system messages displayed to the user.
     */
    protected MessageManagerInterface $messageManager;

    private InvoiceSender $invoiceSender;


    /**
     * InvoiceRepository constructor.
     *
     * @param OrderRepository $paylabsOrderRepository Repository for managing Paylabs Payment orders.
     * @param PaylabsLogger $logger Logger instance for debugging and error tracking.
     * @param Invoice $invoice Invoice model for handling invoice operations.
     * @param InvoiceService $invoiceService Service class for preparing invoices.
     * @param InvoiceRepositoryInterface $magentoInvoiceRepository Interface for saving invoices.
     * @param MessageManagerInterface $messageManager Manager for displaying messages in the Magento admin.
     */
    public function __construct(
        OrderRepository $paylabsOrderRepository,
        PaylabsLogger $logger,
        Invoice $invoice,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        InvoiceRepositoryInterface $magentoInvoiceRepository,
        MessageManagerInterface $messageManager
    ) {
        $this->paylabsOrderRepository = $paylabsOrderRepository;
        $this->logger = $logger;
        $this->invoice = $invoice;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->magentoInvoiceRepository = $magentoInvoiceRepository;
        $this->messageManager = $messageManager;
    }

    /**
     * Create and save an invoice for a given order.
     *
     * This method handles the invoice generation process for a Magento order.
     * It checks if the order allows invoice creation, prepares the invoice,
     * and saves it to the Magento system. If an error occurs, it logs the
     * error and displays an error message to the admin user.
     *
     * @param Order $order The Magento order for which to create an invoice.
     * @return void
     */
    public function createInvoice(Order $order, $data): void
    {
        try {
            // Check if the order exists
            if ($order->isEmpty()) {
                throw new LocalizedException(__("The order no longer exists."));
            }

            // Check if the order allows an invoice to be created
            if (!$order->canInvoice()) {
                throw new LocalizedException(__("The order does not allow an invoice to be created."));
            }

            // Prepare the invoice
            $invoice = $this->invoiceService->prepareInvoice($order);
            if (!$invoice) {
                throw new LocalizedException(__("Unable to prepare the invoice."));
            }

            // Ensure the invoice has products
            if (!$invoice->getTotalQty()) {
                throw new LocalizedException(__("You can't create an invoice without products."));
            }

            // Set transaction details if available
            if ($order->getExtOrderId()) {
                $invoice->setTransactionId($data['id']);
                $order->getPayment()->setLastTransId($order->getExtOrderId());
            }

            // Register and save the invoice
            $invoice->register();
            $invoice->setSendEmail(true);
            $invoice->getOrder()->setCustomerNoteNotify(true);
            $invoice->setState(Invoice::STATE_PAID);

            // Save the invoice and order
            $this->magentoInvoiceRepository->save($invoice);

            // Notify the customer
            $this->invoiceSender->send($invoice);
            $this->paylabsOrderRepository->saveOrder($order);
        } catch (LocalizedException $e) {
            // Handle Magento-specific exceptions
            $this->logger->logDebug("Invoice creation error: " . $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            // Handle generic exceptions
            $this->logger->logErrorException("Unexpected error during invoice creation: " . $e->getMessage(), $e);
            $this->messageManager->addErrorMessage(__("An error occurred while creating the invoice."));
        }
    }
}
