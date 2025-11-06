# Ticket System

The ticket system is the core of your support workflow in Nestogy. Learn how to create tickets, assign them to technicians, track time, manage SLAs, and provide excellent customer support.

## Creating a Ticket

To create a new support ticket:

1. Select a client using the client switcher
2. Click **New Ticket** in quick actions or sidebar
3. Enter ticket details:
   - **Title** - Brief summary of the issue
   - **Description** - Detailed problem description
   - **Priority** - Low, Medium, High, or Critical
   - **Category** - Issue type (Hardware, Software, Network, etc.)
   - **Assigned To** - Select a technician
4. Click **Create Ticket**

> **Email to Ticket**: Tickets can also be created automatically from emails sent to your support address. Configure this in Settings → Email Integration.

---

## Ticket Priorities

Priorities help you triage and organize your support queue:

- **Critical** - System down, business stopped. Immediate response required.
- **High** - Major functionality impaired. Work within 2 hours.
- **Medium** - Important but not urgent. Work within 8 hours.
- **Low** - Minor issues or requests. Work within 24 hours.

---

## Ticket Workflow

Tickets move through several statuses during their lifecycle:

1. **Open** - New ticket waiting to be worked
2. **In Progress** - Technician actively working on the issue
3. **Waiting on Customer** - Needs information or action from client
4. **Waiting on Third Party** - Waiting for vendor or external party
5. **Resolved** - Issue fixed, waiting for customer confirmation
6. **Closed** - Ticket completed and confirmed by customer

---

## Time Tracking on Tickets

Track time spent on each ticket for accurate billing:

- **Start Timer** - Click the timer button to begin tracking
- **Manual Entry** - Add time manually with start/end times
- **Time Log** - View all time entries in the ticket's Time tab
- **Billable vs Non-Billable** - Mark entries as billable for invoicing

Learn more in the [Time Tracking](/docs/time-tracking) documentation.

---

## Comments & Updates

Keep everyone informed with ticket comments:

### Internal Notes

Private notes visible only to your team. Use these for internal communication about the ticket without notifying the client.

### Public Comments

Comments visible to the client. The customer receives an email notification when you add a public comment.

### @Mentions

Use @mention to notify specific team members. They'll receive a notification even if they're not assigned to the ticket.

---

## Attachments

Attach files to tickets for documentation:

- Screenshots and error messages
- Log files and diagnostic reports
- Configuration files
- Documentation and guides

> **File Size Limit**: Individual files must be under 25MB. For larger files, use a file sharing service and include the link in the ticket comments.

---

## SLA Management

Service Level Agreements ensure timely responses:

- **Response Time** - Time to first response
- **Resolution Time** - Time to resolve the issue
- **SLA Warnings** - Visual indicators when deadlines approach
- **Breach Notifications** - Alerts when SLAs are violated

Configure SLAs in the [Contract Management](/docs/contracts) section.

---

## Ticket Templates

Create templates for common issues to save time:

1. Go to Settings → Ticket Templates
2. Click **Create Template**
3. Define title, description, category, and priority
4. When creating tickets, select a template to pre-fill fields

---

## Automation Rules

Automate common ticket actions:

- Auto-assign tickets based on category or client
- Auto-escalate high priority tickets
- Send reminders for tickets waiting on customer
- Close resolved tickets after X days of inactivity

Configure automation in Settings → Ticket Automation.
