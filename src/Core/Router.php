<?php

namespace Twetech\Nestogy\Core;

use Twetech\Nestogy\Auth\Auth;

class Router
{
    private $routes = [];
    private $middlewares = [];
    private $defaultController = 'HomeController';
    private $defaultAction = 'index';

    public function __construct()
    {
        $this->registerRoutes();
    }

    public function add($route, $controller, $action, $middlewares = [])
    {
        $this->routes[$route] = [
            'controller' => $controller ?: $this->defaultController,
            'action' => $action ?: $this->defaultAction,
            'middlewares' => $middlewares
        ];
    }

    public function registerRoutes()
    {
        // Example of route registration
        $this->add('home', 'HomeController', 'index');
        $this->add('client', 'ClientController', 'index');
        $this->add('client/show', 'ClientController', 'show', ['client_id']);
        $this->add('ticket', 'SupportController', 'index');
        $this->add('ticket/show', 'SupportController', 'show', ['ticket_id']);
        $this->add('contact', 'ClientController', 'showContacts', ['client_id']);
        $this->add('location', 'ClientController', 'showLocations', ['client_id']);
        $this->add('documentation', 'DocumentationController', 'index');
        $this->add('documentation/show', 'DocumentationController', 'show', ['documentation_type', 'client_id']);
    }

    public function dispatch()
    {
        $page = $_GET['page'] ?? 'home';
        $route = $this->routes[$page] ?? null;

        if (!$route) {
            $this->handleNotFound();
            return;
        }

        $controller = "Twetech\\Nestogy\\Controller\\" . $route['controller'];
        $action = $route['action'];
        $params = $this->getParams($route['middlewares']);

        if (!Auth::check() && $page !== 'login') {
            header('Location: login.php');
            exit;
        }

        if (class_exists($controller) && method_exists($controller, $action)) {
            $controllerInstance = new $controller();
            call_user_func_array([$controllerInstance, $action], $params);
        } else {
            $this->handleNotFound();
        }
    }

    private function getParams($middlewares)
    {
        $params = [];
        foreach ($middlewares as $param) {
            $params[] = $_GET[$param] ?? null;
        }
        return $params;
    }

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
}
