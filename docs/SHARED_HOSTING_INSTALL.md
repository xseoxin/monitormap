# Instalacja na Serwerze Współdzielonym

## Problem z Node.js/npm na Shared Hosting

Na większości serwerów współdzielonych nie masz dostępu do npm/node.js. Oto **3 rozwiązania**:

---

## ✅ ROZWIĄZANIE 1: Zainstaluj lokalnie i uploaduj (NAJŁATWIEJSZE)

### Kroki:

1. **Na swoim komputerze** (Windows/Mac/Linux):

```bash
# Przejdź do katalogu scraper
cd scraper

# Zainstaluj zależności
npm install

# To stworzy folder node_modules/
```

2. **Uploaduj całą zawartość** folderu `scraper/` (wraz z `node_modules/`) na serwer przez FTP/SFTP

3. **Struktura na serwerze powinna być**:
```
/scraper/
    /node_modules/           <- Cały folder
        /puppeteer/
        /puppeteer-extra/
        /mysql2/
        ...
    package.json
    scraper.js
```

4. **Sprawdź ścieżkę do Node.js** na serwerze:
```bash
which node
# Przykład odpowiedzi: /usr/bin/node
```

5. **Zaktualizuj plik `.env`** ze ścieżką do Node.js:
```
NODE_PATH=/usr/bin/node
```

### ⚠️ Uwaga:
- Folder `node_modules` może być duży (200-300MB)
- Upload może zająć 10-30 minut
- Upewnij się że masz wystarczająco miejsca na serwerze

---

## ✅ ROZWIĄZANIE 2: Użyj SSH (jeśli dostępne)

Jeśli masz dostęp SSH do serwera:

```bash
# Połącz się przez SSH
ssh user@yourserver.com

# Przejdź do katalogu
cd /path/to/monitormap/scraper

# Sprawdź czy npm jest dostępne
npm --version

# Jeśli jest, zainstaluj:
npm install

# Jeśli nie ma npm, sprawdź czy możesz użyć nvm:
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc
nvm install 18
npm install
```

---

## ✅ ROZWIĄZANIE 3: Uproszczona wersja bez Puppeteer (dla bardzo ograniczonych hostingów)

Jeśli naprawdę nie możesz zainstalować Node.js, masz 2 opcje:

### Opcja A: Użyj zewnętrznej usługi scraping
- ScraperAPI (https://www.scraperapi.com/)
- Bright Data (https://brightdata.com/)
- Oxylabs (https://oxylabs.io/)

### Opcja B: Uruchom scraper na lokalnym komputerze
1. Zainstaluj na swoim komputerze
2. Ustaw połączenie do zdalnej bazy danych MySQL
3. Scraper działa u Ciebie, zapisuje do bazy na serwerze
4. PHP na serwerze odczytuje wyniki

**Konfiguracja (scraper działa lokalnie, baza na serwerze)**:

W pliku `.env` na **swoim komputerze** (w folderze scraper):
```
DB_HOST=twojserwer.com    <- Adres serwera
DB_PORT=3306
DB_NAME=maps_monitor
DB_USER=twoj_user
DB_PASS=twoje_haslo
```

**WAŻNE**: Musisz włączyć **zdalne połączenia MySQL** w panelu hostingowym:
- cPanel: Remote MySQL
- Dodaj swoje IP do białej listy

---

## 🔧 Testowanie instalacji

Po instalacji sprawdź czy działa:

```bash
# Test 1: Sprawdź Node.js
node --version

# Test 2: Sprawdź czy scraper się uruchamia
cd scraper
node scraper.js --phrase-id=1 --scan-id=1

# Jeśli zobaczysz błąd "phrase not found" - OK! (brak danych w bazie)
# Jeśli zobaczysz inne błędy - coś jest źle
```

---

## 📋 Checklist instalacji

- [ ] Folder `scraper/node_modules/` istnieje
- [ ] Plik `scraper/package.json` istnieje
- [ ] Node.js jest zainstalowane na serwerze
- [ ] Ścieżka `NODE_PATH` w `.env` jest poprawna
- [ ] Baza danych działa
- [ ] Cron jobs są skonfigurowane

---

## ❓ Najczęstsze problemy

### "node: command not found"
**Rozwiązanie**: Znajdź pełną ścieżkę
```bash
find /usr -name node 2>/dev/null
# Użyj pełnej ścieżki w .env
```

### "Cannot find module 'puppeteer'"
**Rozwiązanie**: `node_modules` nie został przesłany lub jest w złym miejscu
```bash
cd scraper
npm install
```

### "Permission denied"
**Rozwiązanie**: Ustaw uprawnienia
```bash
chmod 755 scraper/scraper.js
chmod -R 755 scraper/node_modules
```

### MySQL connection error
**Rozwiązanie**: Sprawdź czy dane w `.env` są poprawne
```bash
mysql -h DB_HOST -u DB_USER -p DB_NAME
```

---

## 🚀 Rekomendowane hostingi z Node.js

Jeśli twój obecny hosting nie wspiera Node.js, rozważ:

**Tanie z Node.js**:
- **DigitalOcean** - $6/miesiąc
- **Vultr** - $5/miesiąc
- **Linode** - $5/miesiąc
- **Hetzner** - €4/miesiąc

**Managed z Node.js**:
- **Railway.app** - Pay as you go
- **Render.com** - Free tier dostępny
- **Fly.io** - Free tier dostępny

---

## 💡 Najlepsze rozwiązanie dla Ciebie?

1. **Masz SSH + npm?** → Użyj `npm install` przez SSH
2. **Nie masz SSH/npm?** → Zainstaluj lokalnie i uploaduj przez FTP
3. **Bardzo ograniczony hosting?** → Uruchom scraper lokalnie, połącz ze zdalną bazą
4. **Produkcja?** → Rozważ VPS z pełnym dostępem

---

## 📞 Pomoc

Jeśli nadal masz problemy:
1. Sprawdź logi w `/logs/scraper/`
2. Włącz debug mode w `.env`: `APP_DEBUG=true`
3. Przetestuj każdy komponent osobno
