#!/bin/bash
set -euo pipefail

PORT="${PORT:-8080}"

# Ensure only one Apache MPM is active (mod_php requires prefork).
a2dismod -f mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i -E "s/<VirtualHost \\*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p var/cache var/log var/share
chown -R www-data:www-data var

# Build DATABASE_URL from Railway MySQL plugin vars (URL-encode user/password).
export DATABASE_URL="$(php -r '
$host = getenv("MYSQLHOST") ?: "";
$user = getenv("MYSQLUSER") ?: "";
$pass = getenv("MYSQLPASSWORD") ?: "";
$db   = getenv("MYSQLDATABASE") ?: "";
$port = getenv("MYSQLPORT") ?: "3306";
$mysqlUrl = getenv("MYSQL_URL") ?: "";
$existing = getenv("DATABASE_URL") ?: "";

if ($host !== "" && $user !== "" && $pass !== "" && $db !== "") {
    echo sprintf(
        "mysql://%s:%s@%s:%s/%s?serverVersion=9.4.0&charset=utf8mb4",
        rawurlencode($user),
        rawurlencode($pass),
        $host,
        $port,
        rawurlencode($db)
    );
    exit(0);
}

$url = $mysqlUrl !== "" ? $mysqlUrl : $existing;
if ($url === "") {
    fwrite(STDERR, "DATABASE_URL / MYSQL_* variables are missing.\n");
    exit(1);
}

if (!str_contains($url, "serverVersion=")) {
    $url .= (str_contains($url, "?") ? "&" : "?") . "serverVersion=9.4.0&charset=utf8mb4";
}
echo $url;
')"

echo "Database host: $(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_HOST) ?: "(empty)";')"
echo "Database name: $(php -r 'echo ltrim((string) parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_PATH), "/") ?: "(empty)";')"

# DNS / TCP diagnostics (no secrets printed)
php -r '
$host = parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_HOST) ?: "";
$port = parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_PORT) ?: 3306;
echo "DNS resolve: ";
$ip = gethostbyname($host);
echo ($ip === $host ? "FAILED ($host)" : $ip) . PHP_EOL;
$errno = 0; $errstr = "";
$fp = @fsockopen($host, (int) $port, $errno, $errstr, 5);
if ($fp) {
    echo "TCP {$host}:{$port} OK" . PHP_EOL;
    fclose($fp);
} else {
    echo "TCP {$host}:{$port} FAIL: {$errstr} ({$errno})" . PHP_EOL;
}
'

php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod

# Wait for MySQL with a real PDO probe (shows the actual error)
for i in $(seq 1 30); do
  if php -r '
$u = getenv("DATABASE_URL") ?: "";
$p = parse_url($u);
if (!$p || empty($p["host"])) { fwrite(STDERR, "Invalid DATABASE_URL\n"); exit(1); }
$user = isset($p["user"]) ? rawurldecode($p["user"]) : "";
$pass = isset($p["pass"]) ? rawurldecode($p["pass"]) : "";
$host = $p["host"];
$port = $p["port"] ?? 3306;
$db   = isset($p["path"]) ? ltrim($p["path"], "/") : "";
$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $pdo->query("SELECT 1");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
'; then
    echo "Database is ready."
    break
  fi
  echo "Waiting for database... (${i}/30)"
  sleep 2
  if [[ "${i}" -eq 30 ]]; then
    echo "Database still unreachable after 60s."
    exit 1
  fi
done

php bin/console doctrine:migrations:migrate --no-interaction --env=prod

exec apache2-foreground
