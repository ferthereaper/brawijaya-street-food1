<?php
require_once __DIR__ . '/koneksi.php';

/** Session disimpan di tabel php_sessions agar jalan di hosting serverless (Vercel dsb). */
class DbSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;
    private int $ttl = 86400; // 24 jam

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false
    {
        $st = $this->pdo->prepare(
            "SELECT data FROM php_sessions
             WHERE id = ? AND updated_at > NOW() - INTERVAL '1 second' * CAST(? AS INTEGER)"
        );
        $st->execute([$id, $this->ttl]);
        $data = $st->fetchColumn();
        return $data === false ? '' : $data;
    }

    public function write(string $id, string $data): bool
    {
        $st = $this->pdo->prepare(
            "INSERT INTO php_sessions (id, data, updated_at) VALUES (?, ?, NOW())
             ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, updated_at = NOW()"
        );
        return $st->execute([$id, $data]);
    }

    public function destroy(string $id): bool
    {
        return $this->pdo->prepare("DELETE FROM php_sessions WHERE id = ?")->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $st = $this->pdo->prepare(
            "DELETE FROM php_sessions WHERE updated_at < NOW() - INTERVAL '1 second' * CAST(? AS INTEGER)"
        );
        $st->execute([$this->ttl]);
        return $st->rowCount();
    }
}

if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    session_set_save_handler(new DbSessionHandler($pdo), true);

    $https = !empty($_SERVER['HTTPS'])
          || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
