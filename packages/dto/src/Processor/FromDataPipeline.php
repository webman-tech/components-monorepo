<?php

namespace WebmanTech\DTO\Processor;

use InvalidArgumentException;
use WebmanTech\DTO\Exceptions\DTOException;
use WebmanTech\DTO\Exceptions\DTOProcessorException;
use WebmanTech\DTO\Helper\ConfigHelper;

/**
 * fromData 处理管线
 */
final class FromDataPipeline
{
    /** @var list<class-string<ProcessorInterface>> */
    private const DEFAULT_PROCESSORS = [
        DecryptFields::class,
        SanitizeData::class,
        ValidateData::class,
    ];

    /** @var list<ProcessorInterface> */
    private array $processors;

    /**
     * @param list<ProcessorInterface> $processors
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
        $processorClasses = ConfigHelper::get('dto.from_data_processors', self::DEFAULT_PROCESSORS);
        if (!is_array($processorClasses)) {
            throw new InvalidArgumentException('dto.from_data_processors must be an array');
        }

        $processors = [];
        foreach ($processorClasses as $processorClass) {
            if (!is_string($processorClass) || !is_a($processorClass, ProcessorInterface::class, true)) {
                throw new InvalidArgumentException('dto.from_data_processors must contain ProcessorInterface class names');
            }
            $processors[] = new $processorClass();
        }

        return new self($processors);
    }

    public function process(FromDataContext $context): void
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

    public function add(ProcessorInterface $processor): self
    {
        $this->processors[] = $processor;

        return $this;
    }

    /**
     * @param class-string<ProcessorInterface> $before
     */
    public function insert(ProcessorInterface $processor, string $before): self
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
     * @param class-string<ProcessorInterface> $processorClass
     */
    public function remove(string $processorClass): self
    {
        $this->processors = array_values(array_filter(
            $this->processors,
            static fn(ProcessorInterface $processor): bool => !$processor instanceof $processorClass,
        ));

        return $this;
    }

    /**
     * @return list<ProcessorInterface>
     */
    public function processors(): array
    {
        return $this->processors;
    }
}
