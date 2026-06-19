const { chromium } = require("playwright");
const mysql = require("mysql2/promise");

async function run() {
    const keywords = ["homedecor", "interiordesign", "boho", "minimalist"];

    const db = await mysql.createConnection({
        host: "127.0.0.1",
        user: "root",
        password: "",
        database: "your_db_name"
    });

    const browser = await chromium.launch({ headless: true });

    for (const keyword of keywords) {
        const page = await browser.newPage();

        await page.goto(
            `https://ph.pinterest.com/search/pins/?q=${encodeURIComponent(keyword)}`,
            { waitUntil: "domcontentloaded", timeout: 60000 }
        );

        await page.waitForTimeout(3000);

        const pins = await page.evaluate(() => {
            const items = [];

            document.querySelectorAll("img").forEach(img => {
                const a = img.closest("a");

                if (img.src && a) {
                    items.push({
                        title: img.alt || "Pin",
                        image: img.src,
                        link: a.href
                    });
                }
            });

            return items.slice(0, 20);
        });

        for (const pin of pins) {
            const score = Math.floor(Math.random() * 10000); // placeholder scoring

            await db.execute(
                `INSERT INTO pinterest_trends 
                (keyword, title, image, link, author, score, scraped_at, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())`,
                [
                    keyword,
                    pin.title,
                    pin.image,
                    pin.link,
                    "pinterest",
                    score
                ]
            );
        }

        await page.close();
    }

    await browser.close();
    await db.end();

    console.log("Pinterest scraping completed");
}

run();