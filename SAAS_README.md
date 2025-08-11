# Nestogy SaaS Implementation

This document provides a complete overview of the SaaS (Software as a Service) functionality implemented in Nestogy.

## 🏗️ Architecture Overview

### Multi-Tenant Structure
- **Company 1**: Platform operator with SUPER_ADMIN users
- **Tenant Companies**: Customer companies with their own isolated data
- **Dual Records**: Each tenant company has both a Company record (for data) and a Client record (for billing)

### Role Hierarchy
- **SUPER_ADMIN** (Level 4): Platform operators in Company 1 only
- **ADMIN** (Level 3): Tenant administrators  
- **TECH** (Level 2): Technical staff
- **USER** (Level 1): Regular users

## 🚀 Quick Setup

1. **Run the setup script:**
   ```bash
   ./setup-saas.sh
   ```

2. **Configure Stripe in `.env`:**
   ```env
   STRIPE_KEY=pk_test_your_publishable_key
   STRIPE_SECRET=sk_test_your_secret_key
   STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
   ```

3. **Create subscription plans in Stripe dashboard**

4. **Update plan IDs in database:**
   ```sql
   UPDATE subscription_plans SET stripe_price_id = 'price_actual_stripe_id' WHERE slug = 'starter';
   ```

5. **Set up webhook endpoint in Stripe:**
   - URL: `https://yourdomain.com/webhooks/stripe`
   - Events: `customer.*`, `subscription.*`, `invoice.*`, `payment_method.*`

## 📊 Database Schema

### New Tables
- `subscription_plans` - Available subscription plans
- Enhanced `clients` table with subscription fields
- Enhanced `companies` table with client linking
- Enhanced `payment_methods` table with Stripe support

### Key Relationships
```
Company 1 (Platform)
├── Clients (Billing Records)
│   ├── company_link_id → Tenant Company
│   ├── subscription_plan_id → SubscriptionPlan
│   └── payment_methods → PaymentMethod[]
└── Super Admin Users

Tenant Company
├── client_record_id → Client (in Company 1)
├── Users (Tenant staff)
├── Clients (Their customers)
└── All other ERP data
```

## 🔧 Key Components

### 1. Registration System
**Route**: `/signup`  
**Controller**: `CompanyRegistrationController`  
**Features**:
- Multi-step form with validation
- Stripe Elements integration
- Company + Client + User creation
- 14-day trial with payment method required

### 2. Billing Portal
**Route**: `/billing`  
**Controller**: `BillingController`  
**Features**:
- Subscription management
- Payment method management
- Plan changes
- Invoice history
- Usage tracking

### 3. Admin Management
**Route**: `/admin/subscriptions`  
**Controller**: `Admin\SubscriptionManagementController`  
**Features**:
- Tenant overview and management
- Subscription plan changes
- Account suspension/reactivation
- Billing analytics

### 4. Background Jobs
- `CheckTrialExpirations` - Daily trial notifications
- `SyncStripeSubscriptions` - Hourly status sync

### 5. Webhook Handler
**Route**: `/webhooks/stripe`  
**Controller**: `Api\Webhooks\StripeWebhookController`  
**Events Handled**:
- Subscription lifecycle events
- Payment method updates
- Invoice status changes

## 🛡️ Security Features

### Multi-Tenant Isolation
- `BelongsToCompany` trait ensures data isolation
- Middleware prevents cross-tenant access
- Super-admin bypass for platform management

### Permission System
- Gate-based authorization
- Role-based access control
- Cross-tenant management permissions

## 🔄 Business Logic

### Trial Management
1. New registrations start with 14-day trial
2. Payment method required during signup ($1 authorization)
3. Automated notifications at 3 days and 1 day remaining
4. Auto-conversion to paid or suspension on expiration

### Subscription Lifecycle
1. **Trialing** → **Active** (successful payment)
2. **Active** → **Past Due** (payment failure)
3. **Past Due** → **Active** (payment recovered)
4. **Past Due** → **Canceled** (grace period expired)
5. **Canceled** → **Active** (reactivation)

