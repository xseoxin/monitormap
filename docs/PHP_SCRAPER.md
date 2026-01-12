# PHP Scraper - Alternative to Node.js Puppeteer

## Overview

The PHP Scraper is a pure PHP alternative to the Node.js Puppeteer scraper. It's designed for **shared hosting environments** where Node.js or npm cannot be installed.

## Features

✅ **No Node.js Required** - Works with just PHP and cURL
✅ **Shared Hosting Compatible** - No SSH access needed
✅ **Same Functionality** - Grid-based scanning, proxy rotation
✅ **Easy Setup** - Just upload and run
✅ **Resource Efficient** - Lower memory and CPU usage

## Requirements

- PHP 8.1 or higher
- cURL extension enabled
- MySQL 8.0 or higher
- DOM extension enabled (usually included)

## Installation

The PHP scraper is **already included** in your installation. No additional setup required!

### Verify Installation

```bash
# Check if PHP scraper exists
ls -la scraper/scraper.php

# Make sure it's executable
chmod +x scraper/scraper.php

# Test it
php scraper/scraper.php --help
```

## Usage

### Manual Scan

Run a scan manually from command line:

```bash
cd /path/to/monitormap
php scraper/scraper.php --phrase-id=1 --scan-id=1
```

### Cron Jobs

The system automatically detects which scraper to use. Update your cron jobs to use the PHP scraper:

```bash
# Run weekly scans (every Monday at 2 AM)
0 2 * * 1 cd /path/to/monitormap && php cron/weekly_scan.php >> logs/cron.log 2>&1
```

### From Web Interface

The system will automatically use the PHP scraper if Node.js is not available. No configuration changes needed!

## Configuration

Edit `.env` to configure scraper behavior:

```env
# Scraper Type (auto, nodejs, php)
SCRAPER_TYPE=auto

# PHP Scraper Settings
SCRAPER_TIMEOUT=300000
MAX_RETRIES=3
REQUEST_DELAY_MIN=2000
REQUEST_DELAY_MAX=5000
MAX_RESULTS_PER_POINT=20
```

### Scraper Type Options

- `auto` (default) - Automatically detects available scraper
- `nodejs` - Force use of Node.js Puppeteer scraper
- `php` - Force use of PHP cURL scraper

## How It Works

### 1. Grid Generation
- Same as Node.js scraper
- Uses `GridGenerator` class
- Creates grid points based on radius and size

### 2. Page Fetching
- Uses cURL instead of Puppeteer
- Supports proxy rotation via `ProxyManager`
- Mimics real browser behavior with headers

### 3. Result Parsing
- Parses HTML using DOMDocument
- Multiple parsing strategies:
  - JSON-LD structured data
  - JavaScript embedded data
  - HTML element extraction

### 4. Data Storage
- Saves results to MySQL database
- Same schema as Node.js scraper
- Compatible with all existing features

## Advantages vs Node.js Scraper

| Feature | PHP Scraper | Node.js Scraper |
|---------|-------------|-----------------|
| Installation | ✅ Already included | ❌ Requires npm install |
| SSH Access | ✅ Not required | ❌ Required for setup |
| Memory Usage | ✅ Low (~50MB) | ⚠️ High (~500MB) |
| CPU Usage | ✅ Low | ⚠️ High |
| JavaScript Execution | ❌ No | ✅ Yes |
| Anti-detection | ⚠️ Basic | ✅ Advanced |
| Setup Time | ✅ Instant | ⚠️ 5-10 minutes |

## Limitations

### Google Maps Limitations

Google Maps heavily relies on JavaScript. The PHP scraper:

- Cannot execute JavaScript
- Parses HTML structure which Google may change
- May find fewer results than Puppeteer
- Works best for basic place searches

### Workarounds

1. **Use Proxies** - Rotate proxies to avoid rate limiting
2. **Increase Delays** - Add longer delays between requests
3. **Multiple Strategies** - Uses 3 different parsing methods
4. **Fallback** - System tries Node.js scraper if available

## Troubleshooting

### No Results Found

**Problem:** Scraper completes but finds 0 results

