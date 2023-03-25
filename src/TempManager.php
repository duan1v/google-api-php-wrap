<?php


namespace Dywily\Gaw;


use Dywily\Gaw\Entity\DAttachment;

class TempManager
{
    public static function wrapAttachment($p, $fn): string
    {
        return "<a href='{$p}' target='_blank' download='{$fn}'>{$fn}</a><br/>";
    }

    public static function wrapImage($replace, $matches): string
    {
        return "<a style='display: block' href='{$replace}' target='_blank'>{$matches[1]}{$replace}{$matches[4]}{$matches[5]}</a>";
    }
}
