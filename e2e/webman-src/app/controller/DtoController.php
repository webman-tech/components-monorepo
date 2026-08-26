<?php

namespace app\controller;

use app\dto\CreateUserDTO;
use OpenApi\Attributes as OA;
use WebmanTech\DTO\Exceptions\DTOValidateException;

/**
 * DTO 验证链路：真实 HTTP 请求 -> BaseRequestDTO::fromRequest -> 验证成功/失败
 */
class DtoController
{
    #[OA\Post(path: '/dto/create-user', summary: 'DTO 验证创建用户')]
    public function createUser()
    {
        try {
            $dto = CreateUserDTO::fromRequest();
        } catch (DTOValidateException $e) {
            // 注意：webman 2.x 的 json($data, $options) 第二参为编码选项而非状态码，需 withStatus
            return json([
                'code' => 422,
                'message' => $e->first(),
                'errors' => $e->getErrors(),
            ])->withStatus(422);
        }

        return json([
            'code' => 0,
            'data' => [
                'name' => $dto->name,
                'age' => $dto->age,
                'email' => $dto->email,
            ],
        ]);
    }
}
