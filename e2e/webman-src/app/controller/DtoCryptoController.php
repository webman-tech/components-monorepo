<?php

namespace app\controller;

use app\dto\CryptoRequestDTO;
use app\dto\CryptoResponseDTO;
use WebmanTech\DTO\Exceptions\DTOProcessorException;
use WebmanTech\DTO\Exceptions\DTOValidateException;

/**
 * DTO 字段加解密链路：真实 HTTP 请求 -> 入参解密 -> 验证 -> 出参加密
 *
 * 解密失败（密文被篡改等）由处理器抛 DTOProcessorException，属客户端错误，转 400
 */
class DtoCryptoController
{
    public function create()
    {
        try {
            $dto = CryptoRequestDTO::fromRequest();
        } catch (DTOValidateException $e) {
            return json([
                'code' => 422,
                'errors' => $e->getErrors(),
            ])->withStatus(422);
        } catch (DTOProcessorException $e) {
            return json([
                'code' => 400,
                'processor' => $e->processor,
                'field' => $e->fieldName,
            ])->withStatus(400);
        }

        // 出参经 toArray() 加密后再返回，模拟真实响应链路
        $data = CryptoResponseDTO::fromData([
            'result' => 'ok',
            'token' => 'plain-token',
            'tags' => ['alpha', 'beta'],
            'count' => 2,
        ])->toArray();

        return json([
            'code' => 0,
            'data' => [
                // 解密后的明文
                'secret' => $dto->secret,
                // token/tags 为加密后的密文，result/count 原样返回
                'result' => $data['result'],
                'token' => $data['token'],
                'tags' => $data['tags'],
                'count' => $data['count'],
            ],
        ]);
    }
}
