<?php

namespace Twetech\Nestogy\Core;

use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Database;
use NumberFormatter;

/**
 * Router Class
 * 
 * Handles routing and dispatching of requests to appropriate controllers
 * in the Nestogy application.
 */
class Router {
    private $routes = [];
    private $apiRoutes = [];
    private $defaultPage = 'dashboard';
    private $currency_format;
    private $pdo;

    /**
     * Router constructor.
     * 
     * Initializes the router with database connection and registers routes.
     */
    public function __construct($domain)
    {
        $config = require '/var/www/portal.twe.tech/config/' . $domain . '/config.php';
        $database = new Database($config['db']);
        $this->pdo = $database->getConnection();
        $this->currency_format = numfmt_create($config['locale'], NumberFormatter::CURRENCY);
        $GLOBALS['currency_format'] = $this->currency_format;
        $this->registerRoutes();
        $this->registerApiRoutes();
    }

    /**
     * Register all application routes.
     * 
     * @return void
     */
    public function registerRoutes() {
        //Blank route for Debugging
        $this->add('debug', 'DebugController', 'index');

        // Dashboard routes
        $this->add('dashboard', 'DashboardController', 'index', ['month', 'year']);

        // Client routes
        $this->add('clients', 'ClientController', 'index');
        $this->add('client', 'ClientController', 'show', ['client_id']);
        $this->add('contact', 'ClientController', 'showContacts', ['client_id']);
        $this->add('location', 'ClientController', 'showLocations', ['client_id']);

        // Support routes
        $this->add('tickets', 'SupportController', 'index', ['client_id', 'status', 'user_id', 'ticket_type']);
        $this->add('ticket', 'SupportController', 'show', ['ticket_id']);
        $this->add('inventory', 'InventoryController', 'index', ['location_id']);
        $this->add('projects', 'ProjectController', 'index', ['client_id']);
        $this->add('project', 'ProjectController', 'show', ['project_id']);

        // Documentation routes
        $this->add('documentations', 'DocumentationController', 'index');
        $this->add('documentation', 'DocumentationController', 'show', ['documentation_type', 'client_id']);

        // Trip routes
        $this->add('trips', 'TripController', 'index', ['client_id']);
        $this->add('trip', 'TripController', 'show', ['trip_id']);

        // Accounting routes
        $this->add('invoices', 'AccountingController', 'showInvoices', ['client_id']);
        $this->add('invoice', 'AccountingController', 'showInvoice', ['invoice_id']);
        $this->add('subscriptions','AccountingController','showSubscriptions',['client_id']);
        $this->add('subscription','AccountingController','showSubscription',['subscription_id']);
        $this->add('payments', 'AccountingController', 'showPayments', ['client_id']);
        $this->add('payment', 'AccountingController', 'showPayment', ['payment_reference']);
        $this->add('make_payment', 'AccountingController', 'makePayment', ['bank_transaction_id']);
        $this->add('quotes', 'AccountingController', 'showQuotes', ['client_id']);
        $this->add('quote', 'AccountingController', 'showQuote', ['quote_id']);
        $this->add('contracts', 'AccountingController', 'showContracts', ['client_id']);
        $this->add('contract', 'AccountingController', 'showContract', ['contract_id']);
        $this->add('products', 'AccountingController', 'showProducts');
        $this->add('product', 'AccountingController', 'showProduct', ['product_id']);
        $this->add('statement', 'AccountingController', 'showStatement', ['client_id']);
        $this->add('accounts', 'AccountingController', 'showAccounts');
        $this->add('account', 'AccountingController', 'showAccount', ['account_id']);
        $this->add('expenses', 'AccountingController', 'showExpenses', ['client_id']);
        $this->add('expense', 'AccountingController', 'showExpense', ['expense_id']);
        $this->add('bank_transactions', 'AccountingController', 'showBankTransactions', ['client_id']);
        
        // Reports routes
        $this->add('report', 'ReportsController', 'index', ['report']);

        // Account Management routes
        $this->add('aging_invoices', 'AccountManagementController', 'agingInvoices');
        $this->add('closed_tickets', 'AccountManagementController', 'closedTickets');
        $this->add('aging_tickets', 'AccountManagementController', 'agingTickets');
        $this->add('clients_without_login', 'AccountManagementController', 'clientsWithoutLogin');
        $this->add('clients_without_subscription', 'AccountManagementController', 'clientsWithoutSubscription');
        $this->add('sales_pipeline', 'AccountManagementController', 'salesPipeline');

        // Administration routes
        $this->add('admin', 'AdministrationController', 'index', ['admin_page', 'sent']);

        // Human Resources routes
        $this->add('hr', 'HumanResourcesController', 'index', ['hr_page', 'pay_period', 'employee_id']);

        // Course route
        $this->add('learn', 'CourseController', 'index', ['course_id']);

        // SOP route
        $this->add('sop', 'SOPController', 'index', ['id', 'version']);
    }