### Tenant Suspension
- Automatic suspension for unpaid accounts
- Manual suspension by super-admins
- Data preserved during suspension
- Reactivation restores full access

## 📈 Analytics & Reporting

### Platform Metrics
- Monthly Recurring Revenue (MRR)
- Customer Acquisition Rate
- Churn Rate
- Trial Conversion Rate
- Plan Distribution

### Usage Tracking
- Active users per tenant
- Feature utilization
- Storage usage
- API calls (if implemented)

## 🎛️ Configuration

### SaaS Config (`config/saas.php`)
```php
'platform_company_id' => 1,
'trial' => ['days' => 14],
'billing' => ['currency' => 'USD'],
'features' => ['allow_plan_changes' => true]
```

### Environment Variables
```env
SAAS_PLATFORM_COMPANY_ID=1
SAAS_DEFAULT_TRIAL_DAYS=14
SAAS_REQUIRE_PAYMENT_METHOD=true
SAAS_MAX_USERS_PER_COMPANY=100
```

## 🚦 Routes Overview

### Public Routes
- `GET /` - Welcome page with signup CTA
- `GET /signup` - Registration form
- `POST /signup` - Process registration
- `GET /signup/plans` - Available plans API

### Authenticated Routes
- `GET /billing` - Customer billing portal
- `GET /billing/subscription` - Subscription details
- `GET /billing/payment-methods` - Payment methods
- `PATCH /billing/update-plan` - Change subscription plan

### Admin Routes (Super-Admin only)
- `GET /admin/subscriptions` - Tenant management
- `GET /admin/subscriptions/{client}` - Tenant details
- `POST /admin/subscriptions/{client}/create-tenant` - Create tenant
- `PATCH /admin/subscriptions/{client}/suspend-tenant` - Suspend tenant

### API Routes
- `POST /webhooks/stripe` - Stripe webhook handler
- `GET /api/dashboard/*` - Dashboard APIs with tenant isolation

## 🧪 Testing

### Feature Tests
- `SaasRegistrationTest` - Complete signup flow
- `BillingPortalTest` - Customer billing features
- Webhook handler tests
- Multi-tenant access control tests

### Running Tests
```bash
php artisan test --filter=SaasRegistrationTest
php artisan test --filter=BillingPortalTest
```

## 🔧 Maintenance Tasks

### Daily
- Check trial expirations
- Process failed payments
- Sync subscription statuses

### Weekly
- Generate usage reports
- Review suspended accounts
- Analyze churn metrics

### Monthly
- Update MRR calculations
- Review plan utilization
- Customer health scoring

## 🚨 Monitoring

### Key Metrics to Monitor
- Trial conversion rate
- Payment failure rate
- Customer churn
- Platform uptime
- Database performance

### Alert Conditions
- High payment failure rate
- Unusual churn spike
- Trial conversion drop
- System errors in webhooks

## 📞 Support

### Customer Support Features
- Self-service billing portal
- Stripe billing portal integration
- Trial extension capabilities
- Plan change requests

### Platform Support
- Admin override capabilities
- Tenant data access
- Subscription management
- Usage analytics

## 🔄 Deployment Checklist

### Pre-Deployment
- [ ] Stripe keys configured
- [ ] Webhook endpoints set up
- [ ] Database migrations run
- [ ] Subscription plans seeded
- [ ] Queue workers configured

### Post-Deployment
- [ ] Test complete signup flow
- [ ] Verify webhook handling
- [ ] Check trial notifications
- [ ] Test plan changes
- [ ] Validate billing portal

### Ongoing Maintenance
- [ ] Monitor payment success rates
- [ ] Review customer feedback
- [ ] Optimize conversion funnels
- [ ] Update pricing strategies
- [ ] Scale infrastructure as needed

## 🎯 Future Enhancements

### Planned Features
- Usage-based billing
- API rate limiting per plan
- White-label options
- Advanced analytics dashboard
- Automated dunning management

### Integration Opportunities
- CRM integrations
- Accounting software sync
- Marketing automation
- Support ticket systems
- Business intelligence tools

---

For technical support or questions about the SaaS implementation, please refer to the codebase documentation or contact the development team.