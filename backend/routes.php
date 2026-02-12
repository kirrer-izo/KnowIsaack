<?php 

use App\Controllers\ContactController;
use App\Infrastructure\Mail\ResendMailer;

$request = $_SERVER['REQUEST_URI'];

//  Remove subdirectory from path if you are using one
$base_path = '/portfolio';
$path = str_replace($base_path, '', $request);

switch ($path) {
    case '/':
    case '/home':
        require __DIR__ . '/../frontend/pages/home.html';
        break;
    case '/live-your-books':
        require __DIR__ . '/../frontend/pages/live-your-books.html';
        break;
    case '/sole-proprietor-crm':
        require __DIR__ . '/../frontend/pages/sole-proprietor-crm.html';
        break;
    case '/api/contact':
        // This handles your fetch request from the contact modal
        $mailer = new ResendMailer();
        $controller = new ContactController($mailer);
        $controller->handleRequest();
        break;

    default: 
        http_response_code(404);
        echo "Page not found.";
        break;
}