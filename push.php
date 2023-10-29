<?php

class DatabasePusher
{
    public function push(): void
    {
        try {
            // Попытка подключения к базе данных
            throw new Exception("База данных не подключена.");

        } catch (Exception $e) {
            file_put_contents('mysql.log', $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    }
}

$dbPusher = new DatabasePusher();
$dbPusher->push();
