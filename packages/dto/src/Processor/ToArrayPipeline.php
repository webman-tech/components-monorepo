<?php

namespace WebmanTech\DTO\Processor;

use InvalidArgumentException;
use WebmanTech\DTO\Exceptions\DTOException;
use WebmanTech\DTO\Exceptions\DTOProcessorException;
use WebmanTech\DTO\Helper\ConfigHelper;

/**
 * toArray 处理管线
 */
final class ToArrayPipeline
{
    /** @var list<class-string<ToArrayProcessorInterface>> */
    private const DEFAULT_PROCESSORS = [
        SelectProperties::class,
        SerializeProperties::class,
        FilterNullValues::class,
        ConvertEmptyArraysToObjects::class,
        EncryptFields::class,
        ExtractSingleKey::class,
    ];

    /** @var list<ToArrayProcessorInterface> */
    private array $processors;

    /**
     * @param list<ToArrayProcessorInterface> $processors
     */
    public function __construct(array $processors = [])
    {
        $this->processors = $processors;
    }

    /**
     * 根据配置创建一条独立管线
     */
    public static function create(): self
    {
        $processorClasses = ConfigHelper::get('dto.to_array_processors', self::DEFAULT_PROCESSORS);
        if (!is_array($processorClasses)) {
            throw new InvalidArgumentException('dto.to_array_processors must be an array');
        }

        $processors = [];
        foreach ($processorClasses as $processorClass) {
            if (!is_string($processorClass) || !is_a($processorClass, ToArrayProcessorInterface::class, true)) {
                throw new InvalidArgumentException('dto.to_array_processors must contain ToArrayProcessorInterface class names');
            }
            $processors[] = new $processorClass();
        }

        return new self($processors);
    }

    public function process(ToArrayContext $context): void
    {
        foreach ($this->processors as $processor) {
            try {
                $processor($context);
            } catch (DTOException $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw new DTOProcessorException($processor::class, previous: $e);
            }
        }
    }

    public function add(ToArrayProcessorInterface $processor): self
    {
        $this->processors[] = $processor;

        return $this;
    }

    /**
     * @param class-string<ToArrayProcessorInterface> $before
     */
    public function insert(ToArrayProcessorInterface $processor, string $before): self
    {
        foreach ($this->processors as $index => $current) {
            if ($current instanceof $before) {
                array_splice($this->processors, $index, 0, [$processor]);

                return $this;
            }
        }

        throw new InvalidArgumentException("Processor {$before} not found");
    }

    /**
     * @param class-string<ToArrayProcessorInterface> $processorClass
     */
    public function remove(string $processorClass): self
    {
        $this->processors = array_values(array_filter(
            $this->processors,
            static fn(ToArrayProcessorInterface $processor): bool => !$processor instanceof $processorClass,
        ));

        return $this;
    }

    /**
     * @return list<ToArrayProcessorInterface>
     */
    public function processors(): array
    {
        return $this->processors;
    }
}
