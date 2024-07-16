This project provides a basic implementation of a Dependency Injection (DI) container in PHP. 

## Usage Example

```php
use Moaqz\Container\Container;

$container = new Container();
$userController = $container->get(UserController::class);
```

> [!WARNING] 
> This is a simple implementation meant for educational purposes and is not suitable for production environments. 
