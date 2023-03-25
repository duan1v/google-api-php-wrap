<?php

namespace Dywily\Gaw\Services;

use Carbon\Carbon;
use Dywily\Gaw\DHelper;
use Dywily\Gaw\Entity\DAttachment;
use Dywily\Gaw\Entity\DHeader;
use Dywily\Gaw\Entity\DLabel;
use Dywily\Gaw\Entity\DMessage;
use Dywily\Gaw\Entity\DSearchFields;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;

class GmailService
{
    use DHelper;

    const MODE_APPEND = 1;
    const MODE_COVER  = 2;
    const MODE_SINGLE = 4;

    private static ?GmailService $selfService = null;
    private static ?Gmail        $service     = null;
    private static array         $config      = [];
    private static string        $user        = '';

    private function __construct()
    {
    }

    private function __clone(): void
    {
    }

    public static function instance(): static
    {
        if (!(static::$selfService instanceof GmailService)) {
            static::$selfService = new GmailService();
        }
        return static::$selfService;
    }

    public static function service($client, $config): Gmail
    {
        if (!(static::$service instanceof Gmail)) {
            static::$config = $config;
            static::$user = $config['user'];
            static::$service = new Gmail($client);
        }
        return static::$service;
    }

    /**
     * @param Gmail|null $service
     * @return \Generator|Gmail\Label[]
     */
    public static function getLabels($service = null): \Generator|array
    {
        $service = $service ?? static::$service;
        $gl = function () use ($service) {
            $labels = $service->users_labels->listUsersLabels(static::$user)->getLabels();
            foreach ($labels as $label) {
                yield $label;
            }
        };
        return $gl();
    }

    public static function removeQuote($content): string
    {
        $html = new \DOMDocument();
        libxml_use_internal_errors(true);
        $html->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        // 如果html设置了charset，会有乱码
        if ($html->encoding && ($meta = $html->getElementsByTagName('meta'))) {
            for ($i = $meta->count() - 1; $i >= 0; $i--) {
                $meta->item($i)->parentNode->removeChild($meta->item($i));
            }
        }
        $element = $html->getElementsByTagName('blockquote');
        if ($element) {
            $item = $element->item(0);
            if ($item) {
                $pn = $item->parentNode;
                $element1 = $item->previousElementSibling;
                if ($element1)
                    $pn->removeChild($element1);
                $pn->removeChild($item);
            }
        }
        $element = $html->getElementById('isReplyContent');
        if ($element) {
            $pn = $element->parentNode;
            $element1 = $element->previousElementSibling;
            if ($element1)
                $pn->removeChild($element1);
            $pn->removeChild($element);
        }
        $element = $html->getElementById('appendonsend');
        if ($element) {
            $pn = $element->parentNode;
            while ($element1 = $element->nextElementSibling) {
                $pn->removeChild($element1);
            }
            $pn->removeChild($element);
        }
        $element = $html->getElementById('divRplyFwdMsg');
        if ($element) {
            $pn = $element->parentNode;
            $element1 = $element->nextElementSibling;
            if ($element1)
                $pn->removeChild($element1);
            $pn->removeChild($element);
        }
        $xpath = new \DOMXpath($html);
        $node = $xpath->query('//*[@class="gmail_quote"]')->item(0); // get first
        if ($node)
            $node->parentNode->removeChild($node);
        return $html->saveHTML();
    }

    /**
     * @param DMessage $dm
     * @return DMessage
     * @throws \ReflectionException
     */
    public static function display(DMessage $dm): DMessage
    {
        $pregRule = "/(<[img|IMG][^>]*?src=[\'|\"])(cid\:)([^>\"]*)([\'|\"][^>]*?)([\/]?>)/";
        $html = '';
        $fileManager = null;
        if (!empty(static::$config['file_manager'])) {
            $fileManager = new \ReflectionClass(static::$config['file_manager']);
        }
        $tempManager = null;
        if (!empty(static::$config['temp_manager'])) {
            $tempManager = new \ReflectionClass(static::$config['temp_manager']);
        }
        if (!empty($dm->attachments)) {
            $attachments = $dm->attachments;
            $images = [];
            foreach ($attachments as $a) {
                $fn = $a->originName;
                $cid = trim($a->cid, '<>');
                if ($fn == "undefined") {
                    continue;
                }
                $er = explode('.', $fn);
                $e = end($er);
                $p = '';
                if ($fileManager) {
                    $p = $fileManager->getMethod('getMsgPath')->invoke(null, $a);
                }
                if (in_array($e, DAttachment::IMG_EXT) && $cid) {
                    $images[$cid] = $p;
                } else {
                    $html .= $tempManager->getMethod('wrapAttachment')->invoke(null, $p, $fn);
                }
            }
            /** @var string $content */
            $content = preg_replace_callback($pregRule, function ($matches) use ($images, $tempManager) {
                $replace = $matches[2] . $matches[3];
                if (!empty($images[$matches[3]])) {
                    $replace = $images[$matches[3]];
                }
                return $tempManager->getMethod('wrapImage')->invoke(null, $replace, $matches);
            }, $dm->content);
            $dm->contentWrap = $content;
        }
        $dm->attachmentsWrap = $html;
        return $dm;
    }

