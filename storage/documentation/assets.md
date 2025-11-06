# Asset Management

Track equipment, inventory, warranties, and RMM integration. Learn how to manage all client assets, monitor their health, track warranties, and integrate with your RMM tools.

## Understanding Assets

Assets in Nestogy represent any piece of equipment or device you manage for clients:

- Servers and workstations
- Network equipment (routers, switches, access points)
- Mobile devices (phones, tablets)
- Printers and peripherals
- Software licenses
- Cloud services and subscriptions

---

## Adding Assets

To add a new asset:

1. Select a client using the client switcher
2. Navigate to **Assets** in the sidebar
3. Click **Add Asset**
4. Fill in asset details:
   - **Asset Name** - Descriptive name
   - **Asset Type** - Category (Server, Workstation, etc.)
   - **Make/Model** - Manufacturer and model number
   - **Serial Number** - Unique identifier
   - **Location** - Physical location or site
5. Click **Save**

### Quick Add from Discovery

Import assets automatically:

- RMM agent discovery
- Network scanning tools
- Active Directory import
- Bulk CSV upload

---

## Asset Information

Comprehensive data for each asset:

### Basic Details

- Asset name and description
- Asset type and category
- Make, model, and SKU
- Serial number
- Asset tag number
- MAC address
- IP address

### Ownership & Location

- Client assignment
- Physical location
- Assigned user
- Department
- Site/building/room

### Technical Specifications

- Operating system
- CPU, RAM, storage
- Network configuration
- Installed software
- Hardware components

---

## Asset Types

Organize assets by category:

### Computers

- **Servers** - Physical and virtual servers
- **Workstations** - Desktop computers
- **Laptops** - Portable computers
- **Thin Clients** - Terminal devices

### Network Equipment

- **Routers** - Network routers
- **Switches** - Network switches
- **Firewalls** - Security appliances
- **Access Points** - Wireless APs
- **Modems** - Internet gateways

### Mobile Devices

- **Smartphones** - Mobile phones
- **Tablets** - iPad, Android tablets
- **Wearables** - Smartwatches, etc.

### Peripherals

- **Printers** - Laser, inkjet, multifunction
- **Scanners** - Document scanners
- **Displays** - Monitors and screens
- **Storage** - External drives, NAS

### Software & Licenses

- Application licenses
- Operating system licenses
- Cloud subscriptions
- Support contracts

---

## Warranty Management

Track warranties and support contracts:

### Warranty Information

Store warranty details:

- Purchase date
- Warranty start date
- Warranty end date
- Warranty type (manufacturer, extended)
- Warranty provider
- Service level
- Coverage details

### Warranty Expiration Alerts

Automatic notifications:

- 90-day warning
- 60-day reminder
- 30-day final notice
- Email to designated staff
- Dashboard widget showing expiring warranties

### Warranty Claims

Track warranty service:

- Claim number
- Claim date
- Issue description
- Resolution status
- Replacement parts
- Service technician notes

---

## Asset Lifecycle

Manage assets from procurement to retirement:

### Procurement

Track new asset acquisitions:

- Purchase order number
- Vendor information
- Purchase date
- Cost and depreciation
- Delivery tracking

### Deployment

Document asset deployment:

- Deployment date
- Assigned location
- Assigned user
- Configuration applied
- Initial setup notes

### Maintenance

Schedule and track maintenance:

- Preventive maintenance schedules
- Maintenance history
- Service records
- Repair costs
- Downtime tracking

### Retirement

Decommission old assets:

- Retirement date
- Retirement reason
- Data wiping confirmation
- Disposal method
- Recycling certificate

---

## RMM Integration

Connect with Remote Monitoring and Management tools:

### Supported RMM Platforms

- **ConnectWise Automate** (LabTech)
- **Datto RMM** (Autotask Endpoint Management)
- **N-able N-central**
- **N-able RMM** (N-sight)
- **Kaseya VSA**
- **Syncro**

