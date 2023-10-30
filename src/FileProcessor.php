<?php

namespace App;

class FileProcessor
{
    protected string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function process(): void
    {
        $fileContent = file_get_contents($this->filePath);
        if ($fileContent === false) {
            file_put_contents('errors.log', "Failed to read the file: {$this->filePath}\n", FILE_APPEND);
            return;
        }

        $words = preg_split('/\s+|\,|\.|\;|\:|\-|\!|\?/', $fileContent);
        $lettersCount = [];

        foreach ($words as $word) {
            $word = trim($word);
            if (empty($word)) {
                continue;
            }

            $firstLetter = mb_strtolower(mb_substr($word, 0, 1));
            $dirPath = __DIR__ . "/../library/$firstLetter";

            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0755, true);
            }

            file_put_contents("$dirPath/words.txt", $word . PHP_EOL, FILE_APPEND);

            $letterOccurrences = mb_substr_count(mb_strtolower($word), $firstLetter);
            $lettersCount[$firstLetter] = ($lettersCount[$firstLetter] ?? 0) + $letterOccurrences;
        }

        foreach ($lettersCount as $letter => $count) {
            file_put_contents(__DIR__ . "/../library/$letter/count.txt", $count);
        }
    }
}
