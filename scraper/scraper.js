#!/usr/bin/env node

/**
 * Google Maps Scraper
 *
 * Main scraper script using Puppeteer with anti-detection
 */

const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

// Add stealth plugin
puppeteer.use(StealthPlugin());

// Load environment variables
require('dotenv').config({ path: path.join(__dirname, '..', '.env') });

// Configuration
const config = {
    db: {
        host: process.env.DB_HOST || 'localhost',
        user: process.env.DB_USER || 'root',
        password: process.env.DB_PASS || '',
        database: process.env.DB_NAME || 'maps_monitor'
    },
    scraper: {
        headless: process.env.SCRAPER_HEADLESS !== 'false',
        timeout: parseInt(process.env.SCRAPER_TIMEOUT) || 300000,
        pageTimeout: 10000,
        maxRetries: parseInt(process.env.MAX_RETRIES) || 3,
        delayMin: parseInt(process.env.REQUEST_DELAY_MIN) || 2000,
        delayMax: parseInt(process.env.REQUEST_DELAY_MAX) || 5000,
        maxResultsPerPoint: 20
    },
    userAgents: [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15'
    ],
    viewports: [
        { width: 1920, height: 1080 },
        { width: 1366, height: 768 },
        { width: 1536, height: 864 },
        { width: 1440, height: 900 }
    ]
};

// Database connection pool
let dbPool;

/**
 * Initialize database connection
 */
async function initDatabase() {
    dbPool = await mysql.createPool(config.db);
    console.log('[DB] Connected to database');
}

/**
 * Log message to database and console
 */
async function log(level, category, message, context = {}) {
    const timestamp = new Date().toISOString().replace('T', ' ').substring(0, 19);
    console.log(`[${timestamp}] [${level}] [${category}] ${message}`);

    try {
        await dbPool.execute(
            'INSERT INTO logs (level, category, message, context, created_at) VALUES (?, ?, ?, ?, ?)',
            [level, category, message, JSON.stringify(context), timestamp]
        );
    } catch (err) {
        console.error('Failed to log to database:', err.message);
    }
}

/**
 * Generate grid points
 */
function generateGrid(centerLat, centerLng, radiusKm, gridSize) {
    const size = parseInt(gridSize.split('x')[0]);
    const latDegreeKm = 111.0;
    const lngDegreeKm = 111.0 * Math.cos(centerLat * Math.PI / 180);

    const latStep = (radiusKm * 2) / (size * latDegreeKm);
    const lngStep = (radiusKm * 2) / (size * lngDegreeKm);

    const startLat = centerLat - (latStep * (size - 1) / 2);
    const startLng = centerLng - (lngStep * (size - 1) / 2);

    const points = [];
    for (let i = 0; i < size; i++) {
        for (let j = 0; j < size; j++) {
            points.push({
                lat: parseFloat((startLat + (i * latStep)).toFixed(8)),
                lng: parseFloat((startLng + (j * lngStep)).toFixed(8)),
                row: i,
                col: j
            });
        }
    }

    return points;
}

/**
 * Random delay
 */
function randomDelay(min, max) {
    const delay = Math.floor(Math.random() * (max - min + 1)) + min;
    return new Promise(resolve => setTimeout(resolve, delay));
}

/**
 * Get random user agent
 */
function getRandomUserAgent() {
    return config.userAgents[Math.floor(Math.random() * config.userAgents.length)];
}

/**
 * Get random viewport
 */
function getRandomViewport() {
    return config.viewports[Math.floor(Math.random() * config.viewports.length)];
}

/**
 * Get next proxy
 */
async function getNextProxy() {
    try {
        const [rows] = await dbPool.execute(
            'SELECT * FROM proxies WHERE is_active = 1 ORDER BY last_used_at ASC LIMIT 1'
        );

        if (rows.length === 0) {
            return null;
        }

        const proxy = rows[0];

        // Update last used time
        await dbPool.execute(
            'UPDATE proxies SET last_used_at = NOW() WHERE id = ?',
            [proxy.id]
        );

        return proxy;
    } catch (err) {
        await log('ERROR', 'PROXY', 'Failed to get proxy', { error: err.message });
        return null;
    }
}

/**
 * Parse proxy URL
 */
function parseProxyUrl(proxyUrl) {
    const match = proxyUrl.match(/^(https?|socks5):\/\/(?:([^:@]+):([^@]+)@)?([\w\.\-]+):(\d+)$/);

    if (!match) {
        return null;
    }

    return {
        protocol: match[1],
        username: match[2] || null,
        password: match[3] || null,
        host: match[4],
        port: parseInt(match[5])
    };
}

/**
 * Launch browser with proxy
 */