    /**
     * @param $gid
     * @param $cf
     * @param $cacheContent
     * @param DSearchFields $searchFields
     * @param int $mode
     * @param int $maxResults
     * @return \Generator|DMessage[]
     */
    public static function getMsg($gid, $cf, $cacheContent, DSearchFields $searchFields, $mode = self::MODE_APPEND, $maxResults = 0): \Generator|array
    {
        $nextToken = 1;
        $cgFlag = 0;
        while ($nextToken) {
            $options = [
                'pageToken'  => $nextToken,
                'maxResults' => DHelper::getNoEmpty($maxResults, static::$config['max_results'] ?? 10),
            ];
            if ($q = $searchFields->getQ()) {
                $options ['q'] = $q;
            }
            $results = static::$service->users_messages
                ->listUsersMessages(static::$user, $options);
            $nextToken = $results->getNextPageToken();
            foreach ($results as $msg) {
                /** @var Gmail\Message $msg */
                $mid = $msg->getId();
                if ($cgFlag == 0 && $mode == self::MODE_APPEND) {
                    $cacheContent .= $mid . "\r\n";
                    file_put_contents($cf, $cacheContent);
                    $cgFlag = 1;
                }
                if (($mid == $gid && $mode == self::MODE_APPEND) || $mode == self::MODE_SINGLE) {
                    $nextToken = 0;
                    break;
                }
                $result = static::$service->users_messages->get(static::$user, $mid);
                yield static::setMsgInfo($result);
            }
        }
    }

    /**
     * @param DSearchFields $searchFields
     * @param int $mode
     * @param int $maxResults
     * @return \Generator|DMessage[]
     */
    public static function syncMail(DSearchFields $searchFields, $mode = self::MODE_APPEND, $maxResults = 0): \Generator|array
    {
        $cachePath = static::$config['cache_path'];
        $cf = $cachePath . '/cache.log';
        $time = "1900-01-01";
        $gid = '';
        $file = fopen($cf, "a+");
        $rn = 0;
        while (!feof($file)) {
            if ($rn > 1) {
                break;
            }
            $c = trim(fgets($file));
            if ($rn == 0 && $c)
                $time = $c;
            if ($rn == 1 && $c)
                $gid = $c;
            $rn++;
        }
        $cacheContent = Carbon::now()->toDateString() . "\r\n";
        fclose($file);
        $since = Carbon::parse($time);
        $fp = $cachePath . '/upload';
        file_exists($fp) or mkdir($fp, 0777, true);
        if ($mode == static::MODE_APPEND) {
            $searchFields->after = $since->rawFormat("Y/m/d");
        }
        return static::getMsg($gid, $cf, $cacheContent, $searchFields, $mode, $maxResults);
    }

    public function clearUpload()
    {
        $fp = static::$config['cache_path'] . '/upload';
        $this->deleteDir($fp);
    }

