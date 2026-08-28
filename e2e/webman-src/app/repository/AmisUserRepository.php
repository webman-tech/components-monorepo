<?php

declare(strict_types=1);

namespace app\repository;

use app\model\AmisUser;
use WebmanTech\AmisAdmin\Repository\EloquentRepository;

class AmisUserRepository extends EloquentRepository
{
    public const TABLE_SQL = 'CREATE TABLE IF NOT EXISTS amis_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        email TEXT NOT NULL,
        created_at TEXT,
        updated_at TEXT
    )';

    public function __construct()
    {
        parent::__construct(AmisUser::class);
        // e2e 用文件型 sqlite（runtime/e2e.sqlite，http worker 单进程、连接常驻），连接内惰性建表
        $this->model()->getConnection()->statement(self::TABLE_SQL);
    }

    protected function rules(string $scene): array
    {
        return [
            'username' => 'required|string|max:16',
            'email' => 'required|email',
        ];
    }

    protected function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'username' => '用户名',
            'email' => '邮箱',
        ];
    }
}