async function launchBrowser(proxy = null) {
    const launchOptions = {
        headless: config.scraper.headless ? 'new' : false,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-blink-features=AutomationControlled'
        ]
    };

    if (proxy) {
        const parsed = parseProxyUrl(proxy.proxy_url);
        if (parsed) {
            launchOptions.args.push(`--proxy-server=${parsed.protocol}://${parsed.host}:${parsed.port}`);
        }
    }

    const browser = await puppeteer.launch(launchOptions);
    const page = await browser.newPage();

    // Set viewport
    const viewport = getRandomViewport();
    await page.setViewport(viewport);

    // Set user agent
    await page.setUserAgent(getRandomUserAgent());

    // Proxy authentication
    if (proxy) {
        const parsed = parseProxyUrl(proxy.proxy_url);
        if (parsed && parsed.username && parsed.password) {
            await page.authenticate({
                username: parsed.username,
                password: parsed.password
            });
        }
    }

    // Additional stealth measures
    await page.evaluateOnNewDocument(() => {
        // Override the navigator.webdriver property
        Object.defineProperty(navigator, 'webdriver', { get: () => false });

        // Override permissions
        const originalQuery = window.navigator.permissions.query;
        window.navigator.permissions.query = (parameters) => (
            parameters.name === 'notifications' ?
                Promise.resolve({ state: Notification.permission }) :
                originalQuery(parameters)
        );
    });

    return { browser, page };
}

/**
 * Parse place data from Google Maps
 */
async function parsePlaceData(element, page) {
    try {
        const data = await page.evaluate((el) => {
            const name = el.querySelector('a div[role="heading"]')?.textContent?.trim() ||
                         el.querySelector('div.fontHeadlineSmall')?.textContent?.trim() ||
                         '';

            const ratingText = el.querySelector('span[role="img"]')?.getAttribute('aria-label') || '';
            const ratingMatch = ratingText.match(/([\d\.]+)\s*stars?/i);
            const rating = ratingMatch ? parseFloat(ratingMatch[1]) : null;

            const reviewsMatch = ratingText.match(/(\d+[\d,]*)\s*reviews?/i);
            const reviewsCount = reviewsMatch ? parseInt(reviewsMatch[1].replace(/,/g, '')) : 0;

            const link = el.querySelector('a')?.href || '';
            const coordsMatch = link.match(/@([-\d\.]+),([-\d\.]+)/);
            const lat = coordsMatch ? parseFloat(coordsMatch[1]) : null;
            const lng = coordsMatch ? parseFloat(coordsMatch[2]) : null;

            return {
                name,
                rating,
                reviewsCount,
                lat,
                lng,
                placeUrl: link
            };
        }, element);

        return data;
    } catch (err) {
        return null;
    }
}

/**
 * Scrape grid point
 */
async function scrapeGridPoint(page, phrase, gridPoint, scanId, phraseId) {
    const url = `https://www.google.com/maps/search/${encodeURIComponent(phrase)}/@${gridPoint.lat},${gridPoint.lng},15z`;

    try {
        await page.goto(url, {
            waitUntil: 'networkidle2',
            timeout: config.scraper.pageTimeout
        });

        // Wait for results
        await page.waitForSelector('div[role="feed"]', { timeout: 5000 }).catch(() => null);

        // Small delay to ensure results are loaded
        await randomDelay(1000, 2000);

        // Get results
        const results = await page.$$('div[role="feed"] > div > a');

        const places = [];
        const maxResults = Math.min(results.length, config.scraper.maxResultsPerPoint);

        for (let i = 0; i < maxResults; i++) {
            const placeData = await parsePlaceData(results[i], page);

            if (placeData && placeData.name && placeData.lat && placeData.lng) {
                places.push({
                    phrase_id: phraseId,
                    scan_id: scanId,
                    place_name: placeData.name,
                    rating: placeData.rating,
                    reviews_count: placeData.reviewsCount,
                    latitude: placeData.lat,
                    longitude: placeData.lng,
                    place_url: placeData.placeUrl,
                    grid_point_lat: gridPoint.lat,
                    grid_point_lng: gridPoint.lng
                });
            }
        }

        // Save results to database
        for (const place of places) {
            await dbPool.execute(
                `INSERT INTO results
                 (phrase_id, scan_id, place_name, rating, reviews_count, latitude, longitude, place_url, grid_point_lat, grid_point_lng, found_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())`,
                [place.phrase_id, place.scan_id, place.place_name, place.rating, place.reviews_count,
                 place.latitude, place.longitude, place.place_url, place.grid_point_lat, place.grid_point_lng]
            );
        }

        await log('INFO', 'SCRAPER', `Scraped grid point (${gridPoint.lat}, ${gridPoint.lng})`, {
            phrase,
            results_found: places.length
        });

        return places.length;

    } catch (err) {
        await log('ERROR', 'SCRAPER', `Failed to scrape grid point`, {
            phrase,
            grid_point: gridPoint,
            error: err.message
        });

        // Take screenshot on error
        try {
            const screenshotPath = path.join(__dirname, '..', 'storage', 'screenshots',
                `error_${scanId}_${Date.now()}.png`);
            await page.screenshot({ path: screenshotPath, fullPage: false });
        } catch (screenshotErr) {
            // Ignore screenshot errors
        }

        throw err;
    }
}

