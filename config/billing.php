<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ticket Billing Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration controls automatic billing for support tickets based
    | on client contracts, time entries, and per-ticket rates.
    |
    */

    'ticket' => [
        
        /*
        |--------------------------------------------------------------------------
        | Ticket Billing Enabled
        |--------------------------------------------------------------------------
        |
        | Master switch for ticket billing functionality. When disabled, no
        | automatic billing will occur for tickets.
        |
        */
        'enabled' => env('TICKET_BILLING_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Auto-Bill on Ticket Close
        |--------------------------------------------------------------------------
        |
        | When enabled, invoices will be automatically generated when tickets
        | are closed. When disabled, billing must be triggered manually or via
        | scheduled tasks.
        |
        */
        'auto_bill_on_close' => env('AUTO_BILL_ON_CLOSE', false),

        /*
        |--------------------------------------------------------------------------
        | Auto-Bill on Ticket Resolve
        |--------------------------------------------------------------------------
        |
        | When enabled, invoices will be automatically generated when tickets
        | are resolved (not just closed). This provides earlier billing.
        |
        */
        'auto_bill_on_resolve' => env('AUTO_BILL_ON_RESOLVE', false),

        /*
        |--------------------------------------------------------------------------
        | Default Billing Strategy
        |--------------------------------------------------------------------------
        |
        | The default strategy to use when determining how to bill a ticket:
        | - 'time_based': Bill based on time entries only
        | - 'per_ticket': Use fixed per-ticket rate from contract
        | - 'mixed': Combine time entries + per-ticket rate
        |
        */
        'default_strategy' => env('BILLING_STRATEGY_DEFAULT', 'time_based'),

        /*
        |--------------------------------------------------------------------------
        | Minimum Billable Hours
        |--------------------------------------------------------------------------
        |
        | Minimum hours to charge for time-based billing. Useful for enforcing
        | minimum charges (e.g., 0.25 = 15 minutes minimum).
        |
        */
        'min_billable_hours' => env('BILLING_MIN_HOURS', 0.25),

        /*
        |--------------------------------------------------------------------------
        | Round Hours To
        |--------------------------------------------------------------------------
        |
        | Round billable hours to nearest increment:
        | - 0.25 = 15 minutes
        | - 0.5 = 30 minutes
        | - 1.0 = 1 hour
        |
        */
        'round_hours_to' => env('BILLING_ROUND_HOURS_TO', 0.25),

        /*
        |--------------------------------------------------------------------------
        | Invoice Due Days
        |--------------------------------------------------------------------------
        |
        | Number of days until generated invoices are due.
        |
        */
        'invoice_due_days' => env('BILLING_INVOICE_DUE_DAYS', 30),

        /*
        |--------------------------------------------------------------------------
        | Queue Billing Jobs
        |--------------------------------------------------------------------------
        |
        | Queue name for processing ticket billing jobs. Uses 'billing' queue
        | by default for separate processing and monitoring.
        |
        */
        'queue' => env('BILLING_QUEUE', 'billing'),

        /*
        |--------------------------------------------------------------------------
        | Job Retries
        |--------------------------------------------------------------------------
        |
        | Number of times to retry failed billing jobs.
        |
        */
        'job_retries' => env('BILLING_JOB_RETRIES', 3),

        /*
        |--------------------------------------------------------------------------
        | Job Timeout
        |--------------------------------------------------------------------------
        |
        | Maximum seconds a billing job can run before timing out.
        |
        */
        'job_timeout' => env('BILLING_JOB_TIMEOUT', 120),

        /*
        |--------------------------------------------------------------------------
        | Require Approval
        |--------------------------------------------------------------------------
        |
        | When enabled, generated invoices will be marked as drafts requiring
        | manual approval before being sent to clients.
        |
        */
        'require_approval' => env('BILLING_REQUIRE_APPROVAL', true),

        /*
        |--------------------------------------------------------------------------
        | Skip Zero Amount Invoices
        |--------------------------------------------------------------------------
        |
        | When enabled, invoices with $0 amount will not be created.
        |
        */
        'skip_zero_invoices' => env('BILLING_SKIP_ZERO_INVOICES', true),

        /*
        |--------------------------------------------------------------------------
        | Auto-Send Invoices
        |--------------------------------------------------------------------------
        |
        | When enabled and require_approval is false, invoices will be
        | automatically sent to clients after generation.
        |
        */
        'auto_send' => env('BILLING_AUTO_SEND', false),

        /*
        |--------------------------------------------------------------------------
        | Include Unbilled Only
        |--------------------------------------------------------------------------
        |
        | When processing pending billing, only include tickets that haven't
        | already been invoiced.
        |
        */
        'include_unbilled_only' => true,

        /*
        |--------------------------------------------------------------------------
        | Batch Size
        |--------------------------------------------------------------------------
        |
        | Number of tickets to process per batch in scheduled tasks.
        |
        */
        'batch_size' => env('BILLING_BATCH_SIZE', 100),

    ],

    /*
    |--------------------------------------------------------------------------
    | Per-Client Configuration Overrides
    |--------------------------------------------------------------------------
    |
    | Allow specific clients to have custom billing configurations that
    | override the global defaults.
    |
    */
    'client_overrides_enabled' => env('BILLING_CLIENT_OVERRIDES_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Abuse Detection & Monitoring
    |--------------------------------------------------------------------------
    |
    | Protect against abuse of "unlimited" or all-inclusive contracts.
    | Even unlimited plans should have safeguards.
    |
    */
    'abuse' => [
        
        /*
        |--------------------------------------------------------------------------
        | Soft Limits (Alert Thresholds)
        |--------------------------------------------------------------------------
        |
        | When usage exceeds these limits, alerts are logged and contract is
        | flagged for review. No billing is forced yet.
        |
        */
        'soft_ticket_limit' => env('BILLING_ABUSE_SOFT_TICKETS', 100),
        'soft_hours_limit' => env('BILLING_ABUSE_SOFT_HOURS', 200),

        /*
        |--------------------------------------------------------------------------
        | Hard Limits (Billing Override)
        |--------------------------------------------------------------------------
        |
        | When usage exceeds these limits, even "unlimited" contracts will be
        | billed for excess usage. This prevents severe abuse.
        |
        */
        'hard_ticket_limit' => env('BILLING_ABUSE_HARD_TICKETS', 500),
        'hard_hours_limit' => env('BILLING_ABUSE_HARD_HOURS', 500),

        /*
        |--------------------------------------------------------------------------
        | Anomaly Detection Spike Threshold
        |--------------------------------------------------------------------------
        |
        | If current month usage is X times the average of previous months,
        | flag as anomalous. 2.0 = 200% of normal = potential spike.
        |
        */
        'spike_threshold' => env('BILLING_ABUSE_SPIKE_THRESHOLD', 2.0),

        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        |
        | Send notifications when abuse is detected.
        |
        */
        'notify_on_soft_limit' => env('BILLING_ABUSE_NOTIFY_SOFT', true),
        'notify_on_hard_limit' => env('BILLING_ABUSE_NOTIFY_HARD', true),
        'notify_on_anomaly' => env('BILLING_ABUSE_NOTIFY_ANOMALY', true),
        'notification_recipients' => env('BILLING_ABUSE_NOTIFY_EMAILS', 'admin@example.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for billing operations.
    |
    */
    'logging' => [
        'enabled' => env('BILLING_LOGGING_ENABLED', true),
        'channel' => env('BILLING_LOG_CHANNEL', 'stack'),
        'level' => env('BILLING_LOG_LEVEL', 'info'),
    ],

];
