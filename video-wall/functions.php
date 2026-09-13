<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';

function ensureDataDirectory(): void
{
    if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0775, true) && !is_dir(DATA_DIR)) throw new RuntimeException('Unable to create the data folder.');
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    ensureDataDirectory();
    $pdo = new PDO('sqlite:' . DATABASE_FILE, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON; CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY,value TEXT NOT NULL); CREATE TABLE IF NOT EXISTS folders (id INTEGER PRIMARY KEY AUTOINCREMENT,path TEXT NOT NULL UNIQUE); CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT NOT NULL UNIQUE COLLATE NOCASE); CREATE TABLE IF NOT EXISTS production (id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT NOT NULL UNIQUE COLLATE NOCASE); CREATE TABLE IF NOT EXISTS videos (id TEXT PRIMARY KEY,original_name TEXT NOT NULL,display_name TEXT,file TEXT NOT NULL,category TEXT NOT NULL DEFAULT "",folder TEXT NOT NULL,path TEXT NOT NULL UNIQUE,extension TEXT NOT NULL,size INTEGER NOT NULL,modified INTEGER NOT NULL,created INTEGER NOT NULL DEFAULT 0,category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,active INTEGER NOT NULL DEFAULT 1,actors TEXT NOT NULL DEFAULT "",characters TEXT NOT NULL DEFAULT "",notes TEXT NOT NULL DEFAULT "",publish_date TEXT NOT NULL DEFAULT "",production TEXT NOT NULL DEFAULT "",duplicate_of TEXT); CREATE TABLE IF NOT EXISTS video_categories (video_id TEXT NOT NULL REFERENCES videos(id) ON DELETE CASCADE,category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,PRIMARY KEY(video_id,category_id)); CREATE TABLE IF NOT EXISTS video_productions (video_id TEXT NOT NULL REFERENCES videos(id) ON DELETE CASCADE,production_id INTEGER NOT NULL REFERENCES production(id) ON DELETE CASCADE,PRIMARY KEY(video_id,production_id)); CREATE INDEX IF NOT EXISTS video_categories_category_idx ON video_categories(category_id); CREATE INDEX IF NOT EXISTS video_productions_production_idx ON video_productions(production_id)');
    $columns = $pdo->query('PRAGMA table_info(videos)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('category_id', $columns, true)) $pdo->exec('ALTER TABLE videos ADD COLUMN category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL');
    if (!in_array('active', $columns, true)) $pdo->exec('ALTER TABLE videos ADD COLUMN active INTEGER NOT NULL DEFAULT 1');
    if (!in_array('actors', $columns, true)) $pdo->exec('ALTER TABLE videos ADD COLUMN actors TEXT NOT NULL DEFAULT ""');
    if (!in_array('characters', $columns, true)) $pdo->exec('ALTER TABLE videos ADD COLUMN characters TEXT NOT NULL DEFAULT ""');
    if (!in_array('notes', $columns, true)) $pdo->exec('ALTER TABLE videos ADD COLUMN notes TEXT NOT NULL DEFAULT ""');
    if (!in_array('publish_date', $columns, true)) $pdo->exec('ALTER TABLE videos ADD COLUMN publish_date TEXT NOT NULL DEFAULT ""');
    if (!in_array('created', $columns, true)) $pdo->exec('ALTER TABLE videos ADD COLUMN created INTEGER NOT NULL DEFAULT 0');
    if (!in_array('production', $columns, true)) $pdo->exec('ALTER TABLE videos ADD COLUMN production TEXT NOT NULL DEFAULT ""');
    if (!in_array('duplicate_of', $columns, true)) $pdo->exec('ALTER TABLE videos ADD COLUMN duplicate_of TEXT');
    $pdo->exec('INSERT OR IGNORE INTO video_categories(video_id,category_id) SELECT id,category_id FROM videos WHERE category_id IS NOT NULL');
    $categoryColumns = $pdo->query('PRAGMA table_info(video_categories)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (in_array('production_id', $categoryColumns, true)) $pdo->exec('INSERT OR IGNORE INTO video_productions(video_id,production_id) SELECT video_id,production_id FROM video_categories WHERE production_id IS NOT NULL');
    migrateLegacyJson($pdo);
    return $pdo;
}

function migrateLegacyJson(PDO $pdo): void
{
    $file = DATA_DIR . DIRECTORY_SEPARATOR . 'settings.json';
    if (!is_file($file) || (int)$pdo->query('SELECT COUNT(*) FROM folders')->fetchColumn() > 0) return;
    $legacy = json_decode((string)file_get_contents($file), true);
    if (!is_array($legacy)) return;
    $pdo->beginTransaction();
    try {
        $folder = $pdo->prepare('INSERT OR IGNORE INTO folders(path) VALUES(?)');
        foreach (($legacy['folders'] ?? []) as $path) $folder->execute([(string)$path]);
        $setting = $pdo->prepare('INSERT OR REPLACE INTO settings(key,value) VALUES(?,?)');
        $setting->execute(['ffmpegPath', (string)($legacy['ffmpegPath'] ?? '')]);
        $setting->execute(['autoNext', '1']);
        $setting->execute(['startMuted', '0']);
        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}

function loadSettings(): array
{
    $pdo = db();
    $settings = ['folders' => [], 'ffmpegPath' => '', 'autoNext' => true, 'startMuted' => false];
    $settings['folders'] = $pdo->query('SELECT path FROM folders ORDER BY path COLLATE NOCASE')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($pdo->query('SELECT key,value FROM settings') as $row) $settings[$row['key']] = in_array($row['key'], ['autoNext', 'startMuted'], true) ? $row['value'] === '1' : $row['value'];
    return $settings;
}

function saveSettings(array $settings): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->exec('DELETE FROM folders');
        $folder = $pdo->prepare('INSERT INTO folders(path) VALUES(?)');
        foreach ($settings['folders'] as $path) $folder->execute([$path]);
        $statement = $pdo->prepare('INSERT OR REPLACE INTO settings(key,value) VALUES(?,?)');
        $statement->execute(['ffmpegPath', (string)($settings['ffmpegPath'] ?? '')]);
        $statement->execute(['autoNext', !empty($settings['autoNext']) ? '1' : '0']);
        $statement->execute(['startMuted', !empty($settings['startMuted']) ? '1' : '0']);
        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}

function cleanFolders(array $folders): array
{
    $result = [];
    foreach ($folders as $folder) {
        $folder = trim((string)$folder, " \t\n\r\0\x0B\"");
        if ($folder === '') continue;
        $real = realpath($folder);
        if ($real !== false && is_dir($real)) $result[$real] = $real;
    }
    return array_values($result);
}

function deactivateDuplicateItems(array &$items): int
{
    $groups = [];
    foreach ($items as $index => $item) {
        $items[$index]['duplicate_of'] = null;
        $groups[(string)$item['size']][] = $index;
    }
    $count = 0;
    foreach ($groups as $indices) {
        if (count($indices) < 2) continue;
        $hashes = [];
        foreach ($indices as $index) {
            $hash = @hash_file('sha256', $items[$index]['path']);
            if ($hash !== false) $hashes[$hash][] = $index;
        }
        foreach ($hashes as $matches) {
            if (count($matches) < 2) continue;
            $keeper = $matches[0];
            foreach ($matches as $index) if ((int)$items[$index]['active'] === 1) {
                $keeper = $index;
                break;
            }
            foreach ($matches as $index) {
                if ($index === $keeper) continue;
                $items[$index]['active'] = 0;
                $items[$index]['duplicate_of'] = $items[$keeper]['id'];
                $count++;
            }
        }
    }
    return $count;
}

function buildCatalog(array $folders): array
{
    $pdo = db();
    $names = $pdo->query("SELECT id,display_name FROM videos WHERE display_name IS NOT NULL AND display_name<>''")->fetchAll(PDO::FETCH_KEY_PAIR);
    $assignments = $pdo->query('SELECT id,category_id FROM videos WHERE category_id IS NOT NULL')->fetchAll(PDO::FETCH_KEY_PAIR);
    $categoryAssignments = [];
    foreach ($pdo->query('SELECT video_id,category_id FROM video_categories') as $row) $categoryAssignments[$row['video_id']][] = (int)$row['category_id'];
    $productionAssignments = [];
    foreach ($pdo->query('SELECT video_id,production_id FROM video_productions') as $row) $productionAssignments[$row['video_id']][] = (int)$row['production_id'];
    $activeStates = $pdo->query('SELECT id,active FROM videos')->fetchAll(PDO::FETCH_KEY_PAIR);
    $metadata = [];
    foreach ($pdo->query('SELECT id,actors,characters,notes,publish_date,production FROM videos') as $row) $metadata[$row['id']] = $row;
    $legacyRenames = DATA_DIR . DIRECTORY_SEPARATOR . 'renames.json';
    if (is_file($legacyRenames)) {
        $legacy = json_decode((string)file_get_contents($legacyRenames), true);
        if (is_array($legacy)) $names = array_replace($legacy, $names);
    }
    $items = [];
    foreach ($folders as $root) {
        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS));
            foreach ($iterator as $file) {
                if (!$file instanceof SplFileInfo || !$file->isFile()) continue;
                $extension = strtolower($file->getExtension());
                if (!in_array($extension, VIDEO_EXTENSIONS, true)) continue;
                $path = $file->getRealPath();
                if ($path === false) continue;
                $relative = ltrim(str_replace('\\', '/', substr($path, strlen($root))), '/');
                $id = hash('sha256', $path);
                $created = max(0, $file->getCTime());
                $publishedDate = trim((string)($metadata[$id]['publish_date'] ?? ''));
                if ($publishedDate === '' && $created > 0) $publishedDate = date('Y-m-d', $created);
                $items[] = ['id' => $id, 'original_name' => pathinfo($file->getFilename(), PATHINFO_FILENAME), 'display_name' => $names[$id] ?? null, 'file' => $file->getFilename(), 'category' => '', 'folder' => basename($root) . ($relative && dirname($relative) !== '.' ? ' / ' . dirname($relative) : ''), 'path' => $path, 'extension' => $extension, 'size' => $file->getSize(), 'modified' => $file->getMTime(), 'created' => $created, 'category_id' => $assignments[$id] ?? null, 'active' => isset($activeStates[$id]) ? (int)$activeStates[$id] : 1, 'actors' => $metadata[$id]['actors'] ?? '', 'characters' => $metadata[$id]['characters'] ?? '', 'notes' => $metadata[$id]['notes'] ?? '', 'publish_date' => $publishedDate, 'production' => $metadata[$id]['production'] ?? ''];
            }
        } catch (UnexpectedValueException) {
            continue;
        }
    }
    $unique = [];
    foreach ($items as $item) $unique[strtolower(str_replace('\\', '/', $item['path']))] = $item;
    $items = array_values($unique);
    deactivateDuplicateItems($items);
    $pdo->beginTransaction();
    try {
        $pdo->exec('DELETE FROM videos');
        $statement = $pdo->prepare('INSERT INTO videos(id,original_name,display_name,file,category,folder,path,extension,size,modified,created,category_id,active,actors,characters,notes,publish_date,production,duplicate_of) VALUES(:id,:original_name,:display_name,:file,:category,:folder,:path,:extension,:size,:modified,:created,:category_id,:active,:actors,:characters,:notes,:publish_date,:production,:duplicate_of)');
        $categoryStatement = $pdo->prepare('INSERT OR IGNORE INTO video_categories(video_id,category_id) VALUES(?,?)');
        $productionStatement = $pdo->prepare('INSERT OR IGNORE INTO video_productions(video_id,production_id) VALUES(?,?)');
        foreach ($items as $item) {
            $statement->execute($item);
            foreach ($categoryAssignments[$item['id']] ?? [] as $categoryId) $categoryStatement->execute([$item['id'], $categoryId]);
            foreach ($productionAssignments[$item['id']] ?? [] as $productionId) $productionStatement->execute([$item['id'], $productionId]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
    return catalog();
}

function catalog(): array
{
    $pdo = db();
    $videos = $pdo->query("SELECT v.id,v.original_name AS originalName,COALESCE(NULLIF(v.display_name,''),v.original_name)AS name,v.file,v.actors,v.characters,v.notes,v.publish_date AS publishDate,v.production,v.folder,v.path,v.extension,v.size,v.modified FROM videos v WHERE v.active=1 ORDER BY name COLLATE NOCASE")->fetchAll();
    $assigned = [];
    foreach ($pdo->query('SELECT vc.video_id,c.id,c.name FROM video_categories vc JOIN categories c ON c.id=vc.category_id ORDER BY c.name COLLATE NOCASE') as $row) $assigned[$row['video_id']][] = ['id' => (int)$row['id'], 'name' => $row['name']];
    $productionAssignments = [];
    foreach ($pdo->query('SELECT vp.video_id,p.id,p.name FROM video_productions vp JOIN production p ON p.id=vp.production_id ORDER BY p.name COLLATE NOCASE') as $row) $productionAssignments[$row['video_id']][] = ['id' => (int)$row['id'], 'name' => $row['name']];
    foreach ($videos as &$video) {
        $links = $assigned[$video['id']] ?? [];
        $video['categoryIds'] = array_column($links, 'id');
        $video['categories'] = array_column($links, 'name');
        $video['category'] = $video['categories'] ? implode(', ', $video['categories']) : 'Uncategorized';
        $video['categoryId'] = $video['categoryIds'][0] ?? null;
        $productions = $productionAssignments[$video['id']] ?? [];
        $video['productionIds'] = array_column($productions, 'id');
        $video['productions'] = array_column($productions, 'name');
        $video['production'] = $video['productions'] ? implode(', ', $video['productions']) : 'Not added';
    }
    unset($video);
    return $videos;
}
function removedVideos(): array
{
    return db()->query("SELECT v.id,COALESCE(NULLIF(v.display_name,''),v.original_name)AS name,v.path,v.duplicate_of,COALESCE(NULLIF(k.display_name,''),k.original_name)AS duplicate_name,k.path AS duplicate_path FROM videos v LEFT JOIN videos k ON k.id=v.duplicate_of WHERE v.active=0 ORDER BY name COLLATE NOCASE")->fetchAll();
}
function categoriesList(): array
{
    return db()->query('SELECT id,name FROM categories ORDER BY name COLLATE NOCASE')->fetchAll();
}
function productionsList(): array
{
    return db()->query('SELECT id, name FROM production ORDER BY name COLLATE NOCASE')->fetchALL();
}

function ensureCategoryExists(PDO $pdo, string $name): int
{
    $name = trim($name);
    if ($name === '') return 0;

    $check = $pdo->prepare('SELECT id FROM categories WHERE name = ? COLLATE NOCASE');
    $check->execute([$name]);
    $existing = $check->fetch(PDO::FETCH_COLUMN);
    if ($existing !== false) return (int) $existing;

    $insert = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
    $insert->execute([$name]);
    return (int) $pdo->lastInsertId();
}

function ensureProductionExists(PDO $pdo, string $name): int
{
    $name = trim($name);
    if ($name === '') return 0;

    $check = $pdo->prepare('SELECT id FROM production WHERE name = ? COLLATE NOCASE');
    $check->execute([$name]);
    $existing = $check->fetch(PDO::FETCH_COLUMN);
    if ($existing !== false) return (int) $existing;

    $insert = $pdo->prepare('INSERT INTO production (name) VALUES (?)');
    $insert->execute([$name]);
    return (int) $pdo->lastInsertId();
}

function setVideoCategories(PDO $pdo, string $videoId, array $categoryIds): void
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $categoryIds), fn($id) => $id > 0)));
    $valid = [];
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $check = $pdo->prepare("SELECT id FROM categories WHERE id IN ($placeholders)");
        $check->execute($ids);
        $valid = array_map('intval', $check->fetchAll(PDO::FETCH_COLUMN));
    }
    $delete = $pdo->prepare('DELETE FROM video_categories WHERE video_id=?');
    $delete->execute([$videoId]);
    $insert = $pdo->prepare('INSERT INTO video_categories(video_id,category_id) VALUES(?,?)');
    foreach ($valid as $categoryId) $insert->execute([$videoId, $categoryId]);
    $legacy = $pdo->prepare('UPDATE videos SET category_id=? WHERE id=?');
    $legacy->execute([$valid[0] ?? null, $videoId]);
}

