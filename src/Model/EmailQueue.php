<?php
// src/Model/EmailQueue.php

namespace Twetech\Nestogy\Model;

use Twetech\Nestogy\Model\Client;
use Twetech\Nestogy\Model\Invoice;
use PDO;

/**
 * Class EmailQueue
 * Handles the queueing of email notifications for invoices
 * 
 * @package Twetech\Nestogy\Model
 */
class EmailQueue {

    /** @var PDO */
    private $pdo;
    
    /** @var Invoice */
    private $invoice;
    
    /** @var Client */
    private $client;

    /**
     * EmailQueue constructor
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->invoice = new Invoice($pdo);
        $this->client = new Client($pdo);
    }

    /**
     * Sends invoice notifications for multiple invoices
     * 
     * @param array $invoice_ids Array of invoice IDs to send notifications for
     * @return void
     */
    public function SendInvoiceNotification($invoice_ids) {
        $emails = [];
        foreach ($invoice_ids as $invoice_id) {
            $emails[] = $this->getInvoiceEmail($invoice_id);
        }
        $this->addToQueue($emails);
    }

    /**
     * Generates email content for a single invoice
     * 
     * @param int $invoice_id The ID of the invoice
     * @param int $reminder_number Optional reminder number for follow-up emails
     * @return array|false Email data array or false if invoice not found
     */
    private function getInvoiceEmail($invoice_id, $reminder_number = 0) {
        $invoice = $this->invoice->getInvoice($invoice_id);
        if (!$invoice) {
            return false;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM companies WHERE company_id = 1");
        $stmt->execute();
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        $company_name = $company['company_name'];
        $company_phone = $company['company_phone'];
        $config_invoice_from_email = "accounting@twe.tech";#TODO: get from config

        $client = $this->client->getClientContact($invoice['invoice_client_id'], 'billing');
        $contact_name = $client['contact_name'];
        $contact_email = $client['contact_email'];

        $invoice_prefix = $invoice['invoice_prefix'];
        $invoice_number = $invoice['invoice_number'];
        $invoice_scope = $invoice['invoice_scope'];
        $invoice_date = $invoice['invoice_date'];
        $invoice_amount = $invoice['invoice_amount'];
        $invoice_due = $invoice['invoice_due'];
        $invoice_url_key = $invoice['invoice_url_key'];

        $subject = "$company_name Invoice $invoice_prefix$invoice_number";
        if ($reminder_number > 0) {
            $subject = "Reminder $reminder_number: $subject";
        }

        $body = "Hello $contact_name,<br><br>
            An invoice regarding $invoice_scope has been generated. Please view the details below.<br>
            <br>
            Invoice\: $invoice_prefix$invoice_number<br>
            Issue Date\: $invoice_date<br>
            Total\: $invoice_amount<br>
            Due Date\: $invoice_due<br>
            <br>
            To view your invoice, please click 
                <a href=\'https://portal.twe.tech/portal/guest_view_invoice.php?invoice_id=$invoice_id&url_key=$invoice_url_key\'>
                    here
                </a>.<br>
            <br>
            --<br>
            $company_name - Billing<br>
            $config_invoice_from_email<br>
            $company_phone";
        
        return [
            'from' => $config_invoice_from_email,
            'from_name' => $company_name . " - Accounting",
            'recipient' => $contact_email,
            'recipient_name' => $contact_name,
            'subject' => $subject,
            'body' => $body
        ];
    }

    /**
     * Adds multiple emails to the email queue
     * 
     * @param array $emails Array of email data to be queued
     * @return void
     */
    private function addToQueue($emails) {
        foreach ($emails as $email) {
            $from = strval($email['from']);
            $from_name = strval($email['from_name']);
            $recipient = strval($email['recipient']);
            $recipient_name = strval($email['recipient_name']);
            $subject = strval($email['subject']);
            $body = strval($email['body']);
    
            $cal_str = '';
            if (isset($email['cal_str'])) {
                $cal_str = "'" . sanitizeInput($email['cal_str']) . "'";
            }
    
            // Check if 'email_queued_at' is set and not empty
            if (isset($email['queued_at']) && !empty($email['queued_at'])) {
                $queued_at = "'" . sanitizeInput($email['queued_at']) . "'";
            } else {
                // Use the current date and time if 'email_queued_at' is not set or empty
                $queued_at = 'CURRENT_TIMESTAMP()';
            }
    
            if (isset($email['cal_str'])) {
                $sql = "INSERT INTO email_queue (email_recipient, email_recipient_name, email_from, email_from_name, email_subject, email_content, email_queued_at, email_cal_str)
                    VALUES (
                        :email_recipient, :email_recipient_name, :email_from, :email_from_name, :email_subject, :email_content, :email_queued_at, :email_cal_str
                    )";
            } else {
                $sql = "INSERT INTO email_queue (email_recipient, email_recipient_name, email_from, email_from_name, email_subject, email_content, email_queued_at)
                    VALUES (
                        :email_recipient, :email_recipient_name, :email_from, :email_from_name, :email_subject, :email_content, :email_queued_at
                    )";
            }

            // Bind the values to the statement
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email_recipient', $recipient);
            $stmt->bindParam(':email_recipient_name', $recipient_name);
            $stmt->bindParam(':email_from', $from);
            $stmt->bindParam(':email_from_name', $from_name);
            $stmt->bindParam(':email_subject', $subject);
            $stmt->bindParam(':email_content', $body);
            $stmt->bindParam(':email_queued_at', $queued_at);
            if (isset($email['cal_str'])) { $stmt->bindParam(':email_cal_str', $cal_str); }
            $stmt->execute();
        }
    }
}