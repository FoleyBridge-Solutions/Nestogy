# Bank Syncing - Quick Start Guide

## 🚀 Get Started in 5 Minutes

### 1. Run Migrations (Required)
```bash
cd /opt/nestogy
php artisan migrate
```

This creates:
- `plaid_items` table
- `bank_transactions` table  
- Adds Plaid fields to `accounts`, `payments`, `expenses`

### 2. Configure Plaid (Required)

#### Get Plaid Credentials
1. Go to https://dashboard.plaid.com
2. Sign up for free (sandbox account)
3. Copy your Client ID and Secret

#### Update .env
```bash
PLAID_ENABLED=true
PLAID_CLIENT_ID=your_client_id_here
PLAID_SECRET=your_secret_here
PLAID_ENVIRONMENT=sandbox
PLAID_WEBHOOK_URL=https://yourdomain.com/api/webhooks/plaid
```

### 3. Test the API (Optional - for testing)

#### Create Link Token
```bash
curl -X GET http://your-domain.com/financial/bank-connections/create \
  -H "Cookie: laravel_session=your_session_cookie"
```

Response:
```json
{
  "link_token": "link-sandbox-abc123...",
  "expiration": "2024-01-01T12:00:00Z"
}
```

### 4. Using Plaid Link (Frontend - Next Step)

Add to your Blade template:
```html
<!-- Include Plaid Link SDK -->
<script src="https://cdn.plaid.com/link/v2/stable/link-initialize.js"></script>

<script>
// Get link token from your backend
fetch('/financial/bank-connections/create')
  .then(res => res.json())
  .then(data => {
    // Initialize Plaid Link
    const handler = Plaid.create({
      token: data.link_token,
      onSuccess: (public_token, metadata) => {
        // Send to backend to complete connection
        fetch('/financial/bank-connections', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({
            public_token: public_token,
            institution_id: metadata.institution.institution_id,
            institution_name: metadata.institution.name
          })
        })
        .then(res => res.json())
        .then(data => {
          alert('Bank connected successfully!');
          // Reload page or update UI
        });
      },
      onExit: (err, metadata) => {
        if (err != null) {
          console.error('Plaid Link error:', err);
        }
      }
    });
    
    // Open Plaid Link
    handler.open();
  });
</script>
```

---

## 🔧 Available Routes

All routes are prefixed with `/financial/`

### Bank Connections
- `GET /bank-connections` - List all connections
- `GET /bank-connections/create` - Get link token for new connection
- `POST /bank-connections` - Complete connection after Plaid Link
- `GET /bank-connections/{item}` - View connection details
- `POST /bank-connections/{item}/sync` - Manually sync transactions
- `DELETE /bank-connections/{item}` - Remove connection

### Bank Transactions  
- `GET /bank-transactions` - List all transactions
- `GET /bank-transactions/{id}` - View transaction details
- `POST /bank-transactions/{id}/reconcile` - Reconcile with payment/expense
- `POST /bank-transactions/{id}/create-payment` - Create payment from transaction
- `POST /bank-transactions/{id}/create-expense` - Create expense from transaction
- `POST /bank-transactions/bulk-reconcile` - Auto-reconcile multiple transactions

### Webhooks
- `POST /api/webhooks/plaid` - Plaid webhook endpoint (configured in Plaid dashboard)

---

## 🧪 Testing with Sandbox

### Test Bank Credentials
```
Bank: Any supported bank in Plaid Link
Username: user_good
Password: pass_good
```

### Test Flow
1. Click "Connect Bank Account" button
2. Select any bank
3. Login with test credentials
4. Select accounts to connect
5. Transactions will sync automatically

### Expected Results
- Plaid item created in database
- Accounts synced from Plaid
- ~2 years of historical transactions imported
- Webhooks triggered for updates

---

## 📊 Reconciliation Workflow

### Automatic Reconciliation
The system automatically attempts to match bank transactions with:
1. **Payments** (for income/deposits)
2. **Expenses** (for withdrawals)