    public static function setMsgInfo(Message $result): DMessage
    {
        $mh = $result->getPayload()->getHeaders();
        $mid = $result->getId();
        $label = $result->getLabelIds() ? implode(',', $result->getLabelIds()) : '';
        $threadId = $result->getThreadId();
        $msg = new DMessage();
        $msg->gid = $mid;
        $msg->email_user = static::$user;
        $msg->thread_id = $threadId;
        $msg->snippet = $result->getSnippet();
        $msg->label = $label;
        $msg->header = json_encode($mh);
        $msg->is_inbox = (substr_count(strtolower($label), 'inbox') > 0 && substr_count(strtolower($label), 'sent') == 0) ? 1 : 0;
        foreach ($mh as $h) {
            $hn = strtolower($h->getName());
            $hv = $h->getValue();
            if (in_array($hn, ['cc', 'bcc', 'subject', "references"])) {
                $msg->$hn = $hv;
            } else if ($hn == "from") {
                $from = explode("<", $hv);
                $msg->from = $hv;
                $msg->sender_mailbox = rtrim(ltrim(trim(end($from)), "<"), ">");
            } else if ($hn == "to") {
                $to = explode("<", $hv);
                $msg->to = $hv;
                $msg->recipient_mailbox = rtrim(ltrim(trim(end($to)), "<"), ">");
            } else if ($hn == "date") {
                $msg->gmail_time = $hv;
                $msg->send_time = Carbon::parse($hv)->timezone('Antarctica/Troll')->toDateTimeString();
            } else if ($hn == "message-id") {
                $msg->msg_id = $hv;
            } else if ($hn == "in-reply-to") {
                $msg->in_reply_to = $hv;
            } else if ($hn == "return-path") {
                $msg->return_path = $hv;
            }
        }
        /** @var Gmail\MessagePart[] $part */
        $parts = [$result->payload];
        $msgParts = [];
        if ($parts) {
            static::getInfoByParts($parts, $mid, $msgParts);
        }
        $msg->content = empty($msgParts['content']) ? '' : $msgParts['content'];
        $msg->rawContent = empty($msgParts['raw_content']) ? '' : $msgParts['raw_content'];
        $msg->attachments = empty($msgParts['attachments']) ? [] : $msgParts['attachments'];
        $msg->attachment = json_encode($msg->attachments);
        return $msg;
    }

    /**
     * @param Gmail\MessagePart[] $parts
     * @param string $mid
     * @param array $msgParts
     */
    public static function getInfoByParts(array $parts, string $mid, &$msgParts = [])
    {
        $cachePath = static::$config['cache_path'];
        $pref = "/upload/" . static::$user . "_{$mid}/";
        $path = $cachePath . $pref;
        file_exists($path) or mkdir($path, 0777, true);

        foreach ($parts as $part) {
            if ($part->getFilename()
                && ($aid = $part->getBody()->getAttachmentId())) {
                $file = static::$service->users_messages_attachments->get(static::$user, $mid, $aid)->getData();
                $data = base64_decode(str_replace(["_", '-'], ["/", "+"], $file));
                $suffix = substr(strrchr($part->getFilename(), '.'), 0);
                $fileName = md5($part->getFilename()) . $suffix;
                $filePath = $path . $fileName;
                file_put_contents($filePath, $data);
                $ph = array_column($part->getHeaders(), 'value', 'name');
                $at = new DAttachment();
                $at->mime = $part->getMimeType();
                $at->originName = $part->getFilename();
                $at->name = $fileName;
                $at->cid = empty($ph['Content-ID']) ? '' : $ph['Content-ID'];
                $at->filePath = $pref;
                $at->path = $filePath;
                $msgParts['attachments'][$part->getPartId()] = $at;
                unset($file);
                unset($data);
            } elseif ($part->getMimeType() == "text/html"
                && empty($msgParts['content'])
                && empty($part->getFilename())
            ) {
                $rawContent = $part->getBody()->getData();
                $content = base64_decode(str_replace(["_", '-'], ["/", "+"], $rawContent));
                $msgParts['content'] = GmailService::removeQuote($content);
                $msgParts['raw_content'] = $rawContent;
                unset($content);
            }
            if ($part->getParts()) {
                static::getInfoByParts($part->getParts(), $mid, $msgParts);
            }
            unset($part);
        }
    }

    public static function createLabelBase($nl, $bgs = [], $fcs = []): array
    {
        $labels = [];
        try {
            $postBody = new Gmail\Label();
            $postBody->setName($nl);
            $postBody->setLabelListVisibility("labelShow");
            $postBody->setMessageListVisibility("show");
            $color = new Gmail\LabelColor();
            $color->setBackgroundColor(empty($bgs[$nl]) ? '#4986e7' : $bgs[$nl]);
            $color->setTextColor(empty($fcs[$nl]) ? '#ffffff' : $fcs[$nl]);
            $postBody->setColor($color);
            $result = static::$service->users_labels->create(static::$user, $postBody);
            $lid = $result->getId();
            $dl = new DLabel();
            $dl->emailUser = static::$user;
            $dl->labelId = $lid;
            $dl->labelName = $result->getName();
            $dl->msgVisibility = $result->getMessageListVisibility();
            $dl->labelVisibility = $result->getLabelListVisibility();
            $dl->type = 'user';
            $dl->textColor = $color ? $color->getTextColor() : '';
            $dl->bgColor = $color ? $color->getBackgroundColor() : '';
            $labels[$lid] = $dl->labelName;
        } catch (\Exception $e) {
            if ($e->getCode() == 409) {
                $gmailLabels = static::pullLabels();
                foreach ($gmailLabels as $gl) {
                    /** @var DLabel $gl */
                    if ($gl->labelName == $nl) {
                        $labels[$gl->labelId] = $gl->labelName;
                        break;
                    }
                }
            }
        }
        return $labels;
    }

