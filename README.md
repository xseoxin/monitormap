# Google Maps Monitor 🗺️

A comprehensive system for monitoring and tracking search phrases in Google Maps using automated grid-based scanning.

## 🌟 Features

- **Multi-Point Grid Scanning**: Scan Google Maps using customizable geographic grids (5x5, 7x7, 9x9)
- **Automated Scheduling**: Weekly/daily/monthly automatic scans
- **Proxy Management**: Built-in proxy rotation with health monitoring
- **Anti-Detection**: Puppeteer with stealth plugin to avoid detection
- **Real-time Visualization**: Interactive maps with Leaflet.js
- **Result Analytics**: Charts, comparisons, and historical tracking
- **Multi-User Support**: User accounts with role-based access
- **API Access**: RESTful API for integration
- **Export Functionality**: CSV, JSON, PDF exports

## 📋 Requirements

### Server Requirements
- **PHP**: 8.1 or higher
- **MySQL**: 8.0 or higher
- **Node.js**: 16.x or higher
- **Apache**: 2.4+ with mod_rewrite

### PHP Extensions
- PDO
- PDO MySQL
- cURL
- JSON
- MBString

### Node.js Packages
- puppeteer
- puppeteer-extra
- puppeteer-extra-plugin-stealth
- mysql2
- dotenv

## 🚀 Installation

### 1. Clone or Upload Files

Upload all files to your web server's root directory (not /public subdirectory).

### 2. Set Permissions

```bash
chmod 755 storage logs scraper
chmod 644 .env
```

### 3. Run Web Installer

Navigate to `http://yourdomain.com/install.php` and follow the installation wizard:

1. **Requirements Check**: Verify all system requirements
2. **Database Setup**: Configure database connection
3. **Admin Account**: Create your admin user
4. **Complete**: Get next steps

**⚠️ IMPORTANT**: Delete `install.php` after installation!

### 4. Install Node.js Dependencies

```bash
cd scraper
npm install
```

### 5. Configure Environment

Copy `.env.example` to `.env` and update values:

```bash
cp .env.example .env
nano .env
```

Key settings:
- Database credentials
- Application URL
- Node.js path
- Scraper path
- SMTP settings (optional)
- Telegram settings (optional)

### 6. Set Up Cron Jobs

Add these cron jobs to your server:

```cron
# Weekly scan (every Sunday at 2 AM)
0 2 * * 0 /usr/bin/php /path/to/cron_weekly_scan.php

# Proxy health check (every hour)
0 * * * * /usr/bin/php /path/to/cron_proxy_health.php

# Log cleanup (daily at 3 AM)
0 3 * * * /usr/bin/php /path/to/cron_cleanup_logs.php

# Old results cleanup (weekly on Sunday at 4 AM)
0 4 * * 0 /usr/bin/php /path/to/cron_cleanup_old_results.php
```

## 📚 Usage

### Adding Phrases

1. Log in to your account
2. Navigate to **Phrases** → **Add Phrase**
3. Enter your search phrase (e.g., "coffee shop in Warsaw")
4. Select location on map or enter coordinates
5. Set radius (1-50 km)
6. Choose grid size (5x5, 7x7, or 9x9)
7. Set scan frequency
8. Save

### Managing Proxies

1. Navigate to **Proxies**
2. Click **Add Proxy**
3. Enter proxy URL format: `http://user:pass@ip:port`
4. Select proxy type (HTTP, HTTPS, SOCKS5)
5. Add country (optional)
6. Save

You can also import proxies via CSV file.

### Running Scans

**Manual Scan:**
- Go to Phrases list
- Click "Scan Now" button next to a phrase

**Automatic Scans:**
- Scans run automatically based on schedule
- Configure frequency when adding/editing phrases
- Weekly scans run via cron job

### Viewing Results

**Dashboard:**
- Overview of recent scans and statistics
- Mini map with latest results
- Active phrases with next scan times

**Results Page:**
- Full list of all results
- Filterable by phrase, date, rating
- Sortable columns
- Export options

**Map View:**
- Interactive full-screen map
- Color-coded markers by rating (green: >4★, orange: 3-4★, red: <3★)
- Clustering for many results
- Filter by phrase, date range, rating

**History:**
- View all past scans
- Detailed scan information
- Compare two scans to see changes
- Grid visualization showing which points were scanned

## 🏗️ Project Structure

