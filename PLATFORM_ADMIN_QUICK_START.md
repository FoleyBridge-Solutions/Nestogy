# Platform Admin Dashboard - Quick Start Guide

## Accessing the Dashboard

**URL:** `http://localhost:8000/admin` (or `https://your-domain.com/admin`)

**Login Credentials (Super Admin):**
- Email: `super@nestogy.com`
- Password: `password123`

**Note:** No navigation links were added per requirements. Bookmark the `/admin` URL for quick access.

## Dashboard Overview

### Main Dashboard (`/admin`)
- **Purpose:** High-level platform health overview
- **Key Metrics:** MRR, churn rate, ARPU, LTV, total companies
- **Charts:** 12-month revenue trends, signups vs cancellations
- **Quick Links:** Jump to Companies, Billing, or Analytics

### Companies (`/admin/companies`)
- **Purpose:** Manage all MSP tenant companies
- **Features:**
  - Search by name or email
  - Filter by status (active/suspended)
  - Filter by subscription type
  - Suspend/Resume companies
  - View detailed company information

### Company Detail (`/admin/companies/{id}`)
- **Purpose:** Deep dive into individual tenant
- **Tabs:**
  - **Overview:** Basic info + subscription summary
  - **Subscription:** Full billing details, Stripe IDs
  - **Users:** List all users in the company

### Billing Dashboard (`/admin/billing`)
- **Purpose:** Monitor subscription health and revenue
- **Features:**
  - Failed payments alert (past_due subscriptions)
  - Active subscriptions list
  - Revenue metrics

### Analytics (`/admin/analytics`)
- **Purpose:** Business intelligence and retention analysis
- **Features:**
  - Cohort analysis (retention by signup month)
  - LTV and ARPU metrics
  - Top 10 revenue companies
  - Trial conversion rate

## Common Tasks

### Suspend a Company
1. Go to `/admin/companies`
2. Find the company (search or filter)
3. Click **Suspend** button
4. Enter suspension reason (min 10 characters)
5. Click **Suspend Company** in modal
6. ✅ Company users are immediately logged out

### Resume a Company
1. Go to `/admin/companies`
2. Find suspended company (filter by "Suspended" status)
3. Click **Resume** button
4. ✅ Company can login immediately

### View Revenue Trends
1. Go to `/admin` (main dashboard)
2. Scroll to "Revenue Trends (12 Months)" chart
3. Hover over chart for exact MRR per month

### Check Failed Payments
1. Go to `/admin/billing`
2. Check "Failed Payments (Past Due)" section
3. Click company name to view full details

### Analyze Retention
1. Go to `/admin/analytics`
2. View "Cohort Analysis" table
3. Look for retention rates:
   - Green badge (≥80%): Excellent retention
   - Yellow badge (60-79%): Good retention
   - Red badge (<60%): Needs attention

## Business Metrics Explained

### MRR (Monthly Recurring Revenue)
- **What:** Total monthly revenue from all active subscriptions
- **Calculation:** Sum of `monthly_amount` where `status = 'active'`
- **Excludes:** Trial subscriptions (only counts paying customers)

### Churn Rate
- **What:** Percentage of customers lost in last 30 days
- **Formula:** `(Cancellations in last 30d / Active at start) × 100`
- **Good Rate:** <5% monthly churn

### ARPU (Average Revenue Per User)
- **What:** Average revenue generated per active company
- **Formula:** `Total MRR / Number of Active Companies`
- **Use:** Compare against industry benchmarks

### LTV (Lifetime Value)
- **What:** Expected total revenue from a customer
- **Formula:** `ARPU / (Churn Rate / 100)`
- **Example:** $100 ARPU with 5% churn = $2,000 LTV

### Trial Conversion Rate
- **What:** Percentage of trials that become paying customers
- **Formula:** `(Converted Trials / Total Ended Trials) × 100`
- **Good Rate:** >30% trial conversion