function setVideoProduction(PDO $pdo, string $videoId, array $productionIds): void
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $productionIds), fn($id) => $id > 0)));
    $valid = [];
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $check = $pdo->prepare("SELECT id FROM production WHERE id IN ($placeholders)");
        $check->execute($ids);
        $valid = array_map('intval', $check->fetchAll(PDO::FETCH_COLUMN));
    }
    $delete = $pdo->prepare('DELETE FROM video_productions WHERE video_id=?');
    $delete->execute([$videoId]);
    $insert = $pdo->prepare('INSERT INTO video_productions(video_id,production_id) VALUES(?,?)');
    foreach ($valid as $productionId) $insert->execute([$videoId, $productionId]);
}
function publicVideo(array $video): array
{
    $video['fullPath'] = $video['path'];
    unset($video['path']);
    $video['stream'] = 'stream.php?id=' . rawurlencode($video['id']);
    $video['thumbnailKey'] = $video['id'] . ':' . (int)$video['modified'];
    $video['thumbnail'] = 'thumbnail.php?id=' . rawurlencode($video['id']) . '&v=' . (int)$video['modified'];
    return $video;
}
function findFfmpeg(): ?string
{
    $settings = loadSettings();
    $configured = trim((string)($settings['ffmpegPath'] ?? FFMPEG_PATH));
    if ($configured !== '' && is_file($configured)) return $configured;
    $candidates = ['B:\\xampp\\htdocs\\DoweLanCaster\\ffmpeg.exe', 'B:\\xampp\\htdocs\\DoweLanCaster\\bin\\ffmpeg.exe', 'G:\\.gitClones\\DoweLanCaster\\ffmpeg.exe', 'G:\\.gitClones\\DoweLanCaster\\bin\\ffmpeg.exe', 'G:\\.gitClones\\DoweLanCaster.Windows\\ffmpeg.exe', 'G:\\.gitClones\\DoweLanCaster.Windows\\bin\\ffmpeg.exe', 'B:\\xampp\\ffmpeg\\bin\\ffmpeg.exe', 'C:\\ffmpeg\\bin\\ffmpeg.exe'];
    foreach ($candidates as $candidate) if (is_file($candidate)) return $candidate;
    $command = PHP_OS_FAMILY === 'Windows' ? 'where ffmpeg 2>NUL' : 'command -v ffmpeg 2>/dev/null';
    $found = trim((string)@shell_exec($command));
    $first = preg_split('/\R/', $found)[0] ?? '';
    return $first !== '' && is_file($first) ? $first : null;
}
function mimeFor(string $extension): string
{
    return match ($extension) {
        'mp4', 'm4v' => 'video/mp4',
        'webm' => 'video/webm',
        'ogv', 'ogg' => 'video/ogg',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        'mkv' => 'video/x-matroska',
        default => 'video/mpeg'
    };
}