/**
 * Run scan
 */
async function runScan(phraseId, scanId) {
    let browser = null;
    let totalResults = 0;
    let scannedPoints = 0;
    let failedPoints = 0;

    try {
        // Get phrase data
        const [phraseRows] = await dbPool.execute(
            'SELECT * FROM phrases WHERE id = ?',
            [phraseId]
        );

        if (phraseRows.length === 0) {
            throw new Error('Phrase not found');
        }

        const phrase = phraseRows[0];

        // Generate grid
        const gridPoints = generateGrid(
            parseFloat(phrase.location_lat),
            parseFloat(phrase.location_lng),
            parseInt(phrase.radius_km),
            phrase.grid_size
        );

        await log('INFO', 'SCRAPER', `Starting scan for phrase: ${phrase.phrase}`, {
            phrase_id: phraseId,
            scan_id: scanId,
            grid_points: gridPoints.length
        });

        // Update scan status
        await dbPool.execute(
            'UPDATE scan_history SET status = ?, total_grid_points = ? WHERE id = ?',
            ['running', gridPoints.length, scanId]
        );

        // Get proxy
        const proxy = await getNextProxy();

        // Launch browser
        const { browser: br, page } = await launchBrowser(proxy);
        browser = br;

        // Scan each grid point
        for (const gridPoint of gridPoints) {
            let success = false;
            let attempts = 0;

            while (!success && attempts < config.scraper.maxRetries) {
                attempts++;

                try {
                    const resultsCount = await scrapeGridPoint(page, phrase.phrase, gridPoint, scanId, phraseId);
                    totalResults += resultsCount;
                    scannedPoints++;
                    success = true;

                    // Update progress
                    await dbPool.execute(
                        'UPDATE scan_history SET scanned_points = ?, results_found = ? WHERE id = ?',
                        [scannedPoints, totalResults, scanId]
                    );

                } catch (err) {
                    await log('WARNING', 'SCRAPER', `Retry ${attempts}/${config.scraper.maxRetries} for grid point`, {
                        grid_point: gridPoint,
                        error: err.message
                    });

                    if (attempts >= config.scraper.maxRetries) {
                        failedPoints++;
                        break;
                    }

                    // Wait before retry
                    await randomDelay(5000, 10000);
                }
            }

            // Random delay between grid points
            await randomDelay(config.scraper.delayMin, config.scraper.delayMax);
        }

        // Mark scan as completed
        await dbPool.execute(
            'UPDATE scan_history SET status = ?, completed_at = NOW(), scanned_points = ?, failed_points = ?, results_found = ?, duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW()) WHERE id = ?',
            ['completed', scannedPoints, failedPoints, totalResults, scanId]
        );

        // Update phrase last scanned
        await dbPool.execute(
            'UPDATE phrases SET last_scanned_at = NOW() WHERE id = ?',
            [phraseId]
        );

        await log('INFO', 'SCRAPER', `Scan completed successfully`, {
            phrase_id: phraseId,
            scan_id: scanId,
            total_results: totalResults,
            scanned_points: scannedPoints,
            failed_points: failedPoints
        });

    } catch (err) {
        await log('CRITICAL', 'SCRAPER', `Scan failed`, {
            phrase_id: phraseId,
            scan_id: scanId,
            error: err.message,
            stack: err.stack
        });

        // Mark scan as failed
        await dbPool.execute(
            'UPDATE scan_history SET status = ?, error_message = ?, completed_at = NOW() WHERE id = ?',
            ['failed', err.message, scanId]
        );

    } finally {
        if (browser) {
            await browser.close();
        }
    }
}

/**
 * Main function
 */
async function main() {
    try {
        await initDatabase();

        // Get command line arguments
        const args = process.argv.slice(2);
        const phraseIdArg = args.find(arg => arg.startsWith('--phrase-id='));
        const scanIdArg = args.find(arg => arg.startsWith('--scan-id='));

        if (!phraseIdArg || !scanIdArg) {
            console.error('Usage: node scraper.js --phrase-id=<id> --scan-id=<id>');
            process.exit(1);
        }

        const phraseId = parseInt(phraseIdArg.split('=')[1]);
        const scanId = parseInt(scanIdArg.split('=')[1]);

        await runScan(phraseId, scanId);

        process.exit(0);

    } catch (err) {
        console.error('Fatal error:', err);
        await log('CRITICAL', 'SCRAPER', 'Fatal error', { error: err.message, stack: err.stack });
        process.exit(1);
    }
}

// Run
main();
