# Concrete CMS DebugBar

A package to integrate [PHP Debug Bar](http://phpdebugbar.com/) with Concrete CMS.

## Usage

### Messages

You can add messages to this tab using compatible usage with [PSR-3 logger](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md).

```php
\Core::make('debugbar/messages')->info('hello world');
\Core::make('debugbar/messages')->info($object);
```

![Messages Tab](./screenshots/messages.png)

### Timeline

You can check a timeline within the runtime.
You can also add log the execution time of a particular operation.

```php
\Core::make('debugbar/time')->startMeasure('longop', 'My long operation');
sleep(2);
\Core::make('debugbar/time')->stopMeasure('longop');
```

![Timeline Tab](./screenshots/timeline.png)

### Request

You can check how Concrete retrieve request data in this tab.

![Request Tab](./screenshots/request.png)

### Session

You can check values stored in the current session.

![Session Tab](./screenshots/session.png)

### Database

You can check all sql queries on current request in this tab.

![Database Tab](./screenshots/database.png)

### Logs

You can check application logs (same as dashboard/reports/logs but you can quick access!).

![Logs Tab](./screenshots/logs.png)


### Environment

Get some information about the server/application environment.

![Environment Tab](./screenshots/environment.png)
