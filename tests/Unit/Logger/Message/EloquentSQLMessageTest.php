<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use WebmanTech\CommonUtils\Testing\TestLogger;
use WebmanTech\Logger\Message\EloquentSQLMessage;
use function WebmanTech\CommonUtils\runtime_path;

beforeEach(function () {
    $this->connection = new Connection(
        pdo: new PDO('sqlite:' . runtime_path('eloquentSQLMessageSqlite.db')),
        database: 'test',
        config: [
            'name' => 'sqlite',
        ],
    );
});

test('simple', function () {
    $logger = TestLogger::channel('sql');
    $logger->flush();

    $message = new EloquentSQLMessage();

    // 默认不记录 select
    $event = new QueryExecuted(
        sql: 'select * from users',
        bindings: [],
        time: 20,
        connection: $this->connection,
    );
    $message->handle($event);

    // 默认记录所有非 select
    $event = new QueryExecuted(
        sql: 'update users set username = ? where id = ?',
        bindings: ['abc', 1],
        time: 214,
        connection: $this->connection,
    );
    $message->handle($event);

    $logs = $logger->getAll();
    expect(count($logs))->toBe(1)
        ->and($logs[0]['level'])->toBe('INFO')
        ->and($logs[0]['message'])->toBe("update users set username = 'abc' where id = 1")
        ->and($logs[0]['context']['cost'])->toBe(214);
});

test('showConnectionName', function () {
    $logger = TestLogger::channel('sql');
    $logger->flush();

    $message = new EloquentSQLMessage([
        'showConnectionName' => true,
    ]);

    $event = new QueryExecuted(
        sql: 'update users set username = ? where id = ?',
        bindings: ['abc', 1],
        time: 214,
        connection: $this->connection,
    );
    $message->handle($event);

    $logs = $logger->getAll();
    expect(count($logs))->toBe(1)
        ->and($logs[0]['context']['connectionName'])->toBe('sqlite');
});

test('skip', function () {
    $logger = TestLogger::channel('sql');
    $logger->flush();

    $message = new EloquentSQLMessage([
        'ignoreSql' => [
            'select 1',
            'select * from users2',
        ], // 设定记录所有 sql
        'ignoreSqlPattern' => [
            '/select .*? from (customer|admins)/i'
        ],
        'logMinTimeMS' => 0,
    ]);

    $sqls = [
        'select 1', // 忽略
        'select * from users', // 不会忽略
        'select * from users2', // 忽略
        'select * from admins where id = 123', // 忽略
        'select * from customer where id = 123', // 忽略
        'select * from a_customer where id = 123', // 不会忽略
    ];

    foreach ($sqls as $sql) {
        $event = new QueryExecuted(
            sql: $sql,
            bindings: [],
            time: 1000,
            connection: $this->connection,
        );
        $message->handle($event);
    }

    $logs = $logger->getAll();
    expect(count($logs))->toBe(2);
});

test('time and level', function () {
    $logger = TestLogger::channel('sql');
    $logger->flush();

    $message = new EloquentSQLMessage();

    // 1000 以上的 select 会记录
    $event = new QueryExecuted(
        sql: 'select * from users',
        bindings: [],
        time: 1000,
        connection: $this->connection,
    );
    $message->handle($event);

    // 1500 以上 warning
    $event = new QueryExecuted(
        sql: 'select * from users2',
        bindings: [],
        time: 2000,
        connection: $this->connection,
    );
    $message->handle($event);

    // 10000 以上 error
    $event = new QueryExecuted(
        sql: 'select * from users3',
        bindings: [],
        time: 10000,
        connection: $this->connection,
    );
    $message->handle($event);

    $logs = $logger->getAll();
    expect(count($logs))->toBe(3)
        ->and($logs[0]['level'])->toBe('INFO')
        ->and($logs[1]['level'])->toBe('WARNING')
        ->and($logs[2]['level'])->toBe('ERROR');
});

test('switch enable', function () {
    $logger = TestLogger::channel('sql');
    $logger->flush();

    $message = new EloquentSQLMessage([
        'logMinTimeMS' => 0,
    ]);

    $event = new QueryExecuted(
        sql: 'select * from users1',
        bindings: [],
        time: 1000,
        connection: $this->connection,
    );
    $message->handle($event);

    $message->switchEnable(false); // 临时关闭

    $event = new QueryExecuted(
        sql: 'select * from users2',
        bindings: [],
        time: 1000,
        connection: $this->connection,
    );
    $message->handle($event);

    $message->switchEnable(true); // 再开启

    $event = new QueryExecuted(
        sql: 'select * from users3',
        bindings: [],
        time: 1000,
        connection: $this->connection,
    );
    $message->handle($event);

    $logs = $logger->getAll();
    expect(count($logs))->toBe(2)
        ->and($logs[0]['message'])->toContain('users1')
        ->and($logs[1]['message'])->toContain('users3');
});

test('check is not select', function () {
    $logger = TestLogger::channel('sql');
    $logger->flush();

    $message = new EloquentSQLMessage([
        'logMinTimeMS' => 1200,
        'checkIsSqlNotSelect' => function ($sql, QueryExecuted $event) {
            return isset($event->bindings['special']) && str_contains($sql, 'users1');
        },
    ]);

    $event = new QueryExecuted(
        sql: 'select * from users1',
        bindings: ['special' => 1],
        time: 1000,
        connection: $this->connection,
    );
    $message->handle($event);

    $event = new QueryExecuted(
        sql: 'select * from users1',
        bindings: [],
        time: 1000,
        connection: $this->connection,
    );
    $message->handle($event);

    $event = new QueryExecuted(
        sql: 'select * from users2',
        bindings: [],
        time: 1000,
        connection: $this->connection,
    );
    $message->handle($event);

    $logs = $logger->getAll();
    expect(count($logs))->toBe(1)
        ->and($logs[0]['message'])->toContain('users1');
});

test('extraInfo', function () {
    $logger = TestLogger::channel('sql');
    $logger->flush();

    $message = new EloquentSQLMessage([
        'logMinTimeMS' => 0, // 记录所有 sql
        'extraInfo' => function (QueryExecuted $event) {
            return [
                'tenant' => 'demo',
                'connection_info' => [
                    'name' => $event->connectionName,
                    'database' => $event->connection->getDatabaseName(),
                ],
            ];
        },
    ]);

    $event = new QueryExecuted(
        sql: 'select * from users',
        bindings: [],
        time: 100,
        connection: $this->connection,
    );
    $message->handle($event);

    $logs = $logger->getAll();
    expect(count($logs))->toBe(1)
        ->and($logs[0]['context']['tenant'])->toBe('demo')
        ->and($logs[0]['context']['connection_info'])->toBe([
            'name' => 'sqlite',
            'database' => 'test',
        ]);
});
