<?php
declare(strict_types=1);
final class HttpError extends RuntimeException {
    public function __construct(public readonly int $status, string $message) { parent::__construct($message); }
}
final class Http {
    public static function json(mixed $data, int $status = 200): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
    public static function body(): array {
        if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) throw new HttpError(413, 'Requête trop volumineuse.');
        if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') throw new HttpError(415, 'Le format JSON est obligatoire.');
        try { $data = json_decode(file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR); }
        catch (JsonException) { throw new HttpError(400, 'JSON invalide.'); }
        if (!is_array($data) || array_is_list($data) && $data !== []) throw new HttpError(400, 'Un objet JSON est attendu.');
        return $data;
    }
    public static function text(array $data, string $key, int $max, bool $required = true): ?string {
        $value = $data[$key] ?? null;
        if ($value === null || $value === '') {
            if ($required) throw new HttpError(422, 'Champ obligatoire : ' . $key);
            return null;
        }
        if (!is_string($value) || mb_strlen($value) > $max || ($required && trim($value) === '')) throw new HttpError(422, 'Champ invalide : ' . $key);
        return trim($value);
    }
    public static function id(mixed $value): int {
        if (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) throw new HttpError(422, 'Identifiant invalide.');
        return (int)$value;
    }
}
