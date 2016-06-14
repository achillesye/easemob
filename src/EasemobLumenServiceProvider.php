<?php
namespace Easemob\Chat;

use Illuminate\Support\ServiceProvider;
/**
 *
 * Date: 16/6/7
 * Author: eric <eric@winhu.com>
 */
class EasemobLumenServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //使用 vendor:publish 的时候复制配置文件到 config 目录
        $this->publishes([
           __DIR__.'/config/easemob.php' => base_path().'/config/easemob.php',
        ]);

    }
    public function register()
    {

        $this->mergeConfigFrom(
            __DIR__ . '/config/easemob.php','easemob'
        );

       $this->app->singleton('easemob', function ($app) {
            return new Easemob();
       });
    }
}
