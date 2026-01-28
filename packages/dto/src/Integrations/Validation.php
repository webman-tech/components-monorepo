<?php

namespace WebmanTech\DTO\Integrations;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use WebmanTech\DTO\Exceptions\DTOValidateException;
use WebmanTech\DTO\Helper\ConfigHelper;

/**
 * @internal
 */
final class Validation
{
    private static ?ValidatorInterface $factory = null;

    public static function create(): ValidatorInterface
    {
        if (self::$factory === null) {
            $factory = ConfigHelper::get('dto.validator_factory');
            if ($factory === null) {
                $factory = match (true) {
                    function_exists('validator') => LaravelFunctionValidatior::class,
                    default => throw new InvalidArgumentException('not found validator class'),
                };
            }
            if ($factory instanceof \Closure) {
                $factory = $factory();
            }
            if ($factory instanceof ValidatorInterface) {
                self::$factory = $factory;
            } elseif (class_exists($factory) && is_a($factory, ValidatorInterface::class, true)) {
                self::$factory = new $factory();
            } else {
                throw new InvalidArgumentException('validator_factory error');
            }
        }

        return self::$factory;
    }
}

/**
 * @internal
 */
final class LaravelFunctionValidatior implements ValidatorInterface, ValidatorStopOnFirstFailureInterface
{
    use ValidatorStopOnFirstFailureAware;

    public function validate(array $data = [], array $rules = [], array $messages = [], array $customAttributes = []): array
    {
        /** @var ValidatorContract $validator */
        $validator = validator($data, $rules, $messages, $customAttributes);

        if ($this->stopOnFirstFailure === true && $validator instanceof Validator) {
            $validator->stopOnFirstFailure();
        }

        try {
            return $validator->validate();
        } catch (ValidationException $exception) {
            throw new DTOValidateException($validator->errors()->getMessages(), $exception);
        }
    }
}
