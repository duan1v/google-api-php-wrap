<?php

require __DIR__ . '/../../vendor/autoload.php';


use Dywily\Gaw\GoogleManager;
use Dywily\Gaw\Services\GmailService;

$labels = [];
try {
    $gm = new GoogleManager();
    $client = $gm->account('gmail');
    $gm->initService($client);
    $service = GmailService::instance();
    $labels = $service->createOrTagLabels([
        'test1',
        'test2'
    ]);
} catch (Exception $e) {
    var_dump($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gmail test cases</title>
    <link rel="stylesheet" href="../css/bootstrap.css"/>
</head>
<body>
<div class="container">
    <div class="row text-center">
        <div class="mb-3 col-sm-12">
            <h1>gmail test cases</h1>
        </div>
    </div>
    <div class="row" style="margin-top: 50px">
        <div class="mb-3">
            <ol>
                <?php foreach ($labels as $id) { ?>
                    <li><?= $id ?></li>
                <?php } ?>
            </ol>
        </div>
    </div>
</div>
</body>
</html>
