# Email System

Manage email accounts, inbox, and email-to-ticket conversion. Learn how to integrate your email, automatically create tickets from emails, and streamline client communication.

## Understanding Email Integration

Nestogy's email system provides:

- Shared inbox for team collaboration
- Automatic email-to-ticket conversion
- Email tracking and threading
- Template-based responses
- Client communication history
- Full-text email search

---

## Setting Up Email Accounts

Connect your email accounts to Nestogy:

### IMAP Configuration

Connect existing email accounts:

1. Navigate to Settings → Email → Accounts
2. Click **Add Email Account**
3. Enter account details:
   - **Email Address** - full email address
   - **IMAP Server** - incoming mail server
   - **IMAP Port** - usually 993
   - **Username** - email username
   - **Password** - email password
   - **Use SSL** - enable for security
4. Click **Test Connection**
5. Save account

### Common IMAP Settings

**Microsoft 365/Outlook:**
- Server: outlook.office365.com
- Port: 993
- SSL: Yes

**Gmail:**
- Server: imap.gmail.com
- Port: 993
- SSL: Yes
- Note: Enable "Less secure apps" or use App Password

**Other Providers:**
- Consult your email provider's documentation

### Multiple Accounts

Connect multiple email accounts:

- Support email (support@company.com)
- Sales email (sales@company.com)
- Department emails
- Individual technician emails
- Monitored client emails

---

## Email-to-Ticket Conversion

Automatically create tickets from incoming emails:

### Automatic Conversion

Configure automatic ticket creation:

1. Go to Settings → Email → Ticket Rules
2. Click **Add Rule**
3. Define conditions:
   - Which email accounts
   - Subject line patterns
   - Sender domains
   - Specific addresses
4. Set ticket properties:
   - Default priority
   - Default category
   - Auto-assign rules
   - Client matching
5. Enable rule

### Email Parsing

Smart email processing:

- Extract ticket number from subject
- Match sender to client contact
- Parse email signature
- Remove email chains
- Extract attachments
- Identify urgency keywords

### Reply Threading

Maintain email threads as ticket conversations:

- Replies update existing tickets
- Ticket number in subject line
- Automatic thread detection
- Conversation history
- Inline image support

---

## Shared Inbox

Collaborative email management:

### Inbox Views

Organize incoming email:

- **All Mail** - Complete inbox
- **Unassigned** - Emails needing assignment
- **My Mail** - Emails assigned to you
- **Flagged** - Important/follow-up emails
- **Archived** - Processed emails

### Email Assignment

Assign emails to team members:

- Drag email to team member
- Right-click assign menu
- Bulk assignment
- Assignment notifications
- Load balancing

### Email Status

Track email processing:

- **New** - Unread email
- **Read** - Opened but not processed
- **Assigned** - Assigned to team member
- **In Progress** - Being worked on
- **Converted** - Converted to ticket
- **Archived** - Processed and archived

---

## Sending Emails

Send emails from within Nestogy:

### Composing Emails

Create new emails:

1. Click **Compose**
2. Select From address
3. Enter recipient(s)
4. Write subject and body
5. Add attachments (optional)
6. Click **Send**

### Email Templates

Use pre-written templates:

- Common responses
- Service notifications
- Quote templates
- Follow-up emails
- Onboarding emails

Create templates in Settings → Email → Templates.

### Email Signatures

Professional email signatures:

- Company branding
- Contact information
- Legal disclaimers
- Social media links
- Support hours

Configure in Settings → Email → Signatures.

---

## Email Templates

Standardize common communications:

### Template Types

**Support Templates:**
- Ticket received confirmation
- Ticket resolved notification
- Waiting on information
- Scheduled maintenance
- Service restoration

**Business Templates:**
- Meeting follow-ups
- Quote delivery
- Contract renewal
- Payment reminders
- Thank you notes

### Template Variables

Dynamic content insertion:

- `{{client.name}}` - Client company name
- `{{contact.name}}` - Contact person name
- `{{ticket.number}}` - Ticket number
- `{{technician.name}}` - Assigned technician
- `{{invoice.amount}}` - Invoice total
- `{{contract.end_date}}` - Contract end date

### Rich Text Formatting

Professional email formatting:

