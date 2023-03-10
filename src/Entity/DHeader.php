<?php

namespace Dywily\Gaw\Entity;

/**
 * Class DHeader
 * @package Dywily\Gaw\Entity
 */
class DHeader
{
    public string $subject;
    public string $from;
    public string $to;
    public string $cc = '';

    public function __construct($subject, $from = '', $to = '', $cc = '')
    {
        $this->subject = $subject;
        $this->from = $from;
        $this->to = $to;
        $this->cc = $cc;
    }
}
