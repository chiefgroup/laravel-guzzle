# chiefgroup-laravel-guzzle

## 安装

```
$ composer require chiefgroup/laravel-guzzle
```

## 配置文件

generate config file
```
$ php artisan vendor:publish --provider="Chiefgroup\Http\Providers\ServiceProvider"
```

## 使用

```php
    
    // Method : get\put\post\patch\delete

    $result = LaravelGuzzleHttp::get('api/users');

    $data = $headers = [];
    LaravelGuzzleHttp::post('http://xxx.com', $data, $headers);

    dd($result);

```

## License

The MIT License (MIT). Please see License File for more information.