- Bold, italic, underline
- Bullet and numbered lists
- Hyperlinks
- Images and logos
- Tables
- Color and fonts

---

## Email Rules

Automate email processing:

### Rule Conditions

Trigger rules based on:

- Sender address or domain
- Subject line keywords
- Body content keywords
- Attachments present
- Time received
- Email size

### Rule Actions

Automatically:

- Create ticket
- Assign to person/team
- Set priority
- Add tags
- Move to folder
- Forward to address
- Delete/archive

---

## Email Tracking

Monitor email interactions:

### Read Receipts

Track email opens:

- When email was opened
- How many times opened
- Device and location
- Link clicks
- Attachment downloads

### Email Analytics

Measure performance:

- Average response time
- Emails per day/week
- Busiest hours
- Resolution time
- First response time
- Team performance

---

## Client Communication History

Complete email archive per client:

### Communication Log

View all client emails:

- Chronological timeline
- Sent and received
- Associated tickets
- Team member involved
- Attachments included
- Search and filter

### Email Export

Export email communications:

- Single email export
- Bulk export by date range
- PDF format
- EML format (original)
- Compliance and legal holds

---

## Spam and Filtering

Manage unwanted email:

### Spam Detection

Automatic spam filtering:

- Bayesian filtering
- Blacklist checking
- SPF/DKIM verification
- Content analysis
- Sender reputation

### Whitelist/Blacklist

Manage allowed and blocked senders:

- Client domains whitelist
- Known spam blacklist
- Approved senders
- Blocked addresses
- Pattern matching

### Email Quarantine

Review suspected spam:

- Quarantine review queue
- Release false positives
- Confirm spam
- Train spam filter
- Regular cleanup

---

## Mobile Email Access

Access email on mobile devices:

### Mobile App

Nestogy mobile app features:

- Full inbox access
- Send and receive
- Template use
- Ticket conversion
- Push notifications
- Offline read

### Email Notifications

Stay informed:

- New email alerts
- Assigned email notifications
- Urgent email flags
- Customizable rules
- Quiet hours

---

## Email Security

Protect sensitive communications:

### Encryption

Secure email transmission:

- TLS/SSL for IMAP/SMTP
- End-to-end encryption (optional)
- S/MIME support
- PGP support (enterprise)

### Access Control

Restrict email access:

- Role-based permissions
- Account-level access
- Read-only access
- Send restrictions
- Admin controls

### Audit Logging

Track email system usage:

- Login attempts
- Emails sent/received
- Template usage
- Rule changes
- Settings modifications

---

## Integration with Tickets

Seamless ticket integration:

### From Email to Ticket

- Automatic ticket creation
- Email becomes first comment
- Attachments transferred
- Sender becomes requester
- Subject becomes ticket title

### From Ticket to Email

- Reply via email
- Email notifications
- CC additional recipients
- Attach files to email
- Track delivery status

---

## Best Practices

Tips for effective email management:

### Organization

- Use folders wisely
- Archive regularly
- Tag and categorize
- Maintain clean inbox
- Regular cleanup

### Response Times

- Set response SLAs
- Use auto-responders
- Prioritize urgent emails
- Schedule email times
- Avoid email overload

### Templates

- Cover common scenarios
- Keep concise
- Professional tone
- Include necessary info
- Test before use

### Security

- Never share credentials
- Use strong passwords
- Enable 2FA where possible
- Be wary of phishing
- Report suspicious emails

---

## Troubleshooting

Common email issues and solutions:

### Cannot Connect to Email Server

- Verify server settings
- Check username/password
- Confirm firewall rules
- Test from different network
- Contact email provider

### Emails Not Converting to Tickets

- Review ticket rules
- Check email parsing
- Verify sender matching
- Test rule conditions
- Review logs

### Missing Emails

- Check spam/quarantine
- Verify email rules
- Review archive
- Check server retention
- Contact support

---

## Integration with Other Modules

Email connects with other Nestogy features:

- **Tickets** - Email-to-ticket conversion
- **Clients** - Communication history
- **Projects** - Project updates
- **Invoicing** - Invoice delivery
- **Time Tracking** - Time entry notifications

Learn more about [Tickets](/docs/tickets) and [Clients](/docs/clients).
