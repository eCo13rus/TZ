<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli; // Экземпляр mysqli для работы с базой данных.
    private ?string $skipValue = null; // Специальное значение для пропуска аргументов в запросе.

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        $query = $this->replaceBracePlaceholders($query, $args); // Замена плейсхолдеров в фигурных скобках значениями из $args.
        $query = $this->replaceQuestionMarkPlaceholders($query, $args); // Замена плейсхолдеров на вопросительные знаки значениями из $args.

        $this->skipValue = null; // Сброс специального значения для пропуска после построения запроса.

        return $query;
    }

    private function replaceBracePlaceholders(string $query, array $args): string
    {
        $skipValue = $this->skip(); // Получение специального значения для пропуска.

        // Замена плейсхолдеров в фигурных скобках.
        return preg_replace_callback('/\{([^{}]*)\}/', function ($match) use ($args, $skipValue) {
            foreach ($args as $arg) {
                if ($arg === $skipValue) {
                    return ''; // Пропуск значения, если оно совпадает со специальным значением пропуска.
                }
            }
            return $match[1]; // Возвращение значения из $args.
        }, $query);
    }

    private function replaceQuestionMarkPlaceholders(string $query, array &$args): string
    {
        $offset = 0; // Начальное смещение для поиска в строке.
        $skipValue = $this->skip();

        // Поиск и замена плейсхолдеров на вопросительные знаки.
        while (preg_match('/\?#[a-z]*|\?[dfasn]?/', $query, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            $match = $matches[0][0]; // Найденный плейсхолдер.
            $position = $matches[0][1]; // Позиция плейсхолдера в запросе.
            $value = array_shift($args); // Извлечение следующего значения из $args.

            if ($value === $skipValue) {
                $offset += strlen($match); // Смещение вперёд, если значение нужно пропустить.
                continue;
            }

            switch ($match) {
                case '?d':
                    $replacement = intval($value);
                    break;
                case '?f':
                    $replacement = floatval($value);
                    break;
                case '?a':
                    if (!is_array($value)) {
                        throw new Exception('Parameter ?a expects an array.');
                    }
                    $replacement = $this->formatArray($value);
                    break;
                case '?#':
                    if (is_array($value)) {
                        $replacement = join(', ', array_map(function ($v) {
                            return '`' . $this->mysqli->real_escape_string($v) . '`';
                        }, $value));
                    } else {
                        $replacement = '`' . $this->mysqli->real_escape_string($value) . '`';
                    }
                    break;
                case '?n':
                    if (!is_string($value)) {
                        throw new Exception('Identifier must be a string.');
                    }
                    $replacement = $this->mysqli->real_escape_string($value);
                    break;
                default:
                    $replacement = $this->escapeValue($value);
                    break;
            }

            $query = substr_replace($query, $replacement, $position, strlen($match));
            $offset = $position + strlen($replacement);
        }

        return $query;
    }

    private function formatArray(array $array): string
    {
        // Определение ассоциативности массива.
        $isAssoc = array_keys($array) !== range(0, count($array) - 1);

        $result = [];
        foreach ($array as $key => $value) {
            if ($isAssoc) {
                // Формирование строки для SQL из ассоциативного или индексированного массива.
                $result[] = "`" . $this->mysqli->real_escape_string($key) . "` = " . $this->escapeValue($value);
            } else {
                $result[] = $this->escapeValue($value);
            }
        }
        return implode(', ', $result);
    }

    private function escapeValue($value): string
    {
        // Экранирование и форматирование значения в зависимости от его типа.
        if (is_null($value)) return 'NULL';
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_int($value) || is_float($value)) return (string)$value;
        if (!is_scalar($value)) {
            throw new Exception('Only scalar values are allowed.');
        }

        return '\'' . $this->mysqli->real_escape_string($value) . '\''; // Экранирование строки для SQL.
    }

    public function skip()
    {
        // Генерация и возврат специального значения для пропуска, если оно ещё не было установлено.
        if (null === $this->skipValue) {
            $this->skipValue = 'FpDbTest_Database_Skip_Special_Value_' . uniqid();
        }
        return $this->skipValue;
    }
}
