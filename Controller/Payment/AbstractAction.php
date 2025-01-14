<?php

namespace Paylabs\Payment\Controller\Payment;

use Paylabs\Payment\Model\Order\CreditMemoRepository;
use Paylabs\Payment\Logger\PaylabsLogger;
use Paylabs\Payment\Model\Config\Payment\ModuleConfig;
use Paylabs\Payment\Model\Order\InvoiceRepository;
use Paylabs\Payment\Model\Order\OrderRepository;
use Paylabs\Payment\Model\Payment\RequestFactory;
// use Paylabs\Payment\Service\PaylabsService;
use Paylabs\Payment\Model\PaylabsService;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface as ActionApp;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Request\Http;

/**
 * AbstractAction class for handling payment-related actions that implement ActionApp Magento in the Paylabs Payment module.
 *
 * This abstract class serves as the base for payment-related controllers in the Paylabs Payment module.
 * It provides common dependencies, initialization, and utility methods to simplify specific actions.
 *
 * @package Paylabs\Payment\Controller\Payment
 */
abstract class AbstractAction implements ActionApp
{
    /**
     * @var PaylabsLogger Logger instance for logging actions, requests, and errors.
     */
    public PaylabsLogger $logger;

    /**
     * @var PaylabsService Service class for handling payment creation and Paylabs API communication.
     */
    public PaylabsService $paylabsService;

    /**
     * @var OrderRepository Repository class for managing order data.
     */
    public OrderRepository $orderRepository;

    /**
     * @var InvoiceRepository Repository for managing Magento invoices.
     */
    public InvoiceRepository $invoiceRepository;

    /**
     * @var CreditMemoRepository Repository for managing credit memo data.
     */
    public CreditMemoRepository $creditMemoRepository;

    /**
     * @var Session Payment session for managing cart and order session data.
     */
    protected Session $_checkoutSession;

    /**
     * @var Context Action context for accessing controller parameters and settings.
     */
    private Context $_context;

    /**
     * @var JsonFactory Factory for creating JSON response results.
     */
    public JsonFactory $_resultJsonFactory;

    /**
     * @var Json Serializer for encoding and decoding JSON data.
     */
    public Json $jsonSerializer;

    /**
     * @var ResultFactory Factory for generating various result types.
     */
    protected ResultFactory $_resultFactory;

    /**
     * @var ModuleConfig Configuration class to retrieve module-specific settings.
     */
    public ModuleConfig $paylabsModuleConfig;

    /**
     * @var RequestFactory Factory for creating payment request payloads.
     */
    protected RequestFactory $requestFactory;

    /**
     * @var RequestInterface Interface for managing HTTP requests.
     */
    public RequestInterface $requestInterface;

    /**
     * @var RedirectFactory Factory for generating redirect results.
     */
    public RedirectFactory $redirectFactory;

    /**
     * @var PageFactory Factory for creating page results.
     */
    public PageFactory $pageFactory;

    /**
     * @var Http HTTP request object for handling incoming requests.
     */
    protected Http $request;

    /**
     * Constructor for AbstractAction class.
     *
     * @param Context $context The context for the action, containing request and response objects.
     * @param Session $checkoutSession Session object for managing the checkout process.
     * @param JsonFactory $resultJsonFactory Factory for generating JSON responses.
     * @param PaylabsService $paylabsService Service for interacting with Paylabs API.
     * @param PaylabsLogger $logger Logger instance for recording events and errors.
     * @param ModuleConfig $paylabsModuleConfig Config class for Paylabs Payment module settings.
     * @param OrderRepository $orderRepository Repository for managing orders.
     * @param InvoiceRepository $invoiceRepository Repository for managing invoices.
     * @param CreditMemoRepository $creditMemoRepository Repository for managing credit memos.
     * @param RequestFactory $requestFactory Factory for generating payment requests.
     * @param PageFactory $pageFactory Factory for creating view pages.
     * @param Http $request HTTP request object for retrieving request data.
     * @param Json $jsonSerializer JSON serializer for encoding and decoding data.
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        JsonFactory $resultJsonFactory,
        PaylabsService $paylabsService,
        PaylabsLogger $logger,
        ModuleConfig $paylabsModuleConfig,
        OrderRepository $orderRepository,
        InvoiceRepository $invoiceRepository,
        CreditMemoRepository $creditMemoRepository,
        RequestFactory $requestFactory,
        PageFactory $pageFactory,
        Http $request,
        Json $jsonSerializer
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_context = $context;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_resultFactory = $context->getResultFactory();
        $this->paylabsService = $paylabsService;
        $this->logger = $logger;
        $this->paylabsModuleConfig = $paylabsModuleConfig;
        $this->orderRepository = $orderRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->creditMemoRepository = $creditMemoRepository;
        $this->requestFactory = $requestFactory;
        $this->pageFactory = $pageFactory;
        $this->requestInterface = $context->getRequest();
        $this->redirectFactory = $context->getResultRedirectFactory();
        $this->request = $request;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Get the HTTP request object.
     *
     * @return Http The HTTP request object.
     */
    public function getRequest(): Http
    {
        return $this->request;
    }
}