Matching is based on:
- Amount (within $0.01 tolerance)
- Date (within 3 days)
- Description similarity

### Manual Reconciliation
1. View unreconciled transactions
2. See suggested matches with confidence scores
3. Click "Reconcile" to match manually
4. Or create new payment/expense

### Creating from Transactions
```php
// Create payment from bank transaction
POST /financial/bank-transactions/{id}/create-payment
{
  "client_id": 123,
  "invoice_id": 456  // optional
}

// Create expense from bank transaction
POST /financial/bank-transactions/{id}/create-expense
{
  "category_id": 789,  // optional
  "client_id": 123,    // optional
  "project_id": 456    // optional
}
```

---

## 🔄 Sync Behavior

### Automatic Syncing
- **Webhooks:** Plaid sends updates when new transactions are available
- **Frequency:** Real-time for new transactions
- **Historical:** Last 2 years synced on initial connection

### Manual Syncing
```bash
# Trigger manual sync via API
POST /financial/bank-connections/{item}/sync
```

### Scheduled Syncing (To Be Implemented)
```php
// In app/Console/Kernel.php
$schedule->job(new SyncAllPlaidAccounts)->hourly();
```

---

## ⚠️ Common Issues & Solutions

### "Failed to create link token"
- Check PLAID_CLIENT_ID and PLAID_SECRET in .env
- Verify Plaid account is active
- Check APP_URL is accessible

### "Webhook not received"
- Ensure PLAID_WEBHOOK_URL is publicly accessible
- Check Laravel logs for webhook errors
- Verify webhook endpoint is not CSRF protected

### "Transactions not syncing"
- Check plaid_items.status (should be 'active')
- Review plaid_items.error_message for errors
- Manually trigger sync

### "Cannot connect bank"
- Using sandbox credentials (user_good/pass_good)?
- Plaid Link SDK loaded correctly?
- HTTPS required for production

---

## 🎯 Next Steps

### For Development
1. ✅ Run migrations
2. ✅ Configure Plaid
3. ⏳ Build Livewire components for UI
4. ⏳ Create views for bank connections
5. ⏳ Implement background sync jobs

### For Production
1. Switch to Plaid production environment
2. Implement webhook signature verification
3. Set up monitoring for sync failures
4. Create user documentation
5. Add email notifications for errors

---

## 📚 Key Models & Services

### Models
- `PlaidItem` - Represents a bank connection
- `BankTransaction` - Represents a synced transaction
- `Account` - Enhanced with Plaid data
- `Payment` - Can be linked to transactions
- `Expense` - Can be linked to transactions

### Services
- `PlaidService` - All Plaid API interactions
- `BankReconciliationService` - Matching & reconciliation logic

### Controllers
- `BankConnectionController` - Manage connections
- `BankTransactionController` - Manage transactions
- `PlaidWebhookController` - Handle Plaid events

---

## 💡 Pro Tips

1. **Start with Sandbox:** Always test in sandbox before production
2. **Monitor Webhooks:** Set up logging for webhook events
3. **Handle Errors:** Plaid items can enter error states - show users
4. **Reauth Flow:** Implement UI for handling reauth_required status
5. **Category Mapping:** Map Plaid categories to your expense categories
6. **Balance Checking:** Use balance updates to detect discrepancies

---

## 🆘 Need Help?

- **Plaid Docs:** https://plaid.com/docs/
- **Plaid Support:** https://dashboard.plaid.com/support
- **Laravel Docs:** https://laravel.com/docs
- **Check Logs:** `storage/logs/laravel.log`

---

## ✅ Checklist

- [ ] Migrations run successfully
- [ ] Plaid credentials configured
- [ ] Test connection in sandbox
- [ ] Transactions syncing
- [ ] Reconciliation working
- [ ] Webhooks receiving updates
- [ ] Error handling tested
- [ ] Ready for frontend development

---

**Implementation Status:** Backend Complete ✅  
**Ready For:** Frontend Development & Testing  
**Estimated Time to Full Production:** 1-2 weeks (with UI components)
