# French Trainer V3

PHP-platform om Franse woordenschat en werkwoorden online te oefenen, met aparte dashboards voor leerlingen, leraren en beheerders.

## Nieuw in V3
- compactere, modernere interface in Studyflow-stijl
- leerlingrapport met thema-overzicht en foutenremediëring
- filters op thema en werkwoordgroep tijdens het oefenen
- uitgebreider klasrapport voor leraren
- snellere navigatie tussen oefenen, rapportage en beheer

## Vereisten
- PHP 8+
- PDO SQLite (`pdo_sqlite`)
- lokale server zoals XAMPP of een hostingpakket met PHP

## Lokaal starten met XAMPP
1. Installeer XAMPP.
2. Start **Apache** in het XAMPP Control Panel.
3. Zet deze map in `C:\xampp\htdocs\french_trainer_v3`.
4. Open in je browser:
   - `http://localhost/french_trainer_v3/public/setup.php`
   - daarna `http://localhost/french_trainer_v3/public/index.php`
5. Log in met de demo-accounts hieronder.

## Demo-accounts
- beheerder: `admin` / `admin123`
- leraar: `leraar1` / `Welkom123!`
- leerling: `leerling1` / `Welkom123!`

## Online zetten
1. Upload de volledige projectmap naar je hosting.
2. Zorg dat PHP 8+ en SQLite actief zijn.
3. Zet de website zo online dat de map `public` de zichtbare webmap is, of open handmatig `public/index.php`.
4. Open één keer `setup.php` om de database en demo-data te maken.
5. Verwijder of beveilig daarna `setup.php` als je live gaat.

## Structuur
- `public/` publieke pagina's
- `src/` database, auth en statistieken
- `templates/` header/footer
- `assets/` CSS
- `sql/` schema
- `storage/` SQLite databasebestand
- `samples/` voorbeeld CSV voor import

## Opmerking
Voor een echte schooluitrol raad ik later een MySQL-versie, HTTPS, wachtwoordreset en logging aan.
