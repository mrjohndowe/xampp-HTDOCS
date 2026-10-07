<?php

declare(strict_types=1);

require_once __DIR__ . '/../assets/includes/functions.php';

$dbDir = __DIR__ . '/../database';

if (!is_dir($dbDir)) {
    mkdir($dbDir, 0775, true);
}

$dbFile = $dbDir . '/insurance.sqlite';

try {

    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    $pdo->exec('PRAGMA foreign_keys = ON');

    /*
     * Insurance companies
     */

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS insurance_companies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            address TEXT,
            city TEXT,
            state TEXT,
            zip TEXT,
            phone TEXT,
            website TEXT,
            logo TEXT,
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    /*
     * Add missing columns to older databases.
     */

    $companyColumns = $pdo->query("PRAGMA table_info(insurance_companies)")->fetchAll();
    $existingCompanyColumns = array_column($companyColumns, 'name');

    $companyMigrations = [
        'address' => 'ALTER TABLE insurance_companies ADD COLUMN address TEXT',
        'city' => 'ALTER TABLE insurance_companies ADD COLUMN city TEXT',
        'state' => 'ALTER TABLE insurance_companies ADD COLUMN state TEXT',
        'zip' => 'ALTER TABLE insurance_companies ADD COLUMN zip TEXT',
        'phone' => 'ALTER TABLE insurance_companies ADD COLUMN phone TEXT',
        'website' => 'ALTER TABLE insurance_companies ADD COLUMN website TEXT',
        'logo' => 'ALTER TABLE insurance_companies ADD COLUMN logo TEXT',
        'active' => 'ALTER TABLE insurance_companies ADD COLUMN active INTEGER NOT NULL DEFAULT 1',
    ];

    foreach ($companyMigrations as $column => $sql) {
        if (!in_array($column, $existingCompanyColumns, true)) {
            $pdo->exec($sql);
        }
    }

    /*
     * States
     */

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS states (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            abbreviation TEXT NOT NULL UNIQUE,
            active INTEGER NOT NULL DEFAULT 1
        )
    ");

    $states = [
        ['Alabama', 'AL'],
        ['Alaska', 'AK'],
        ['Arizona', 'AZ'],
        ['Arkansas', 'AR'],
        ['California', 'CA'],
        ['Colorado', 'CO'],
        ['Connecticut', 'CT'],
        ['Delaware', 'DE'],
        ['Florida', 'FL'],
        ['Georgia', 'GA'],
        ['Hawaii', 'HI'],
        ['Idaho', 'ID'],
        ['Illinois', 'IL'],
        ['Indiana', 'IN'],
        ['Iowa', 'IA'],
        ['Kansas', 'KS'],
        ['Kentucky', 'KY'],
        ['Louisiana', 'LA'],
        ['Maine', 'ME'],
        ['Maryland', 'MD'],
        ['Massachusetts', 'MA'],
        ['Michigan', 'MI'],
        ['Minnesota', 'MN'],
        ['Mississippi', 'MS'],
        ['Missouri', 'MO'],
        ['Montana', 'MT'],
        ['Nebraska', 'NE'],
        ['Nevada', 'NV'],
        ['New Hampshire', 'NH'],
        ['New Jersey', 'NJ'],
        ['New Mexico', 'NM'],
        ['New York', 'NY'],
        ['North Carolina', 'NC'],
        ['North Dakota', 'ND'],
        ['Ohio', 'OH'],
        ['Oklahoma', 'OK'],
        ['Oregon', 'OR'],
        ['Pennsylvania', 'PA'],
        ['Rhode Island', 'RI'],
        ['South Carolina', 'SC'],
        ['South Dakota', 'SD'],
        ['Tennessee', 'TN'],
        ['Texas', 'TX'],
        ['Utah', 'UT'],
        ['Vermont', 'VT'],
        ['Virginia', 'VA'],
        ['Washington', 'WA'],
        ['West Virginia', 'WV'],
        ['Wisconsin', 'WI'],
        ['Wyoming', 'WY'],
    ];

    $insertState = $pdo->prepare("
        INSERT OR IGNORE INTO states (
            name,
            abbreviation
        )
        VALUES (
            :name,
            :abbreviation
        )
    ");

    foreach ($states as [$name, $abbreviation]) {
        $insertState->execute([
            ':name' => $name,
            ':abbreviation' => $abbreviation,
        ]);
    }

    /*
     * Insurance cards
     */

    $cardColumns = $pdo->query("PRAGMA table_info(insurance_cards)")->fetchAll();
    $existingCardColumns = array_column($cardColumns, 'name');

    if (!$cardColumns) {

        $pdo->exec("
            CREATE TABLE insurance_cards (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL,
                insured_name TEXT NOT NULL,
                policy_number TEXT NOT NULL UNIQUE,
                vehicle_year INTEGER,
                vehicle_make TEXT,
                vehicle_model TEXT,
                vin TEXT,
                license_plate TEXT,
                effective_date TEXT NOT NULL,
                expiration_date TEXT NOT NULL,
                liability_bod TEXT,
                property_damage TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id)
                    REFERENCES insurance_companies(id)
                    ON DELETE RESTRICT
            )
        ");

        $pdo->exec("
            ALTER TABLE insurance_cards
            ADD COLUMN secondary_insured TEXT NOT NULL DEFAULT ''
        ");

    } elseif (!in_array('company_id', $existingCardColumns, true)) {

        $pdo->exec('PRAGMA foreign_keys = OFF');
        $pdo->beginTransaction();

        try {

            $oldCompanies = $pdo->query("
                SELECT DISTINCT
                    company_name,
                    company_address,
                    company_phone
                FROM insurance_cards
                WHERE TRIM(company_name) <> ''
            ")->fetchAll();

            $insertCompany = $pdo->prepare("
                INSERT OR IGNORE INTO insurance_companies (
                    name,
                    address,
                    phone
                )
                VALUES (
                    :name,
                    :address,
                    :phone
                )
            ");

            foreach ($oldCompanies as $company) {
                $insertCompany->execute([
                    ':name' => $company['company_name'],
                    ':address' => $company['company_address'],
                    ':phone' => $company['company_phone'],
                ]);
            }

            $pdo->exec("ALTER TABLE insurance_cards RENAME TO insurance_cards_old");

            $pdo->exec("
                CREATE TABLE insurance_cards (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    company_id INTEGER NOT NULL,
                    insured_name TEXT NOT NULL,
                    policy_number TEXT NOT NULL UNIQUE,
                    vehicle_year INTEGER,
                    vehicle_make TEXT,
                    vehicle_model TEXT,
                    vin TEXT,
                    license_plate TEXT,
                    effective_date TEXT NOT NULL,
                    expiration_date TEXT NOT NULL,
                    liability_bod TEXT,
                    property_damage TEXT,
                    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (company_id)
                        REFERENCES insurance_companies(id)
                        ON DELETE RESTRICT
                )
            ");

            $pdo->exec("
                INSERT INTO insurance_cards (
                    id,
                    company_id,
                    insured_name,
                    policy_number,
                    vehicle_year,
                    vehicle_make,
                    vehicle_model,
                    vin,
                    license_plate,
                    effective_date,
                    expiration_date,
                    liability_bod,
                    property_damage,
                    created_at,
                    updated_at
                )
                SELECT
                    old.id,
                    companies.id,
                    old.insured_name,
                    old.policy_number,
                    old.vehicle_year,
                    old.vehicle_make,
                    old.vehicle_model,
                    old.vin,
                    old.license_plate,
                    old.effective_date,
                    old.expiration_date,
                    old.liability_bod,
                    old.property_damage,
                    old.created_at,
                    old.updated_at
                FROM insurance_cards_old AS old
                INNER JOIN insurance_companies AS companies
                    ON companies.name = old.company_name
            ");

            $pdo->exec("DROP TABLE insurance_cards_old");

            $pdo->commit();

        } catch (Throwable $e) {

            $pdo->rollBack();

            throw $e;
        }

        $pdo->exec('PRAGMA foreign_keys = ON');
    }

} catch (Throwable $e) {

    http_response_code(500);

    exit('Database initialization failed: ' . e($e->getMessage()));
}
