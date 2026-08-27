<?php

namespace App\Support;

class CrmWorkspaceContext
{
    protected static $workspaceId;

    public static function set($workspaceId) { static::$workspaceId = (int) $workspaceId; }
    public static function id() { return static::$workspaceId; }
    public static function clear() { static::$workspaceId = null; }
}
