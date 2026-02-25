<?php

namespace PHPTools\LaravelDatabaseTask\Concerns;

use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use PHPTools\LaravelDatabaseTask\Enums\InputType;

trait InteractsWithInput
{
    use Conditionable;
    use EvaluatesClosures;

    protected InputType $type = InputType::QUERY;

    protected mixed $value;

    protected bool | \Closure $isExcluded = false;

    protected array | \Closure $options = [];

    protected bool | \Closure $hasTime = false;

    protected bool | \Closure $canBeMultiple = false;

    protected bool | \Closure $canBeFile = false;

    protected bool | \Closure $canBeExcluded = false;

    protected bool | \Closure $required = false;

    protected array | \Closure $requiredWithoutAll = [];

    protected array | \Closure $extraInputAttributes = [];

    // --- Basic Methods ---

    public function getType(): InputType
    {
        return $this->type;
    }

    public function getName(): string
    {
        return Str::of(static::class)->afterLast('\\')->snake()->toString();
    }

    public function getValue(): mixed
    {
        return $this->evaluate($this->value);
    }

    public function value(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function asQuery(): self
    {
        $this->type = InputType::QUERY;

        return $this;
    }

    public function asNumber(): self
    {
        $this->type = InputType::NUMBER;

        return $this;
    }

    public function asSelect(array | \Closure $options): self
    {
        $this->type = InputType::SELECT;
        $this->options = $options;

        return $this;
    }

    public function asDatetime(bool | \Closure $hasTime = false): self
    {
        $this->type = InputType::DATETIME;
        $this->hasTime = $hasTime;

        return $this;
    }

    public function asBoolean(): self
    {
        $this->type = InputType::BOOLEAN;

        return $this;
    }

    public function asFile(): self
    {
        $this->type = InputType::FILE;

        return $this;
    }

    public function excluded(bool | \Closure $condition = true): static
    {
        $this->isExcluded = $condition;

        return $this;
    }

    public function isExcluded(): bool
    {
        return $this->type->canBeExcluded() && (bool) $this->evaluate($this->isExcluded);
    }

    public function isFile(): bool
    {
        return $this->type->canBeFile() && $this->getValue() instanceof \SplFileObject;
    }

    // --- Form schema configuration Methods ---

    public function getLabel(): string
    {
        $inputName = Str::of(static::class)->afterLast('\\')->snake();

        return __("database-task::tasks.inputs.{$inputName}.label");
    }

    public function getOptions(): array
    {
        return (array) $this->evaluate($this->options);
    }

    public function hasTime(): bool
    {
        return (bool) $this->evaluate($this->hasTime);
    }

    public function multiple(bool | \Closure $condition = true): self
    {
        $this->canBeMultiple = $condition;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->type->canBeMultiple() && (bool) $this->evaluate($this->canBeMultiple);
    }

    public function canBeExcluded(bool | \Closure $condition = true): self
    {
        $this->canBeExcluded = $condition;

        return $this;
    }

    public function isCanBeExcluded(): bool
    {
        return $this->type->canBeExcluded() && (bool) $this->evaluate($this->canBeExcluded);
    }

    public function canBeFile(bool | \Closure $condition = true): self
    {
        $this->canBeFile = $condition;

        return $this;
    }

    public function isCanBeFile(): bool
    {
        if ($this->type === InputType::FILE) {
            return true;
        }

        return $this->type->canBeFile() && (bool) $this->evaluate($this->canBeFile);
    }

    public function extraInputAttributes(array | \Closure $extraInputAttributes): self
    {
        $this->extraInputAttributes = $extraInputAttributes;

        return $this;
    }

    public function getExtraInputAttributes(): array
    {
        return (array) $this->evaluate($this->extraInputAttributes);
    }

    // --- Validation Methods ---

    public function required(bool | \Closure $condition = true): self
    {
        $this->required = $condition;

        return $this;
    }

    public function isRequired(): bool
    {
        return (bool) $this->evaluate($this->required);
    }

    public function requiredWithoutAll(array | \Closure $fields): self
    {
        $this->requiredWithoutAll = $fields;

        return $this;
    }

    public function requiredWithoutAllFields(): array
    {
        return (array) $this->evaluate($this->requiredWithoutAll);
    }

    // --- Localization Methods ---

    public function getPlaceholder(): string
    {
        return $this->getType()->getPlaceholder($this->getLabel(), $this->isMultiple());
    }

    public function getHelperText(): string
    {
        return $this->getType()->getHelperText($this->getLabel(), $this->isMultiple());
    }
}
