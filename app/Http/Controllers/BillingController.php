<?php

namespace App\Http\Controllers;

use App\Services\TelnyxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    protected $telnyxService;

    public function __construct(TelnyxService $telnyxService)
    {
        $this->telnyxService = $telnyxService;
    }

    /**
     * Display billing dashboard
     */
    public function index(): Response
    {
        try {
            // Get balance
            $balanceData = $this->telnyxService->getBalance();
            $balance = $balanceData ? ($balanceData['data'] ?? null) : null;

            // Get recent invoices (last 10)
            $invoicesData = $this->telnyxService->listInvoices(1, 10);
            $invoices = $invoicesData ? ($invoicesData['data'] ?? []) : [];

            // Get billing groups
            $billingGroupsData = $this->telnyxService->listBillingGroups(1, 25);
            $billingGroups = $billingGroupsData ? ($billingGroupsData['data'] ?? []) : [];

            // Get payment methods
            $paymentMethodsData = $this->telnyxService->getPaymentMethods();
            $paymentMethods = $paymentMethodsData ? ($paymentMethodsData['data'] ?? []) : [];

            return Inertia::render('Billing/Index', [
                'balance' => $balance,
                'invoices' => $invoices,
                'billingGroups' => $billingGroups,
                'paymentMethods' => $paymentMethods,
            ]);
        } catch (\Exception $e) {
            Log::error('Billing Dashboard Error: ' . $e->getMessage());
            
            return Inertia::render('Billing/Index', [
                'balance' => null,
                'invoices' => [],
                'billingGroups' => [],
                'paymentMethods' => [],
                'error' => 'Failed to load billing information'
            ]);
        }
    }

    /**
     * Display invoices page
     */
    public function invoices(Request $request): Response
    {
        try {
            $pageNumber = $request->input('page', 1);
            $filters = $request->only(['status', 'start_date', 'end_date']);

            $invoicesData = $this->telnyxService->listInvoices($pageNumber, 25, $filters);
            
            return Inertia::render('Billing/Invoices', [
                'invoices' => $invoicesData ? ($invoicesData['data'] ?? []) : [],
                'meta' => $invoicesData ? ($invoicesData['meta'] ?? null) : null,
                'filters' => $filters,
            ]);
        } catch (\Exception $e) {
            Log::error('Invoices List Error: ' . $e->getMessage());
            
            return Inertia::render('Billing/Invoices', [
                'invoices' => [],
                'meta' => null,
                'error' => 'Failed to load invoices'
            ]);
        }
    }

    /**
     * Get account balance as JSON
     */
    public function getBalance()
    {
        try {
            $balanceData = $this->telnyxService->getBalance();
            
            if ($balanceData) {
                return response()->json($balanceData);
            }

            return response()->json([
                'error' => 'Failed to retrieve balance'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Get Balance Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to retrieve balance'
            ], 500);
        }
    }

    /**
     * Get invoices as JSON
     */
    public function listInvoices(Request $request)
    {
        try {
            $pageNumber = $request->input('page', 1);
            $pageSize = $request->input('per_page', 25);
            $filters = $request->only(['status', 'start_date', 'end_date']);

            $invoicesData = $this->telnyxService->listInvoices($pageNumber, $pageSize, $filters);
            
            if ($invoicesData) {
                return response()->json($invoicesData);
            }

            return response()->json([
                'error' => 'Failed to retrieve invoices'
            ], 500);
        } catch (\Exception $e) {
            Log::error('List Invoices Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to retrieve invoices'
            ], 500);
        }
    }

    /**
     * Get specific invoice details
     */
    public function getInvoice($invoiceId)
    {
        try {
            $invoiceData = $this->telnyxService->getInvoice($invoiceId);
            
            if ($invoiceData) {
                return response()->json($invoiceData);
            }

            return response()->json([
                'error' => 'Invoice not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Get Invoice Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to retrieve invoice'
            ], 500);
        }
    }

    /**
     * Download invoice PDF
     */
    public function downloadInvoice($invoiceId)
    {
        try {
            $pdfContent = $this->telnyxService->downloadInvoicePdf($invoiceId);
            
            if ($pdfContent) {
                return response($pdfContent, 200)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="invoice-' . $invoiceId . '.pdf"');
            }

            return response()->json([
                'error' => 'Failed to download invoice'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Download Invoice Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to download invoice'
            ], 500);
        }
    }

    /**
     * Get billing groups as JSON
     */
    public function getBillingGroups(Request $request)
    {
        try {
            $pageNumber = $request->input('page', 1);
            $pageSize = $request->input('per_page', 25);

            $billingGroupsData = $this->telnyxService->listBillingGroups($pageNumber, $pageSize);
            
            if ($billingGroupsData) {
                return response()->json($billingGroupsData);
            }

            return response()->json([
                'error' => 'Failed to retrieve billing groups'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Get Billing Groups Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to retrieve billing groups'
            ], 500);
        }
    }

    /**
     * Get usage reports
     */
    public function getUsageReports(Request $request)
    {
        try {
            $filters = $request->only(['start_date', 'end_date', 'product', 'page_size']);

            $usageData = $this->telnyxService->getUsageReports($filters);
            
            if ($usageData) {
                return response()->json($usageData);
            }

            return response()->json([
                'error' => 'Failed to retrieve usage reports'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Get Usage Reports Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to retrieve usage reports'
            ], 500);
        }
    }

    /**
     * Display usage reports page
     */
    public function usage(Request $request): Response
    {
        try {
            // Default to last 30 days
            $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));
            
            $filters = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'page_size' => 100
            ];

            if ($request->has('product')) {
                $filters['product'] = $request->input('product');
            }

            $usageData = $this->telnyxService->getUsageReports($filters);
            
            return Inertia::render('Billing/Usage', [
                'usage' => $usageData ? ($usageData['data'] ?? []) : [],
                'meta' => $usageData ? ($usageData['meta'] ?? null) : null,
                'filters' => $filters,
            ]);
        } catch (\Exception $e) {
            Log::error('Usage Reports Error: ' . $e->getMessage());
            
            return Inertia::render('Billing/Usage', [
                'usage' => [],
                'meta' => null,
                'error' => 'Failed to load usage reports'
            ]);
        }
    }

    /**
     * Get payment methods
     */
    public function getPaymentMethods()
    {
        try {
            $paymentMethodsData = $this->telnyxService->getPaymentMethods();
            
            if ($paymentMethodsData) {
                return response()->json($paymentMethodsData);
            }

            return response()->json([
                'error' => 'Failed to retrieve payment methods'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Get Payment Methods Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to retrieve payment methods'
            ], 500);
        }
    }
}