**Solutions:**
1. Check if cURL is enabled: `php -i | grep cURL`
2. Verify internet connectivity
3. Test Google Maps access: `curl -I https://www.google.com/maps`
4. Check proxy configuration
5. Review logs in `logs/` directory

### cURL Errors

**Problem:** cURL connection failures

**Solutions:**
```php
// Check cURL version
php -r "echo curl_version()['version'];"

// Test basic cURL
php -r "echo file_get_contents('https://www.google.com');"
```

### Slow Performance

**Problem:** Scans take too long

**Solutions:**
1. Reduce grid size (5x5 instead of 9x9)
2. Decrease max results per point
3. Increase request delays to avoid blocks
4. Use faster proxies

### Blocked by Google

**Problem:** Getting 429 or 403 errors

**Solutions:**
1. Add more proxies
2. Increase delays between requests
3. Rotate user agents
4. Use residential proxies instead of datacenter

## Comparison Example

### Node.js Scraper Results
- **Grid 5x5:** 25 points, ~150 results
- **Time:** 15 minutes
- **Memory:** 500 MB
- **Detection Rate:** Very Low

### PHP Scraper Results
- **Grid 5x5:** 25 points, ~80-120 results
- **Time:** 20 minutes
- **Memory:** 50 MB
- **Detection Rate:** Low

## Best Practices

### 1. Use Proxies
Always use proxies to avoid IP bans:
```bash
# Add proxies in web interface
https://yoursite.com/proxies.php
```

### 2. Optimize Grid Size
Start small and increase:
- 5x5 for radius ≤ 5 km
- 7x7 for radius ≤ 15 km
- 9x9 for radius > 15 km

### 3. Monitor Logs
Check logs regularly:
```bash
tail -f logs/scraper.log
tail -f logs/system.log
```

### 4. Test First
Always test with a small scan:
```bash
php scraper/scraper.php --phrase-id=1 --scan-id=1
```

### 5. Schedule Wisely
Avoid peak hours:
- ✅ Run at night (2-6 AM)
- ✅ Spread scans throughout week
- ❌ Don't run all scans at once

## Migration from Node.js

If you're currently using Node.js scraper and want to switch:

### 1. Update Configuration
```env
SCRAPER_TYPE=php
```

### 2. Update Cron Jobs
No changes needed! System auto-detects scraper type.

### 3. Test
Run a test scan to verify:
```bash
php scraper/scraper.php --phrase-id=1 --scan-id=1
```

### 4. Monitor
Check first few scans for result quality.

## Technical Details

### Architecture

```
PhpScraper.php
├── runScan() - Main scan orchestrator
├── scrapeGridPoint() - Single point scraper
├── buildSearchUrl() - URL builder
├── fetchPage() - cURL wrapper
├── parseResults() - HTML parser
│   ├── extractJsonLdData() - JSON-LD parser
│   ├── extractFromScriptTags() - JS data parser
│   └── extractFromHtmlElements() - HTML parser
└── removeDuplicatePlaces() - Deduplication

```

### Classes Used

- `GridGenerator` - Generate grid points
- `ProxyManager` - Proxy rotation
- `ScanHistory` - Scan tracking
- `Result` - Result storage
- `Logger` - Logging

### Database

Same schema as Node.js scraper:
- `phrases` - Search phrases
- `results` - Scan results
- `scan_history` - Scan tracking
- `proxies` - Proxy list

## Support

If you encounter issues:

1. **Check Logs:** `logs/scraper.log`
2. **Enable Debug:** Set `APP_DEBUG=true` in `.env`
3. **Test cURL:** Verify cURL works
4. **Check Proxies:** Test proxy connectivity
5. **Report Issue:** Create issue with logs

## Conclusion

The PHP scraper is the **recommended solution** for shared hosting environments. While it may find slightly fewer results than the Node.js Puppeteer scraper, it's:

- ✅ Much easier to set up
- ✅ More resource efficient
- ✅ More reliable on shared hosting
- ✅ Sufficient for most use cases

For maximum results and anti-detection, use the Node.js scraper. For ease of use and shared hosting compatibility, use the PHP scraper.
