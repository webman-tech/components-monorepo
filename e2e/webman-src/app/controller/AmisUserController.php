<?php

declare(strict_types=1);

namespace app\controller;

use app\model\AmisUser;
use app\repository\AmisUserRepository;
use Webman\Http\Response;
use WebmanTech\AmisAdmin\Amis;
use WebmanTech\AmisAdmin\Controller\AmisSourceController;
use WebmanTech\AmisAdmin\Repository\RepositoryInterface;

class AmisUserController extends AmisSourceController
{
    protected function createRepository(): RepositoryInterface
    {
        return new AmisUserRepository();
    }

    protected function grid(): array
    {
        return ['id', 'username', 'email'];
    }

    protected function form(string $scene): array
    {
        return [
            Amis\FormField::make()->name('username')->label('用户名'),
            Amis\FormField::make()->name('email')->label('邮箱'),
        ];
    }

    protected function detail(): array
    {
        return ['id', 'username', 'email'];
    }

    /**
     * e2e 测试辅助：重置数据为固定数据集（DROP 重建保证 id 从 1 开始，避免 AUTOINCREMENT 递增漂移）
     */
    public function reset(): Response
    {
        $connection = (new AmisUserRepository())->model()->getConnection();
        $connection->statement('DROP TABLE IF EXISTS amis_users');
        $connection->statement(AmisUserRepository::TABLE_SQL);

        foreach ([
            ['username' => 'e2e-user-1', 'email' => 'user1@e2e.test'],
            ['username' => 'e2e-user-2', 'email' => 'user2@e2e.test'],
            ['username' => 'e2e-user-3', 'email' => 'user3@e2e.test'],
        ] as $item) {
            AmisUser::create($item);
        }

        return json(['status' => 0]);
    }
}