    /**
     * @param array<string> $preAddLabelsName
     * @param array<string> $gids
     * @param array<string> $preRmLabelsName
     * @param array<string> $bgs
     * @param array<string> $fcs
     * @return array
     */
    public static function createOrTagLabels(array $preAddLabelsName, $gids = [], $preRmLabelsName = [], $bgs = [], $fcs = []): array
    {
        $gmailLabels = static::pullLabels();
        $allLabels = array_unique(array_merge($preAddLabelsName, $preRmLabelsName));
        $plsWrap = [];
        // gmail现有的便签
        foreach ($gmailLabels as $pl) {
            /** @var DLabel $pl */
            $plsWrap[$pl->labelId] = $pl->labelName;
        }
        // 需要被创建的标签
        $dln = array_diff($allLabels, array_values($plsWrap));
        if ($dln) {
            foreach ($dln as $nl) {
                $labelsBuf = static::createLabelBase($nl, $bgs, $fcs);
                $plsWrap = array_merge($plsWrap, $labelsBuf);
            }
        }
        $alIds = [];
        $rlIds = [];
        $needLabelIds = [];
        foreach ($plsWrap as $k => $lid) {
            if (in_array($lid, $preAddLabelsName)) {
                $alIds[] = $k;
            }
            if (in_array($lid, $preRmLabelsName)) {
                $rlIds[] = $k;
            }
            if (in_array($lid, $allLabels)) {
                $needLabelIds[$lid] = $k;
            }
        }

        if ($gids && $alIds) {
            $body = new Gmail\BatchModifyMessagesRequest();
            $body->setAddLabelIds($alIds);
            $body->setIds($gids);
            if ($rlIds) {
                // 此处数组必须不能是有键值的，即使键值是1打头
                $body->setRemoveLabelIds(array_values(array_diff($rlIds, $alIds)));
            }
            static::$service->users_messages->batchModify(static::$user, $body);
        }
        return $needLabelIds;
    }

    /**
     * @return array
     *
     * 重新拉取gmail中的labels
     */
    public static function pullLabels(): array
    {
        $results = static::$service->users_labels
            ->listUsersLabels(static::$user)
            ->getLabels();
        $data = [];
        foreach ($results as $result) {
            /** @var Gmail\Label $result */
            $lid = $result->getId();
            $color = $result->getColor();
            $name = $result->getName();
            $dl = new DLabel();
            $dl->emailUser = static::$user;
            $dl->labelId = $lid;
            $dl->labelName = $name;
            $dl->msgVisibility = $result->getMessageListVisibility() ?? '';
            $dl->labelVisibility = $result->getLabelListVisibility() ?? '';
            $dl->type = $result->getType();
            $dl->textColor = $color ? $color->getTextColor() : '';
            $dl->bgColor = $color ? $color->getBackgroundColor() : '';
            $data[$lid] = $dl;
        }
        return $data;
    }

