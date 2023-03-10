<?php

namespace Dywily\Gaw\Entity;

/**
 * Class DMessage
 * @package Dywily\Gaw\Entity
 *
 * 有关单封邮件的基础信息
 *
 */
class DMessage
{
    public int    $id;
    public int    $is_read;
    public string $from;
    public string $email_user;
    public int    $edit_user_identifier;
    public string $sender_mailbox;
    public string $recipient_mailbox;
    public string $header;
    public string $in_reply_to;
    public string $references;
    public string $return_path;
    public string $msg_id;
    public string $gmail_time;
    public string $to;
    public string $cc;
    public string $bcc;
    public string $send_time;
    public string $email_time;
    public string $subject;
    public string $snippet;
    public string $label;
    public string $attachment;
    public int    $is_inbox;
    public string $gid;
    public string $thread_id;

    public string $rawContent;
    public string $content;
    /** @var array<DAttachment> $attachments */
    public array  $attachments;
    public string $contentWrap     = '';
    public string $attachmentsWrap = '';
}