# Instalacja Node.js przez SSH

Masz dostęp SSH? Świetnie! To najłatwiejszy sposób instalacji.

---

## 🚀 Metoda 1: Automatyczna instalacja (ZALECANA)

### Krok 1: Połącz się przez SSH
```bash
ssh twoj_user@twoj_serwer.com
```

### Krok 2: Przejdź do katalogu aplikacji
```bash
cd /home/host893619/domains/monitor-map.xseox.com.pl/public_html
# lub: cd /twoja/sciezka/do/monitormap
```

### Krok 3: Uruchom skrypt instalacyjny
```bash
chmod +x install_nodejs.sh
./install_nodejs.sh
```

**Gotowe!** Skrypt automatycznie:
- ✅ Sprawdzi czy Node.js jest zainstalowany
- ✅ Zainstaluje Node.js i npm (jeśli potrzebne)
- ✅ Zainstaluje wszystkie dependencies (puppeteer, mysql2, etc.)
- ✅ Zaktualizuje ścieżkę NODE_PATH w .env

---

## 🔧 Metoda 2: Manualna instalacja

### A. Sprawdź czy Node.js jest zainstalowany

```bash
node --version
npm --version
```

**Jeśli widzisz wersje (np. v18.17.0)** - przejdź do kroku C.

**Jeśli dostajesz błąd "command not found"** - przejdź do kroku B.

---

### B. Zainstaluj Node.js

#### Opcja 1: Przez NVM (Node Version Manager) - ZALECANA ⭐

NVM pozwala instalować Node.js bez uprawnień root.

```bash
# Pobierz i zainstaluj NVM
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash

# Załaduj NVM do obecnej sesji
source ~/.bashrc
# lub jeśli używasz zsh:
# source ~/.zshrc

# Zainstaluj Node.js (wersja 18 LTS)
nvm install 18

# Ustaw jako domyślną
nvm use 18
nvm alias default 18

# Sprawdź instalację
node --version
npm --version
```

#### Opcja 2: Przez manager pakietów (wymaga sudo)

**Debian/Ubuntu:**
```bash
# Zaktualizuj listę pakietów
sudo apt-get update

# Zainstaluj Node.js i npm
sudo apt-get install -y nodejs npm

# Sprawdź wersje
node --version
npm --version
```

**CentOS/RHEL:**
```bash
# Włącz repozytorium EPEL
sudo yum install -y epel-release

# Zainstaluj Node.js i npm
sudo yum install -y nodejs npm

# Sprawdź wersje
node --version
npm --version
```

#### Opcja 3: Przez NodeSource (nowsze wersje)

**Dla Node.js 18.x (LTS):**
```bash
# Debian/Ubuntu
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# CentOS/RHEL
curl -fsSL https://rpm.nodesource.com/setup_18.x | sudo bash -
sudo yum install -y nodejs
```

---

### C. Zainstaluj dependencies dla scrapera

```bash
# Przejdź do katalogu scraper
cd /home/host893619/domains/monitor-map.xseox.com.pl/public_html/scraper
# lub: cd /twoja/sciezka/do/monitormap/scraper

# Zainstaluj wszystkie zależności
npm install
```

To zainstaluje:
- `puppeteer` - automatyzacja przeglądarki
- `puppeteer-extra` - rozszerzenia
- `puppeteer-extra-plugin-stealth` - anty-detekcja
- `mysql2` - połączenie z bazą danych
- `dotenv` - zmienne środowiskowe

**Uwaga:** Instalacja puppeteer może zająć 2-5 minut, bo pobiera Chromium (~170MB).

---

### D. Zaktualizuj plik .env

```bash
# Znajdź ścieżkę do node
which node
# Przykład: /home/host893619/.nvm/versions/node/v18.17.0/bin/node

# Edytuj .env
cd ..
nano .env
```

Znajdź linię `NODE_PATH=` i ustaw poprawną ścieżkę:
```
NODE_PATH=/home/host893619/.nvm/versions/node/v18.17.0/bin/node
```

