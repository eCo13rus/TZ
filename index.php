<?php

require_once 'vendor/autoload.php';

use App\FileProcessor;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$loader = new FilesystemLoader('templates');
$twig = new Environment($loader);

$message = null;

if (php_sapi_name() == "cli") {
    echo "Файл успешно обработан\n";
    $filePath = 'test.txt';
    $fileProcessor = new FileProcessor($filePath);
    $fileProcessor->process();
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file'])) {
        $message = 'Произошла ошибка при загрузке файла.';
        echo $twig->render('upload_form.twig', ['message' => $message]);
        return;
    }

    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Пожалуйста загрузите файл.';
        echo $twig->render('upload_form.twig', ['message' => $message]);
        return;
    }

    $uploadedFile = $_FILES['file']['tmp_name'];
    $fileProcessor = new FileProcessor($uploadedFile);
    $fileProcessor->process();
    $message = "Файл успешно добавлен.";
}

echo $twig->render('upload_form.twig', ['message' => $message]);

