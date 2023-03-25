<?php

require __DIR__ . '/../../vendor/autoload.php';


use Dywily\Gaw\GoogleManager;
use Dywily\Gaw\Services\GmailService;
use Dywily\Gaw\Entity\DMessage;

/** @var DMessage $msg */
$msg = null;
try {
    $gm = new GoogleManager();
    $client = $gm->account('gmail');
    $gm->initService($client);
    $service = GmailService::instance();
    // TODO 改成自己某个邮件的gid
    $msg = $service->pullMsg('186c1e15362aed11');
    $msg = $service->display($msg);
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
    <?php if ($msg) { ?>
        <div class="text-center" style="margin-top: 50px">
            <div class="mb-3">
                <h2><?= $msg->subject ?></h2>
            </div>
        </div>
        <div class="row" style="margin-top: 50px">
            <div class="mb-3">
                <?= $msg->contentWrap ?>
            </div>
        </div>
        <div class="row" style="margin-top: 50px">
            <div class="mb-3">
                <?= $msg->attachmentsWrap ?>
            </div>
        </div>
    <?php } ?>
</div>
</body>
</html>