Zapisz (Ctrl+O, Enter) i wyjdź (Ctrl+X).

---

## ✅ Test instalacji

### 1. Sprawdź czy wszystko jest zainstalowane

```bash
# Sprawdź Node.js
node --version
# Oczekiwane: v18.17.0 (lub podobne)

# Sprawdź npm
npm --version
# Oczekiwane: 9.6.7 (lub podobne)

# Sprawdź czy moduły są zainstalowane
cd scraper
ls -la node_modules/
# Powinien być puppeteer, mysql2, dotenv, etc.
```

### 2. Test scraper (opcjonalnie)

```bash
# Stwórz testową frazę w bazie (przez panel web)
# Następnie uruchom scraper:
node scraper.js --phrase-id=1 --scan-id=1
```

Jeśli zobaczysz logi typu:
```
[DB] Connected to database
[INFO] Starting scan for phrase: test phrase
```
To znaczy że działa! ✅

---

## 🐛 Troubleshooting

### Problem: "npm: command not found"

**Rozwiązanie:**
```bash
# Sprawdź czy npm jest zainstalowany
which npm

# Jeśli nie, zainstaluj ponownie Node.js (z npm)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc
nvm install 18
```

---

### Problem: "Permission denied" podczas npm install

**Rozwiązanie:**
```bash
# Nie używaj sudo z npm!
# Zamiast tego użyj NVM (instalacja bez roota)

# Lub zmień właściciela katalogu
chown -R $USER:$USER ~/monitormap/scraper/node_modules
```

---

### Problem: Puppeteer pobiera Chromium bardzo długo

**Rozwiązanie:**
To normalne - Chromium ma ~170MB. Poczekaj 5-10 minut.

Jeśli chcesz używać systemowego Chrome:
```bash
# W scraper.js zmień:
const browser = await puppeteer.launch({
    executablePath: '/usr/bin/google-chrome',  // lub /usr/bin/chromium
    ...
});
```

---

### Problem: "Error: Could not find Chrome"

**Rozwiązanie:**
```bash
# Zainstaluj Chromium
# Debian/Ubuntu:
sudo apt-get install -y chromium-browser

# CentOS:
sudo yum install -y chromium

# Lub pozwól puppeteer pobrać własnego Chromium:
cd scraper
npm install puppeteer
```

---

### Problem: "EACCES: permission denied, mkdir '/root/.npm'"

**Rozwiązanie:**
```bash
# Ustaw npm prefix na katalog użytkownika
mkdir ~/.npm-global
npm config set prefix '~/.npm-global'

# Dodaj do PATH
echo 'export PATH=~/.npm-global/bin:$PATH' >> ~/.bashrc
source ~/.bashrc

# Spróbuj ponownie
npm install
```

---

## 📋 Checklist po instalacji

- [ ] Node.js zainstalowane (`node --version` działa)
- [ ] npm zainstalowane (`npm --version` działa)
- [ ] Dependencies zainstalowane (`ls scraper/node_modules/puppeteer`)
- [ ] NODE_PATH ustawione w .env (poprawna ścieżka)
- [ ] Scraper testowo uruchomiony (opcjonalnie)
- [ ] Web installation dokończona (install.php)
- [ ] Cron jobs skonfigurowane

---

## 🎯 Co dalej?

1. ✅ **Dokończ instalację web** (install.php Step 3 i 4)
2. ✅ **Skonfiguruj cron jobs** (zobacz `docs/CRON_SETUP.md`)
3. ✅ **Dodaj proxy** (w panelu Proxies)
4. ✅ **Dodaj pierwszą frazę** (Phrases → Add Phrase)
5. ✅ **Uruchom test scan** (kliknij "Scan Now")

---

## 📞 Wsparcie

Jeśli masz problemy:
1. Sprawdź logi: `cat logs/scraper/$(date +%Y-%m-%d).log`
2. Sprawdź błędy Node.js: `node scraper/scraper.js` (bez parametrów)
3. Sprawdź uprawnienia: `ls -la scraper/`

---

**Powodzenia!** 🚀
