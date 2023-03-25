# Google-api-php-wrap

- A package base from [google/apiclient](https://github.com/googleapis/google-api-php-client)
- Currently only packaging the gmail, you can easily manage syncing email, displaying email, and sending email
- The examples folder in the reference package is used for the basics
- you can see configuration file `config/gaw.php` and overwrite related configuration

## Configure OAuth client authorization

* refer: [google api 授权及使用 总结](https://duan1v.com/google_api/)

* You need to move the downloaded credentials file into the configuration {auth_path}/{credential_file}

## Usage of Examples folder

* Enter parent directory of the examples
  
* Execute

```sh
php -S localhost:8068
```

* Browser view: http://localhost:8068/examples/index.php

## If you use the laravel

* Add the following into `routes/console.php`

```php
Artisan::command('dywily:gaw-install', function () {
    $dt = new Dywily\Gaw\Console\InstallCommand();
    $dt->handle();
});
```

* Execute

```shell
php artisan dywily:gaw-install
```

* Add the following into `providers` of `config/app.php` 

```php 
App\Providers\GmailServiceProvider::class,
```

* Execute and view: http://localhost:8068/getLabels

```shell
php artisan serve --port 8068
```

## PS
* Usage of memory when synchronous mail is about using yield to retrieve the mailing list, vs directly return the mailing list
  ![内存比照](https://static.duan1v.com/images/20230314001350.png)