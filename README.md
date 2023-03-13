# google-api-php-wrap

- 基于[google/apiclient](https://github.com/googleapis/google-api-php-client) 的一个封装库
- 目前只有gmail的封装，可以轻松地同步邮件，展示邮件，发送邮件
- 基础使用可参考包中的examples文件夹
- 可参考配置文件 `config/gaw.php` 改写相关配置

## 配置OAuth客户端授权

* 可参考[google api 授权及使用 总结](https://duan1v.com/google_api/)

* 需要将下载的凭证文件移到配置中的{auth_path}/{credential_file}

## examples文件夹的使用

* 进入到examples父目录
  
* 执行

```sh
php -S localhost:8068
```

* 浏览器访问：http://localhost:8068/examples/index.php

## 如果用的是laravel

* 在 `routes/console.php` 中添加

```php
Artisan::command('dywily:gaw-install', function () {
    $dt = new Dywily\Gaw\Console\InstallCommand();
    $dt->handle();
});
```

* 执行

```shell
php artisan dywily:gaw-install
```

* 在 `config/app.php` 中的 `providers` 添加

```php 
App\Providers\GmailServiceProvider::class,
```

* 执行并访问：http://localhost:8068/getLabels

```shell
php artisan serve --port 8068
```

## PS
* 同步邮件关于使用yield返回邮件列表，与直接返回邮件列表，内存比照
  ![内存比照](https://static.duan1v.com/images/20230314001350.png)