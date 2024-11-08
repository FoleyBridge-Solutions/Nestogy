<?php
// src/Controller/DebugController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Model\Accounting;
use Twetech\Nestogy\Model\Client;
use NumberFormatter;

class DebugController {
    private $pdo;
    private $view;
    private $auth;
    private $accounting;
    private $client;
    private $currency_format;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->view = new View();
        $this->auth = new Auth($pdo);
        $this->accounting = new Accounting($pdo);
        $this->client = new Client($pdo);
        $this->currency_format = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        if (!$this->auth->check()) {
            header('Location: /login');
            exit;
        }
    }

    public function index() {
        $this->clientsWithLatePayments();
    }

    public function paymentsRecentlyMessedUp() {
        $data = [
            'title' => 'Debug',
            'body' => ''
        ];

        $payments = $this->accounting->getPayments();

        
        foreach ($payments as $payment) {
            if ($payment['payment_updated_at'] != NULL) {
                $invoice_total = $this->accounting->getInvoiceTotal($payment['payment_invoice_id']);
                $data['body'] .= $payment['payment_id'] . ' - ' . $payment['payment_updated_at'];
                $data['body'] .= ' - Invoice ID: <a href="/public/?page=invoice&invoice_id=' . $payment['payment_invoice_id'] . '">' . $payment['payment_invoice_id'] . '</a>';
                $data['body'] .= ' - Invoice Total: ' . $invoice_total . ' - Payment Amount: ' . $payment['payment_amount'];
                $data['body'] .= ' - Payment difference percentage: ' . round(($invoice_total - $payment['payment_amount']) / $invoice_total * 100, 2) . '%';
                $data['body'] .= '<br>';
                //set payment_amount to invoice_total
                $sql = 'UPDATE payments SET payment_amount = :invoice_total WHERE payment_id = :payment_id';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['invoice_total' => $invoice_total, 'payment_id' => $payment['payment_id']]);
            }
        }
        $this->view->render('debug', $data);
    }

    public function clientsWithLatePayments() {
        $data = [
            'title' => 'Debug'
        ];

        $clients = $this->client->getClients();
        foreach ($clients as $client) {
            $client['balance'] = round($this->accounting->getClientBalance($client['client_id']), 2);
            $client['past_due'] = round($this->accounting->getClientPastDueBalance($client['client_id']), 2);
            if ($client['balance'] > 0) {
            }

            //If clients past due balance is greater than 0, check when the last payment was
            if ($client['past_due'] > 0) {
                $client['last_payment'] = $this->accounting->getLastPaymentByClient($client['client_id']);
            }
            // Get the contact info for the client
            $client['contact'] = $this->client->getClientContact($client['client_id']);
            // if last payment is older than 30 days, add to list
            if ($client['last_payment']['payment_date'] < date('Y-m-d', strtotime('-30 days'))) {
                $data['clients_with_late_payments'][] = $client;
            }
        }
        $emails = [];
        foreach ($data['clients_with_late_payments'] as $client) {
            $emails[] = [
                'to' => $client['contact']['contact_email'],
                'subject' => 'Past Due Balance',
                'body' => 'Dear '. $client['contact']['contact_name'] .',

                We are reaching out to remind you of the outstanding balance on your account. As per our records, your account currently shows a past due balance of $'. $client['past_due'] .'.

                Please make the necessary payment as soon as possible to avoid any disruptions to your service. You can make a payment online through our secure payment portal or by contacting our office at (555) 555-5555.

                Thank you for your prompt attention to this matter.

                Best regards,
                ' . $this->auth->getUser()['name'] . '
                ' . $this->auth->getUser()['email'] . '
                ' . $this->auth->getUser()['phone'] . '
                ' . $this->auth->getUser()['address'] . '
                '
            ];
        }

        //sort clients with late payments by last payment date
        usort($data['clients_with_late_payments'], function($a, $b) {
            return strtotime($a['last_payment']['payment_date']) - strtotime($b['last_payment']['payment_date']);
        });
        $data['body'] = '<pre>';
        #$data['body'] .= print_r($data['clients_with_late_payments'], true);
        $data['body'] .= '</pre>';

        // Make a table of clients with late payments
        $data['body'] .= '<table class="table table-striped">';
        $data['body'] .= '<tr>'.
                '<th>Client Name</th>'.
                '<th>Last Payment Date</th>'.
                '<th>Past Due Balance</th>'.
                '<th>Contact</th>'.
                '<th>Actions</th>'.
            '</tr>';
        foreach ($data['clients_with_late_payments'] as $client) {
            if ($client['past_due'] > 0) {
                $data['body'] .= '<tr><td><a href="/public/?page=statement&client_id=' . $client['client_id'] . '">' . $client['client_name'] . '</a></td><td>' . date('M j, Y', strtotime($client['last_payment']['payment_date'])) . '</td><td>' . $client['past_due'] . '</td><td>'. $client['contact']['contact_name'] .': <a href="tel:' . $client['contact']['contact_phone'] . '">' . $client['contact']['contact_phone'] . '</a></td><td>'.
                '<form method="post">
                    <input type="hidden" name="client_id" value="' . $client['client_id'] . '">
                    <input type="hidden" name="ticket_assigned_to" value="' . $this->auth->getUser()['id'] . '">
                    <input type="hidden" name="ticket_contact" value="' . $client['contact']['contact_id'] . '">
                    <input type="hidden" name="ticket_subject" value="Past Due Balance">
                    <input type="hidden" name="ticket_priority" value="1">
                    <input type="hidden" name="ticket_details" value="The client has a past due balance of ' . $client['past_due'] . ' as of ' . date('M j, Y') . '">
                    <input type="hidden" name="ticket_type" value="accounting">
                    <button type="submit" class="btn btn-primary">Create Ticket</button>
                </form>
                </td></tr>';
            }
        }
        $data['body'] .= '</table>';
        $this->view->render('debug', $data);
    }
}