# Database Setup Instructions

## Required Database Changes

To use the new Security and Addresses pages, you need to run the SQL file to create the required database tables and columns.

### Steps:

1. **Open phpMyAdmin** (or your preferred MySQL management tool)

2. **Select your database** (usually the database name for your HORAASTORE project)

3. **Import the SQL file:**
   - Go to the "Import" tab
   - Click "Choose File" and select: `sql/user_addresses_and_security.sql`
   - Click "Go" to execute

### What this adds:

1. **user_addresses table** - Stores user shipping/billing addresses
2. **user_activity_log table** - Tracks security events and password changes
3. **Additional columns to users table:**
   - `two_factor_enabled` - For 2FA settings
   - `email_notifications` - For email notification preferences
   - `login_alerts` - For login alert settings

### Alternative Manual Setup:

If you prefer to run the SQL manually, copy the contents of `sql/user_addresses_and_security.sql` and execute it in your MySQL console.

---

## New Features Added:

✅ **Security Page** (`/user/security.php`)
- Password change functionality moved from profile
- Security preferences (2FA, notifications, login alerts)
- Security tips and best practices
- Premium responsive design

✅ **Addresses Page** (`/user/addresses.php`)
- Add, edit, delete shipping/billing addresses
- Set default addresses by type
- Support for home, work, billing, shipping, other address types
- Premium responsive design with animations

✅ **Updated Profile Navigation**
- Security link now redirects to dedicated security page
- Addresses link points to new addresses page
- Cleaner profile page focused on personal information only

All pages maintain the premium design with:
- Gradient backgrounds
- Glass morphism effects
- Smooth animations
- Mobile-responsive layout
- Professional styling with Poppins/Nunito fonts
