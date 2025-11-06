# Invoice & Billing

Create and send professional invoices to your clients. Learn how to manage line items, apply taxes, process payments, set up recurring billing, and track your financial health.

## Creating an Invoice

To create a new invoice:

1. Select a client using the client switcher
2. Navigate to **Financial** → **Invoices**
3. Click **Create Invoice**
4. Configure invoice details:
   - **Invoice Date** - Date the invoice is issued
   - **Due Date** - Payment deadline
   - **Invoice Number** - Auto-generated or custom
   - **Purchase Order** - Client's PO number (optional)
5. Add line items
6. Review totals and tax calculations
7. Click **Save** or **Save & Send**

---

## Line Items

Add different types of line items to your invoices:

### Service Items

Bill for services provided:

- Description of the service
- Quantity (hours or units)
- Rate per unit
- Total amount
- Tax settings

### Product Items

Bill for products or equipment sold:

- Product name and SKU
- Quantity
- Unit price
- Discount (optional)
- Tax settings

### Time Entries

Import billable time directly from tickets:

- Click **Add Time Entries**
- Select date range
- Choose which time entries to include
- Entries are automatically converted to line items

### Expenses

Add reimbursable expenses:

- Expense description
- Amount
- Markup percentage (optional)
- Attach receipts

---

## Tax Configuration

Configure taxes to comply with your jurisdiction:

### Tax Rates

Set up tax rates in Settings → Financial → Tax Rates:

- Tax name (e.g., "VAT", "GST", "Sales Tax")
- Percentage rate
- Default application (always, never, or by client location)

### Per-Line Tax Control

Control tax application at the line item level:

- Mark individual items as taxable or non-taxable
- Apply different tax rates to different items
- View tax breakdown in invoice totals

### Tax-Inclusive vs Tax-Exclusive

Choose your pricing model:

- **Tax-Exclusive** - Tax added on top of subtotal (default)
- **Tax-Inclusive** - Tax included in line item prices

---

## Payment Processing

Multiple ways for clients to pay invoices:

### Online Payments

Accept payments through integrated gateways:

- **Credit/Debit Cards** - Via Stripe integration
- **ACH/Bank Transfer** - Direct bank payments
- **PayPal** - PayPal account payments

Configure payment gateways in Settings → Financial → Payment Gateways.

### Manual Payments

Record payments received outside the system:

1. Open the invoice
2. Click **Record Payment**
3. Enter payment details:
   - Amount received
   - Payment date
   - Payment method
   - Reference/transaction number
4. Click **Save**

### Partial Payments

Invoices support partial payments:

- Accept multiple payments toward one invoice
- Track remaining balance
- Automatically mark as paid when balance reaches zero

---

## Invoice Status Workflow

Invoices progress through several statuses:

1. **Draft** - Invoice being prepared, not sent to client
2. **Sent** - Invoice sent to client, awaiting payment
3. **Viewed** - Client has opened the invoice
4. **Partially Paid** - Some payment received, balance remaining
5. **Paid** - Full payment received
6. **Overdue** - Past due date without payment
7. **Void** - Invoice cancelled or invalidated

---

## Recurring Invoices

Automate billing for ongoing services:

### Setting Up Recurring Invoices

1. Create a standard invoice as a template
2. Click **Make Recurring**
3. Configure recurrence settings:
   - **Frequency** - Daily, Weekly, Monthly, Quarterly, Annually
   - **Start Date** - When to begin recurring
   - **End Condition** - Never, After X occurrences, or specific end date
4. Click **Activate**

### Managing Recurring Invoices

- View all recurring invoices in Financial → Recurring Invoices
- Edit recurrence settings anytime
- Pause or cancel recurring invoices
- Review generated invoices history

---

## Invoice Templates

Customize invoice appearance:

### Template Editor

Customize your invoice template:

- Company logo and branding
- Color scheme
- Header and footer text
- Terms and conditions
- Payment instructions

Access template editor in Settings → Financial → Invoice Templates.

### Multiple Templates

Create different templates for different purposes:

- Standard service invoice
- Product sales invoice
- Retainer/prepayment invoice
- Credit note template

---

## Late Payment Reminders

Automate payment reminders:

### Automatic Reminders

Configure automated reminder emails:

- First reminder: X days after due date
- Second reminder: Y days after first reminder
- Final notice: Z days after second reminder

### Manual Reminders

Send one-off reminders:

1. Open the overdue invoice
2. Click **Send Reminder**
3. Customize email message
4. Click **Send**

---

## Credit Notes

Issue credits for returned services or products:

1. Open the original invoice
2. Click **Create Credit Note**
3. Select which line items to credit
4. Adjust amounts if partial credit
5. Save and send to client

Credit notes can be:

- Applied to future invoices
- Refunded to the client
- Left as account credit

---

## Invoice Reports

Track your financial performance:

- **Aged Receivables** - Outstanding invoices by age
- **Revenue by Client** - Income breakdown per client
- **Revenue by Service** - Income by service type
- **Payment Methods** - Payment method analysis
- **Tax Reports** - Tax collected and owed

Access reports in Financial → Reports.

---

## Late Fees

Automatically add late fees to overdue invoices:

### Configure Late Fees

Settings → Financial → Late Fees:

- **Type** - Fixed amount or percentage
- **Amount** - Fee amount or percentage
- **Grace Period** - Days after due date before applying
- **Maximum Fee** - Cap on late fees (optional)

### Manual Late Fees

Add one-time late fees:

1. Open overdue invoice
2. Click **Add Late Fee**
3. Enter amount and description
4. Fee added as new line item

---

## Best Practices

Tips for efficient invoicing:

- **Clear Descriptions** - Write detailed line item descriptions
- **Itemize Time** - Break down time entries by task
- **Early Delivery** - Send invoices promptly after work completion
- **Payment Terms** - Clearly state payment terms and due dates
- **Follow Up** - Send reminders before and after due dates
- **Accept Multiple Methods** - Offer various payment options
- **Track Everything** - Record all payments immediately

---

## Integration with Other Modules

Invoicing connects with other Nestogy features:

- **Tickets** - Convert billable time to invoice line items
- **Time Tracking** - Import time entries directly
- **Contracts** - Automatic invoicing based on contract terms
- **Projects** - Bill project milestones and deliverables
- **Client Portal** - Clients view and pay invoices online

Learn more about [Contracts](/docs/contracts), [Time Tracking](/docs/time-tracking), and [Client Portal](/docs/client-portal).
