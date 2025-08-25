# 🚂 Railway Deployment Guide for HORAASTORE

## Step-by-Step Railway Deployment

### 1. 📋 Prerequisites
- ✅ GitHub account
- ✅ Railway account (connected to GitHub)
- ✅ MySQL service created in Railway (Done!)

### 2. 🔧 Environment Variables Setup

In your Railway project dashboard, add these variables:

#### Required Variables:
```
MYSQL_URL = ${{ MySQL.MYSQL_URL }}
SITE_URL = https://your-project-name.up.railway.app
SITE_NAME = HORAASTORE
ADMIN_EMAIL = admin@horaastore.com
```

#### Email Configuration:
```
SMTP_HOST = smtp.gmail.com
SMTP_PORT = 587
SMTP_USER = bishwasghimire2060@gmail.com
SMTP_PASS = clxygljjpmuvkhcr
SMTP_FROM = bishwasghimire2060@gmail.com
SMTP_FROM_NAME = HORAASTORE
```

#### Security:
```
JWT_SECRET = your_very_long_random_string_here_make_it_at_least_32_characters
```

### 3. 📤 Deploy to Railway

#### Option A: GitHub Integration (Recommended)
1. Push your code to GitHub:
```bash
git init
git add .
git commit -m "Deploy HORAASTORE to Railway"
git branch -M main
git remote add origin https://github.com/yourusername/horaastore.git
git push -u origin main
```

2. In Railway dashboard:
   - Click "New Project"
   - Select "Deploy from GitHub repo"
   - Choose your HORAASTORE repository
   - Railway will automatically detect it's a PHP project

#### Option B: Railway CLI
```bash
npm install -g @railway/cli
railway login
railway init
railway up
```

### 4. 🗄️ Database Setup

After deployment, run the setup script:
1. Visit: `https://your-project-name.up.railway.app/railway_setup.php`
2. This will create all tables and default admin user

### 5. 🔐 Access Your Site

- **Frontend**: `https://your-project-name.up.railway.app`
- **Admin Panel**: `https://your-project-name.up.railway.app/admin/login.php`
- **Default Admin**: 
  - Username: `admin`
  - Password: `admin123`

### 6. ⚙️ Post-Deployment Configuration

1. **Change Admin Password**: Login and go to Settings
2. **Update Site URL**: Ensure SITE_URL matches your Railway domain
3. **Test Email**: Verify SMTP settings work
4. **Upload Images**: Test product image uploads

### 🔧 Important Railway Settings

#### Environment Variables You Already Have:
- ✅ `MYSQL_URL` - Automatically provided by Railway MySQL service

#### Environment Variables You Need to Add:
- `SITE_URL` - Your Railway app URL
- `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS` - Email configuration
- `JWT_SECRET` - Security token

### 📁 File Storage Note

Railway uses ephemeral storage. For production:
- Consider using cloud storage (AWS S3, Cloudinary) for product images
- Current setup stores uploads temporarily (will be lost on restart)

### 🐛 Troubleshooting

#### Database Connection Issues:
- Verify `MYSQL_URL` variable is set correctly
- Check Railway MySQL service is running

#### Site Not Loading:
- Check Railway logs in dashboard
- Verify `SITE_URL` environment variable

#### Admin Login Issues:
- Run `/railway_setup.php` to recreate admin user
- Check database tables were created

### 🚀 Your Railway Deployment is Ready!

Once deployed, your HORAASTORE will be:
- ✅ Automatically built and deployed
- ✅ Connected to Railway MySQL
- ✅ Accessible via HTTPS
- ✅ Auto-deployed on GitHub pushes
- ✅ Scalable and monitored