```
monitormap/
├── assets/               # Frontend assets
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   ├── images/          # Images and icons
│   └── vendor/          # Third-party libraries
├── classes/             # PHP classes
│   ├── Auth.php
│   ├── Database.php
│   ├── GridGenerator.php
│   ├── Logger.php
│   ├── Phrase.php
│   ├── Proxy.php
│   ├── ProxyManager.php
│   ├── RateLimiter.php
│   └── ...
├── config/              # Configuration files
│   ├── app.php
│   ├── database.php
│   ├── logging.php
│   └── ...
├── includes/            # Shared includes
│   ├── auth_check.php
│   ├── footer.php
│   ├── functions.php
│   ├── header.php
│   └── init.php
├── logs/                # Application logs
│   ├── app/
│   ├── auth/
│   ├── proxy/
│   └── ...
├── migrations/          # Database migrations
├── scraper/             # Node.js scraper
│   ├── package.json
│   └── scraper.js
├── storage/             # File storage
│   ├── exports/
│   ├── screenshots/
│   └── temp/
├── .env                 # Environment variables
├── .htaccess           # Apache configuration
├── index.php           # Dashboard
├── login.php           # Login page
├── phrases.php         # Phrases management
└── cron_*.php          # Cron job scripts
```

## 🔧 Configuration

### Grid Generator Algorithm

The system uses a geographic grid algorithm to create scan points:

1. Calculate lat/lng degree in km based on location
2. Determine step size based on radius and grid size
3. Generate points in a grid pattern around center
4. Each point becomes a separate Google Maps search

Example: 5x5 grid with 10km radius = 25 scan points

### Proxy Rotation

Three strategies available:
- **Round-robin**: Use each proxy in sequence
- **Random**: Randomly select proxy
- **Fastest**: Always use proxy with best response time

### Anti-Detection Features

- Puppeteer Stealth plugin
- Random user agents
- Random viewport sizes
- Request delays (2-5 seconds)
- Proxy rotation
- No automation flags

## 🔐 Security

### Best Practices

1. **Delete install.php** after installation
2. Use strong passwords (minimum 8 characters)
3. Keep PHP and Node.js updated
4. Use HTTPS in production
5. Configure firewall rules
6. Restrict database access
7. Regular backups
8. Monitor logs for suspicious activity

### Security Features

- Password hashing (bcrypt)
- CSRF protection
- SQL injection prevention (PDO prepared statements)
- XSS protection (output escaping)
- Rate limiting (login, API, scans)
- Session security (HTTP-only cookies)
- IP tracking

## 📊 API Documentation

### Authentication

Use API token in header:
```
Authorization: Bearer YOUR_API_TOKEN
```

Get your API token from Settings page.

### Endpoints

**GET /api/phrases.php**
- List user's phrases
- Params: `page`, `limit`, `active`

**POST /api/phrases.php**
- Create new phrase
- Body: `{phrase, lat, lng, radius_km, grid_size}`

**GET /api/results.php**
- Get scan results
- Params: `phrase_id`, `date_from`, `date_to`, `rating_min`

**POST /api/scan.php**
- Start manual scan
- Body: `{phrase_id}`

**GET /api/stats.php**
- Get user statistics

Rate limit: 100 requests/hour per token

## 🐛 Troubleshooting

### Scraper Issues

**Problem**: Scraper fails immediately
- Check Node.js installation: `node --version`
- Verify scraper path in `.env`
- Check file permissions
- Review logs in `/logs/scraper/`

**Problem**: No results found
- Verify phrase and location are valid
- Check proxy functionality
- Google Maps may have rate limited your IP
- Try different proxies

### Database Issues

**Problem**: Cannot connect to database
- Verify credentials in `.env`
- Check MySQL is running
- Verify user has correct permissions
- Check firewall rules

### Proxy Issues

**Problem**: All proxies deactivated
- Test proxies manually
- Check proxy format is correct
- Verify proxy authentication
- Try different proxy providers

### Permission Issues

**Problem**: Cannot write logs/storage
```bash
chmod -R 755 logs storage
chown -R www-data:www-data logs storage
```

## 🔄 Updates

To update the system:

1. Backup database and files
2. Replace files (keep `.env` and `storage/`)
3. Run any new migrations
4. Clear cache/temp files
5. Test functionality

## 📝 License

This project is licensed under the MIT License.

## 🤝 Support

For issues and questions:
- Check troubleshooting section
- Review logs in `/logs/`
- Check documentation in `/docs/`

## ⚠️ Disclaimer

This tool is for educational and research purposes. Make sure you comply with Google Maps Terms of Service and applicable laws. Use proxies and reasonable delays to avoid overloading Google's servers.

## 🎯 Roadmap

- [ ] API rate limiting per user
- [ ] Advanced result comparison
- [ ] Email reports
- [ ] Multi-language support
- [ ] Mobile app
- [ ] Real-time scan progress
- [ ] Advanced analytics dashboard
- [ ] Integration with Google My Business API

---

Made with ❤️ for location intelligence
