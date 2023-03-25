<?php


namespace Dywily\Gaw\Entity;


class DSearchFields
{
    // 收件箱
    const IN_INBOX = "in:inbox";
    // 已删除邮件
    const IN_TRASH = "in:trash";
    // 草稿
    const IN_DRAFTS = "in:drafts";
    // 聊天记录
    const IN_CHATS = "in:chats";
    // 垃圾邮件
    const IN_SPAM = "in:spam";
    // 已读邮件
    const IS_READ = "is:read";
    // 未读邮件
    const IS_UNREAD = "is:unread";
    // 已发邮件
    const IS_SENT = "is:sent";
    // 已加星标
    const IS_STARRED = "is:starred";
    // 社交
    const CATEGORY_SOCIAL = "category:social";
    // 动态
    const CATEGORY_UPDATES = "category:updates";
    //邮件、垃圾邮件、已删除邮件
    const IN_ANYWHERE = "in:anywhere";
    // 论坛
    const CATEGORY_FORUMS = "category:forums";
    // 推广
    const CATEGORY_PROMOTIONS = "category:promotions";
    // 标签
    const IN_LABEL = "label:%s";

    // smaller:1K|larger:2M|smaller:2 // 数字可变
    const SMALLER_THAN = "smaller:%s";
    const LARGER_THAN  = "larger:%s";

    public string       $in         = "";
    public string|array $from       = "";
    public string|array $to         = "";
    public string       $subject    = "";
    public string       $content    = "";
    public string       $notContain = "";
    // 带有附件
    public bool $hasAttachment = false;
    // 不包括聊天
    public bool   $notInChat = false;
    public string $size      = "";
    // after:2023/3/19
    public string $after = "";
    // before:2023/4/3
    public string $before = "";

    public function getQ(): string
    {
        $ql = [];
        if ($this->in) {
            $ql[] = $this->in;
        }
        if ($this->from) {
            $ql[] = sprintf("from:(%s)", is_array($this->from) ? implode(',', $this->from) : $this->from);
        }
        if ($this->to) {
            $ql[] = sprintf("to:(%s)", is_array($this->to) ? implode(',', $this->to) : $this->to);
        }
        if ($this->subject) {
            $ql[] = sprintf("subject:(%s)", $this->subject);
        }
        if ($this->content) {
            $ql[] = $this->content;
        }
        if ($this->notContain) {
            $schema = strpos($this->notContain, ' ') ? "-{%s}" : "-%s";
            $ql[] = sprintf($schema, $this->notContain);
        }
        if ($this->hasAttachment) {
            $ql[] = "has:attachment";
        }
        if ($this->notInChat) {
            $ql[] = "-in:chats";
        }
        if ($this->size) {
            $ql[] = $this->size;
        }
        if ($this->after) {
            $ql[] = sprintf("after:%s", $this->after);
        }
        if ($this->before) {
            $ql[] = sprintf("before:%s", $this->before);
        }
        return $ql ? implode(' ', $ql) : "";
    }

}
