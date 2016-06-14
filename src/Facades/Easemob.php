<?php
namespace Easemob\Chat\Facades;

use Illuminate\Support\Facades\Facade;
/**
 *
 * Date: 16/6/7
 * Author: eric <eric@winhu.com>
 */
class Easemob extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'easemob';
    }
}
