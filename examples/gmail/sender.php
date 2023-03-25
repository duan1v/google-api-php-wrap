<?php

require __DIR__ . '/../../vendor/autoload.php';

use Dywily\Gaw\Entity\DHeader;
use Dywily\Gaw\GoogleManager;
use Dywily\Gaw\Services\GmailService;

$res = [
    'status' => 200,
    'msg'    => 'success'
];

function returnJson($res)
{
    echo json_encode($res);
    die();
}

header('Content-Type: application/json');
try {
    $content = $_POST['content'];
    if (!strip_tags($content)) {
        throw new Exception('content could not null.', 500001);
    }
    $attachments = $_POST['attachments'];
    $subject = $_POST['subject'];
    if (!$subject) {
        throw new Exception('current email has not title.', 500002);
    }
    $to = $_POST['to'];
    if (!$to) {
        throw new Exception('current email has not recipient.', 500003);
    }
    $cc = $_POST['cc'] ?? '';
    $from = 'detachedgod@gmail.com';
    $header = new DHeader($subject, $from, $to, $cc);
    $gm = new GoogleManager();
    $client = $gm->account('gmail');
    $gm->initService($client);
    $service = GmailService::instance();
    $msg = $service->sendMsg($header, $content, $attachments);
} catch (Exception $e) {
    $res = [
        'status' => $e->getCode(),
        'msg'    => $e->getMessage()
    ];
} finally {
    returnJson($res);
}