### Automated Asset Discovery

RMM agents automatically report:

- Hardware specifications
- Operating system details
- Installed applications
- Network configuration
- Performance metrics
- Security status

### Real-Time Monitoring

Monitor asset health:

- Online/offline status
- CPU and memory usage
- Disk space available
- Network connectivity
- Service status
- Security alerts

### Remote Management

Perform remote actions:

- Remote desktop access
- Command execution
- Software deployment
- Patch management
- System reboot
- File transfer

---

## Asset Relationships

Link related assets and services:

### Parent-Child Relationships

- Virtual machines linked to host servers
- Workstations linked to users
- Peripherals linked to computers
- Network devices in topology

### Dependencies

Map service dependencies:

- Critical path analysis
- Impact assessment
- Failover planning
- Disaster recovery

---

## Asset Documentation

Store important asset information:

### Attached Documents

Upload and organize files:

- Configuration documents
- User manuals
- Warranty certificates
- Service records
- Network diagrams
- Backup schedules

### Notes and Comments

Add contextual information:

- Setup notes
- Known issues
- Workarounds
- Contact information
- Access credentials (encrypted)

---

## Asset Reports

Generate comprehensive reports:

### Inventory Reports

- Complete asset inventory by client
- Assets by type
- Assets by location
- Assets by age
- Asset valuation

### Warranty Reports

- Expiring warranties (30/60/90 days)
- Expired warranties
- Warranty coverage gaps
- Warranty cost analysis

### Lifecycle Reports

- Asset age distribution
- Refresh recommendations
- End-of-life assets
- Depreciation schedules

### Compliance Reports

- Software license compliance
- Security patch status
- Configuration compliance
- Audit trail reports

---

## Asset Fields Customization

Add custom fields for your specific needs:

1. Go to Settings → Asset Fields
2. Click **Add Custom Field**
3. Configure field properties:
   - Field name
   - Field type (text, number, date, dropdown)
   - Required or optional
   - Default value
   - Help text
4. Apply to specific asset types
5. Field appears on asset forms

---

## Bulk Operations

Manage multiple assets efficiently:

### Bulk Import

Import assets via CSV:

- Download CSV template
- Fill in asset data
- Map columns to fields
- Validate and import
- Review import results

### Bulk Update

Update multiple assets at once:

- Select assets (checkboxes)
- Choose bulk action
- Update fields:
  - Location
  - Status
  - Warranty dates
  - Custom fields
- Apply changes

### Bulk Export

Export asset data:

- Select assets or export all
- Choose fields to include
- Export to CSV or PDF
- Use for reporting or backup

---

## Asset QR Codes

Generate QR codes for quick asset lookup:

### QR Code Generation

- Auto-generate QR code for each asset
- Print QR code labels
- Scan with mobile device
- Instant asset details

### Mobile Asset Management

Use Nestogy mobile app:

- Scan asset QR codes
- View asset details
- Update asset information
- Add photos
- Create tickets for assets

---

## Best Practices

Tips for effective asset management:

### Accurate Data

- Enter complete information
- Use consistent naming
- Update changes promptly
- Verify serial numbers
- Regular audits

### Organization

- Use consistent asset types
- Maintain location accuracy
- Document configurations
- Track all changes
- Link related items

### Proactive Management

- Monitor warranty expirations
- Plan refresh cycles
- Track total cost of ownership
- Anticipate capacity needs
- Budget for replacements

### Security

- Encrypt sensitive data
- Restrict access appropriately
- Audit asset access
- Track asset movements
- Secure disposal process

---

## Integration with Other Modules

Assets connect with other Nestogy features:

- **Tickets** - Asset-specific support tickets
- **Contracts** - Per-device pricing
- **Time Tracking** - Asset maintenance time
- **Invoicing** - Hardware and license billing
- **Projects** - Asset deployment projects

Learn more about [Tickets](/docs/tickets), [Contracts](/docs/contracts), and [Projects](/docs/projects).