    public static function deleteLabel($lid)
    {
        try {
            $user = static::$user;
            $service = static::$service;
            $service->users_labels->delete($user, $lid);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }

    public static function watchEmail()
    {
        try {
            $service = static::$service;
            $postBody = new Gmail\WatchRequest();
            $postBody->setTopicName('projects/fluted-oasis-249217/topics/sync-email');
            $postBody->setLabelIds(['INBOX']);
            while (true) {
                $res = $service->users->watch(static::$user, $postBody);
                var_dump($res);
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }

    /**
     * @param $tid
     * @return \Generator|DMessage[]
     */
    public static function syncMailWithThread($tid): \Generator|array
    {
        try {
            $user = static::$user;
            $service = static::$service;
            $res = $service->users_threads->get($user, $tid);
            $msgArr = $res->getMessages();
            foreach ($msgArr as $msg) {
                yield static::setMsgInfo($msg);
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }

    public static function pullMsg($gid): DMessage|bool
    {
        try {
            $result = static::$service->users_messages->get(static::$user, $gid);
            return static::setMsgInfo($result);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
        return false;
    }

    /**
     * @param DHeader $header
     * @param string $body
     * @param string $attachmentIds
     * @param DMessage|null $replyEmail
     * @return Message
     * @throws \ReflectionException
     */
    public static function sendMsg(DHeader $header, string $body, $attachmentIds = '', DMessage $replyEmail = null): Message
    {
        $user = static::$user;
        /** @var Gmail $service */
        $service = static::$service;
        $message = new Message();
        $boundary = uniqid(rand(), true);
        $headerCharset = $charset = 'utf-8';

        $class = null;
        if (!empty(static::$config['file_manager'])) {
            $class = new \ReflectionClass(static::$config['file_manager']);
        }

        $getFile = function ($class, $path) {
            if ($class) {
                $file = $class->getMethod('getFile')->invoke(null, $path);
                $fileSize = $class->getMethod('getFileSize')->invoke(null, $path);
            } else {
                $file = file_get_contents($path);
                $fileSize = strlen($file);
            }
            return compact('file', 'fileSize');
        };

        $html = new \DOMDocument();
        libxml_use_internal_errors(true);
        $html->loadHTML(mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8'));
        if ($html->encoding) {
            $meta = $html->getElementsByTagName('meta');
            for ($i = $meta->count() - 1; $i >= 0; $i--) {
                $meta->item($i)->parentNode->removeChild($meta->item($i));
            }
        }
        $imgInfo = [];
        $xpath = new \DOMXpath($html);
        $imgList = $xpath->query("//img");
        // 图片附件信息改写
        foreach ($imgList as $key => $img) {
            if (!$img) {
                continue;
            }
            /** @var \DOMNode $img */
            $os = $img->getAttribute('src');
            $pathInfo = pathinfo($os);
            $file = '';
            $fileSize = 0;
            extract($getFile($class, $os));
            if (!$fileSize) {
                continue;
            }
            $cidImg = uniqid(rand());
            $img->setAttribute('src', 'cid:' . $cidImg);
            $fileName = $pathInfo['basename'];
            $imgInfo = array_merge($imgInfo, [
                "--{$boundary}",
                "Content-Type: " . static::mime($pathInfo['extension']) . "; name=\"{$fileName}\";",
                "Content-ID: <{$cidImg}>",
                "Content-Description: {$fileName};",
                "Content-Disposition: inline; filename=\"{$fileName}\"; size=" . $fileSize . ";",
                "Content-Transfer-Encoding: base64\r\n",
                chunk_split(base64_encode($file)),
            ]);
        }
        $body = $html->saveHTML();
        $c = quoted_printable_encode($body);

        $attachments = [];
        $as = array_unique(explode(',', $attachmentIds));
        if ($as && $class) {
            $attachments = $class->getMethod('getAttachmentWithId')->invoke(null, $as);
        }

        $attachmentInfo = [];
        if ($attachments) {
            foreach ($attachments as $attachment) {
                /** @var DAttachment $attachment */
                $path = $attachment->path;
                $file = '';
                $fileSize = 0;
                extract($getFile($class, $path));;
                if (!$fileSize) {
                    continue;
                }
                $cid = uniqid(rand());
                $fileName = $attachment->originName;
                $fi = pathinfo($path);
                $attachmentInfo = array_merge($attachmentInfo, [
                    "--{$boundary}",
                    "Content-Type: " . static::mime($fi['extension']) . "; name=\"{$fileName}\";",
                    "Content-ID: <{$cid}>",
                    "Content-Description: {$fileName};",
                    "Content-Disposition: attachment; filename=\"{$fileName}\"; size=" . $fileSize . ";",
                    "Content-Transfer-Encoding: base64\r\n",
                    chunk_split(base64_encode($file), 76, "\n"),
                ]);
            }
        }
        $rawArr = [
            "From: " . ($header->from ?? ("<" . $user . ">")),
            "To: " . $header->to,
        ];
        if ($header->cc) {
            $rawArr[] = "Cc: " . $header->cc;
        }
        if ($replyEmail) {
            $rawArr[] = "In-Reply-To: " . $replyEmail->msg_id;
            $message->setThreadId($replyEmail->thread_id);
        }
        $rawArr = array_merge($rawArr, [
            "MIME-Version: 1.0",
            "Content-type: Multipart/Mixed; boundary=\"{$boundary}\"",
            "Subject: =?{$headerCharset}?B?" . base64_encode($header->subject) . "?=\r\n",
            "--{$boundary}",
            "Content-Type: text/html; charset={$charset}",
            "Content-Transfer-Encoding: quoted-printable\r\n",
            $c
        ]);

        $message->setRaw(base64_encode(implode("\r\n", array_merge($rawArr, $imgInfo, $attachmentInfo))));
        return $service->users_messages->send($user, $message);
    }
}
