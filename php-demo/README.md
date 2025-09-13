# PHP Contact Form Demo

This folder contains a PHP contact form demonstration for the Bishwas Ghimire portfolio website.

## Important Note

**GitHub Pages cannot run PHP code.** This PHP demo is provided for educational purposes and to demonstrate backend capabilities. To use this contact form, you need to deploy it on a PHP-enabled hosting service.

## Features

- ✅ Input validation and sanitization
- ✅ Rate limiting protection
- ✅ Spam detection
- ✅ reCAPTCHA support (optional)
- ✅ Email templates with HTML formatting
- ✅ PHPMailer integration support
- ✅ Security headers
- ✅ Error logging
- ✅ CORS support

## Setup Instructions

### 1. Environment Configuration

Create a `.env` file in this directory with your email settings:

```env
# SMTP Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_ENCRYPTION=tls

# Email Addresses
FROM_EMAIL=noreply@yourdomain.com
TO_EMAIL=bishwas.ghimire@example.com

# reCAPTCHA (optional)
RECAPTCHA_SECRET=your-recaptcha-secret-key
```

### 2. Hosting Options

#### Free Hosting
- **000webhost.com** - Free PHP hosting with MySQL
- **InfinityFree** - Free PHP hosting
- **AwardSpace** - Free hosting with PHP support
- **ByetHost** - Free PHP hosting

#### Paid Hosting
- **Shared Hosting** - Any cPanel hosting provider
- **VPS/Cloud** - DigitalOcean, Linode, AWS EC2
- **Managed Hosting** - SiteGround, Bluehost, HostGator

### 3. Upload Files

Upload these files to your PHP hosting:
- `contact.php`
- `.env` (with your configuration)
- `composer.json` (if using PHPMailer)

### 4. Install Dependencies (Optional)

For better email delivery, install PHPMailer:

```bash
composer install
```

Or download PHPMailer manually and extract to `vendor/` folder.

### 5. Update HTML Form

Update the form action in your HTML to point to your PHP host:

```html
<form action="https://your-php-host.com/contact.php" method="POST">
```

## Security Features

### Input Validation
- Sanitizes all input data
- Validates email format
- Checks string lengths
- Filters HTML tags

### Rate Limiting
- Maximum 5 submissions per 5 minutes per session
- Prevents spam attacks
- Automatic cleanup of old attempts

### Spam Protection
- Keyword filtering
- Content analysis
- reCAPTCHA integration
- IP logging

### Security Headers
- XSS protection
- Content type validation
- Frame options
- CORS configuration

## Testing

### Local Testing (XAMPP/WAMP)

1. Install XAMPP or WAMP
2. Place files in `htdocs/` or `www/` directory
3. Configure `.env` with your email settings
4. Access via `http://localhost/contact.php`

### Production Testing

1. Deploy to your PHP hosting
2. Test with real form submissions
3. Check email delivery
4. Verify error handling

## Troubleshooting

### Email Not Sending

1. **Check SMTP settings** - Verify host, port, credentials
2. **Enable less secure apps** - For Gmail, use App Passwords
3. **Check spam folder** - Emails might be filtered
4. **Review error logs** - Check server error logs
5. **Test with different provider** - Try different SMTP service

### Common Issues

**"Method not allowed"**
- Ensure form uses POST method
- Check CORS settings

**"Too many requests"**
- Wait 5 minutes between submissions
- Clear browser session

**"reCAPTCHA failed"**
- Add reCAPTCHA to your form
- Configure secret key in .env

## Email Providers

### Gmail Setup
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_ENCRYPTION=tls
```

### Outlook/Hotmail
```env
SMTP_HOST=smtp-mail.outlook.com
SMTP_PORT=587
SMTP_USERNAME=your-email@outlook.com
SMTP_PASSWORD=your-password
SMTP_ENCRYPTION=tls
```

### Yahoo Mail
```env
SMTP_HOST=smtp.mail.yahoo.com
SMTP_PORT=587
SMTP_USERNAME=your-email@yahoo.com
SMTP_PASSWORD=your-password
SMTP_ENCRYPTION=tls
```

## Integration with Main Site

For the main GitHub Pages site, use a service like:
- **Formspree** - Easy form backend
- **Netlify Forms** - If using Netlify
- **Getform** - Form backend service
- **FormSubmit** - Simple form handling

Update the main site's form action:
```html
<form action="https://formspree.io/f/YOUR_FORM_ID" method="POST">
```

## File Structure

```
php-demo/
├── contact.php          # Main contact form handler
├── .env.example         # Environment template
├── .env                 # Your configuration (not in git)
├── composer.json        # PHP dependencies
├── vendor/              # PHPMailer (if installed)
└── README.md           # This file
```

## Security Best Practices

1. **Never commit .env file** - Add to .gitignore
2. **Use HTTPS** - Always use SSL encryption
3. **Regular updates** - Keep PHP and libraries updated
4. **Monitor logs** - Check for suspicious activity
5. **Backup regularly** - Backup your configuration

## License

This demo is part of the Bishwas Ghimire portfolio project and is provided for educational purposes.