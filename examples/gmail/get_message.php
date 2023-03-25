<?php


require __DIR__ . '/../../vendor/autoload.php';


use Dywily\Gaw\GoogleManager;
use Dywily\Gaw\Services\GmailService;
use Dywily\Gaw\Entity\DSearchFields;

$message = null;
try {
    $gm = new GoogleManager();
    $client = $gm->account('gmail');
    $gm->initService($client);
    $service = GmailService::instance();

    echo "初始: " . memory_get_usage() . "B\n";
    // 生成的时候不需要时间空间
    // 遍历时会生成附件的临时文件，可通过附件的path获取，保存到其他的地方，比如七牛、s3等云存储
    $searchFields = new DSearchFields();
    $searchFields->after = "2023/03/24";
    $searchFields->before = "2023/03/25";
    $message = $service->syncMail($searchFields, GmailService::MODE_COVER);
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
                <?php
                if ($message) {
                    foreach ($message as $m) { ?>
                        <li><?= $m->subject ?></li>
                    <?php }
                }
                // 之后可以删除临时文件
                //    $service->clearUpload();
                echo "使用: " . memory_get_usage() . "B\n";
                unset($message);
                echo "释放: " . memory_get_usage() . "B\n";
                echo "峰值: " . memory_get_peak_usage() . "B\n";
                ?>
            </ol>
        </div>
    </div>
</div>
</body>
</html>