function normalizeVideoTitle(string $title): string
{
    $title = trim($title);

    $title = preg_replace('/\s+/u', ' ', $title) ?? $title;

    if (function_exists('mb_strtolower')) {
        $title = mb_strtolower($title, 'UTF-8');
    } else {
        $title = strtolower($title);
    }

    // Treat punctuation differences as the same title.
    $title = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $title) ?? $title;

    $title = preg_replace('/\s+/u', ' ', $title) ?? $title;

    return trim($title);
}


function makeUniqueVideoTitle(string $title, array $existingTitles): string
{
    $title = trim($title);

    if ($title === '') {
        $title = 'Untitled Video';
    }

    $used = [];

    foreach ($existingTitles as $existingTitle) {
        $used[normalizeVideoTitle((string) $existingTitle)] = true;
    }

    if (! isset($used[normalizeVideoTitle($title)])) {
        return $title;
    }

    $base   = $title;
    $number = 2;

    while (isset($used[normalizeVideoTitle($base . ' ' . $number)])) {
        $number++;
    }

    return $base . ' ' . $number;
}

function analysisError(string $message, int $status = 422): never
{
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

function removeAnalysisFrames(array $frames): void
{
    foreach ($frames as $frame) {
        @unlink($frame);
    }

}

function normalizeAnalysisTitle(string $value): string
{
    return trim((string) preg_replace('/[^a-z0-9]+/i', ' ', strtolower($value)));
}

function improveAnalysisTitle(string $title, string $originalName): string
{
    $title              = trim($title);
    $originalNormalized = normalizeAnalysisTitle($originalName);

    if ($title === '') {
        return '';
    }

    $parts = preg_split('/\s*[-–—:]\s*/', $title, 2);
    if (count($parts) === 2) {
        [$leading, $descriptive] = array_map('trim', $parts);
        $leadingNormalized       = normalizeAnalysisTitle($leading);
        if ($descriptive !== '' && $leadingNormalized !== '' && str_contains($originalNormalized, $leadingNormalized)) {
            $title = $descriptive . ' - ' . $leading;
        }
    }

    if (normalizeAnalysisTitle($title) === $originalNormalized) {
        return '';
    }

    return $title;
}

function probeVideoDuration(string $ffmpeg, string $videoPath): ?float
{
    $ffprobe = preg_replace('/ffmpeg(?:\.exe)?$/i', PHP_OS_FAMILY === 'Windows' ? 'ffprobe.exe' : 'ffprobe', $ffmpeg);
    if (! is_string($ffprobe) || $ffprobe === '' || ! is_file($ffprobe)) {
        return null;
    }

    $command = escapeshellarg($ffprobe) . ' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . escapeshellarg($videoPath);
    $output  = [];
    $code    = 1;
    @exec($command, $output, $code);
    if ($code !== 0 || ! $output) {
        return null;
    }

    $duration = (float) trim((string) $output[0]);
    return $duration > 0 ? $duration : null;
}

function buildFrameOffsets(?float $duration): array
{
    if ($duration === null || $duration <= 0) {
        return [2.0, 10.0, 20.0];
    }

    $fractions = [0.10, 0.50, 0.90];
    $offsets   = [];
    foreach ($fractions as $fraction) {
        $offset = max(0.0, min($duration - 0.15, $duration * $fraction));
        if ($offset >= 0) {
            $offsets[] = round($offset, 3);
        }

    }

    $offsets = array_values(array_unique($offsets, SORT_REGULAR));
    return $offsets ?: [0.0];
}