### Cohort Retention
- **What:** Percentage of companies still active from each signup month
- **Formula:** `(Still Active from Cohort / Total Signed Up) × 100`
- **Tracks:** Long-term customer loyalty

## Security & Access

### Who Can Access?
- **Only super-admin users:**
  - Must have `company_id = 1` (Nestogy Platform company)
  - Must have `admin` role assigned
  - Must be authenticated and email verified

### What Happens If I Try to Access Without Permission?
- **Redirected to login** if not authenticated
- **403 Forbidden error** if not super-admin
- **No data leakage** - middleware blocks at route level

### What Companies Can Be Managed?
- **All tenant companies** (company_id > 1)
- **Cannot manage** Platform company (company_id = 1)
- **Cannot delete** companies (only suspend/resume)

## Technical Notes

### Suspension Details
When you suspend a company:
1. Company marked as inactive (`is_active = false`)
2. Subscription status changed to `suspended`
3. **All user sessions deleted** from database
4. **Remember tokens cleared** (cannot auto-login)
5. Reason stored in subscription metadata
6. Action logged with timestamp and actor

**Effect:** Users are logged out immediately and cannot re-login until resumed.

### Resume Details
When you resume a company:
1. Company marked as active (`is_active = true`)
2. Subscription status changed to `active`
3. Users can login immediately (no delay)
4. Resume action logged with metadata

### Chart Technology
- **Built with:** Flux UI native charts (no external libraries)
- **Features:** Tooltips, grid lines, axis formatting, multi-series support
- **Performance:** Client-side rendering, no API calls

### Data Refresh
- **Real-time:** All data loads on page visit
- **No auto-refresh:** Refresh browser to see latest data
- **Future:** Consider adding 5-minute cache for expensive queries

## Troubleshooting

### "403 Forbidden" Error
- **Cause:** Not a super-admin user
- **Fix:** Login with `super@nestogy.com` or verify your user has:
  - `company_id = 1`
  - `admin` role assigned

### Charts Not Displaying
- **Cause:** Missing data or Flux UI not loaded
- **Fix:** 
  - Check browser console for JavaScript errors
  - Ensure Vite is running (`npm run dev`)
  - Verify Flux UI is installed

### Suspend Button Not Working
- **Cause:** Modal not opening or validation error
- **Fix:**
  - Check console for JavaScript errors
  - Ensure Livewire is properly configured
  - Verify suspension reason is at least 10 characters

### MRR Shows $0
- **Cause:** No active subscriptions in database
- **Fix:** Normal for fresh install, will populate as companies subscribe

### No Companies in List
- **Cause:** Only platform company (ID 1) exists
- **Fix:** 
  - Register test companies via `/register` route
  - Seed test companies if in development

## Quick Reference: Routes

| URL | Purpose |
|-----|---------|
| `/admin` | Main dashboard |
| `/admin/companies` | Company list |
| `/admin/companies/{id}` | Company detail |
| `/admin/billing` | Billing dashboard |
| `/admin/analytics` | Analytics & cohorts |

## Quick Reference: Permissions

| Permission | Description |
|------------|-------------|
| `platform.*` | Full platform admin access |
| `platform.dashboard` | View main dashboard |
| `platform.companies.view` | View company list |
| `platform.companies.suspend` | Suspend companies |
| `platform.companies.resume` | Resume companies |
| `platform.billing.view` | View billing dashboard |
| `platform.analytics.view` | View analytics |

## Support

**Implementation Details:** See `PLATFORM_ADMIN_IMPLEMENTATION_COMPLETE.md`  
**Original Plan:** See `PLATFORM_ADMIN_IMPLEMENTATION_PLAN.md`  

**Questions?** All business logic is in:
- **Service:** `app/Domains/Platform/Services/PlatformBillingService.php`
- **Components:** `app/Livewire/Admin/`
- **Views:** `resources/views/livewire/admin/`
- **Routes:** `routes/admin.php`