    /**
     * Register all API routes.
     * 
     * @return void
     */
    private function registerApiRoutes() {
        // Invoice actions
        $this->addApi('email_invoice', 'AccountingController', 'emailInvoice');
        $this->addApi('cancel_invoice', 'AccountingController', 'cancelInvoice');
        $this->addApi('delete_invoice', 'AccountingController', 'deleteInvoice');
        $this->addApi('mark_invoice_sent', 'AccountingController', 'markInvoiceSent');
        
        // Project actions
        $this->addApi('project_note_add', 'ProjectController', 'addNote');
        
        // Ticket actions
        $this->addApi('delete_ticket', 'SupportController', 'deleteTicket');
        
        // Authentication actions
        $this->addApi('logout', 'AuthController', 'logout');
    }

    /**
     * Add a new route to the application.
     * 
     * @param string $route      The route identifier
     * @param string $controller The controller class name
     * @param string $action     The controller action method
     * @param array  $middlewares Optional middleware parameters
     * @return void
     */
    public function add($route, $controller, $action, $middlewares = []) {
        $this->routes[$route] = [
            'controller' => $controller,
            'action' => $action,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Add a new API endpoint.
     * 
     * @param string $endpoint      The API endpoint identifier
     * @param string $controller    The controller class name
     * @param string $method        The controller method to call
     * @param string $requestMethod The HTTP request method (default: 'POST')
     * @return void
     */
    public function addApi($endpoint, $controller, $method, $requestMethod = 'POST') {
        $this->apiRoutes[$endpoint] = [
            'controller' => $controller,
            'method' => $method,
            'requestMethod' => $requestMethod
        ];
    }

    /**
     * Dispatch the current request to the appropriate controller.
     * 
     * @param string $domain The domain to use for the request
     * @return void
     */
    public function dispatch()
    {

        // Get the page from the URL
        $page = $_GET['page'] ?? $this->defaultPage;
        $route = $this->routes[$page] ?? null;

        

        // If the page is not found, handle the error
        if (!$route) {
            $this->handleNotFound();
            return;
        }

        // Get the controller and action from the route
        $controller = "Twetech\\Nestogy\\Controller\\" . $route['controller'];
        $action = $route['action'];
        $params = $this->getParams($route['middlewares']);

        // If the user is not logged in and the page is not the login page, redirect to the login page
        if (!Auth::check() && $page !== 'login') {
            header('Location: login.php');
            exit;
        }

        // If the controller and action exist, call them
        if (class_exists($controller) && method_exists($controller, $action)) {
            $controllerInstance = new $controller($this->pdo);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $postData = $this->sanitizePostData($_POST);
                call_user_func_array([$controllerInstance, $action], array_merge($params, [$postData]));
            } else {
                call_user_func_array([$controllerInstance, $action], $params);
            }
        } else {
            $this->handleNotFound();
        }
    }

    /**
     * Sanitize POST data to prevent XSS attacks.
     * 
     * @param array $data The POST data to sanitize
     * @return array Sanitized data
     */
    private function sanitizePostData($data)
    {
        $sanitizedData = [];
        foreach ($data as $key => $value) {
            $sanitizedData[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        return $sanitizedData;
    }

    /**
     * Get parameters from URL based on middleware requirements.
     * 
     * @param array $middlewares List of required parameters
     * @return array List of parameter values
     */
    private function getParams($middlewares)
    {
        $params = [];
        foreach ($middlewares as $param) {
            if (isset($_GET[$param])) {
                $params[] = htmlspecialchars($_GET[$param], ENT_QUOTES, 'UTF-8');
            } else {
                $params[] = null; // or handle missing parameters as needed
            }
        }
        return $params;
    }

    /**
     * Handle 404 Not Found errors.
     * 
     * @return void
     */
    private function handleNotFound()
    {

        $view = new \Twetech\Nestogy\View\View();
        $messages = [
            "Well, this is awkward. The page you're looking for ran away with the circus. Try searching for something else or double-check that URL!",
            "Oh no! The page you're looking for is on vacation. Try searching for something else or double-check that URL!",
            "Oh dear! The page you're looking for must be taking a nap. Try searching for something else or double-check that URL!",
            "Oh snap! The page you're looking for is on a coffee break. Try searching for something else or double-check that URL!",
            "Oh my! The page you're looking for must be in a meeting. Try searching for something else or double-check that URL!",
            "Oh brother! The page you're looking for is at the gym. Try searching for something else or double-check that URL!",
            "Yee Yee, the page you're looking for is at the rodeo. Try searching for something else or double-check that URL!"
        ];
        $message = $messages[array_rand($messages)];
        $view->error([
            'title' => 'Oops! Page not found',
            'message' => $message
        ]);
    }

    /**
     * Handle API requests and return JSON responses.
     * 
     * @return void
     * @throws \Exception When request method is invalid
     */
    private function handleApiRequest() {
        $endpoint = $_GET['endpoint'] ?? null;
        $route = $this->apiRoutes[$endpoint] ?? null;

        if (!$route) {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            exit;
        }

        try {
            $controller = "Twetech\\Nestogy\\Controller\\" . $route['controller'];
            $method = $route['method'];
            
            if ($_SERVER['REQUEST_METHOD'] !== $route['requestMethod']) {
                throw new \Exception('Invalid request method');
            }

            $controllerInstance = new $controller($this->pdo);
            $requestData = json_decode(file_get_contents('php://input'), true) ?? [];
            $result = $controllerInstance->$method($requestData);
            
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}