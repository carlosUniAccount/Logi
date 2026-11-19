<?php
declare(strict_types=1);

function getDb(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dataDir = __DIR__ . '/../data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }

    $pdo = new PDO('sqlite:' . $dataDir . '/logistica.sqlite', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS produtos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            preco REAL NOT NULL DEFAULT 0,
            peso_kg REAL NOT NULL DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS enderecos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cep TEXT NOT NULL,
            logradouro TEXT NOT NULL,
            numero TEXT NOT NULL,
            bairro TEXT NOT NULL,
            cidade TEXT NOT NULL,
            uf TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS transportadores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            documento TEXT NOT NULL,
            veiculo TEXT NOT NULL,
            telefone TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS fretes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            data_frete TEXT NOT NULL,
            transportador_id INTEGER NOT NULL,
            origem_id INTEGER NOT NULL,
            destino_id INTEGER NOT NULL,
            status TEXT NOT NULL DEFAULT 'Em trânsito',
            FOREIGN KEY (transportador_id) REFERENCES transportadores(id),
            FOREIGN KEY (origem_id) REFERENCES enderecos(id),
            FOREIGN KEY (destino_id) REFERENCES enderecos(id)
        );

        CREATE TABLE IF NOT EXISTS frete_itens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            frete_id INTEGER NOT NULL,
            produto_id INTEGER NOT NULL,
            quantidade INTEGER NOT NULL DEFAULT 1,
            FOREIGN KEY (frete_id) REFERENCES fretes(id) ON DELETE CASCADE,
            FOREIGN KEY (produto_id) REFERENCES produtos(id)
        );
    ");

    return $pdo;
}