<?php namespace Zephyrus\Utilities\Listing;

class FilterView
{
    private string $columnName;
    private string $name;
    private string $icon;
    private string $type;
    private array $allowedColumnFilters;

    public static function string(string $columnName): self
    {
        $self = new self($columnName, ['contains', 'begins', 'ends', 'equals']);
        $self->setType("string");
        return $self;
    }

    public static function money(string $columnName): self
    {
        $self = new self($columnName, ['equals', 'less', 'less_equals', 'greater', 'greater_equals', 'between']);
        $self->setType("money");
        return $self;
    }

    public static function select(string $columnName): self
    {
        return new self($columnName, ['selector']);
    }

    public static function date(string $columnName): self
    {
        $self = new self($columnName, ['equals', 'less_equals', 'greater_equals', 'between']);
        $self->setType("date");
        return $self;
    }

    public static function decimal(string $columnName): self
    {
        $self = new self($columnName, ['equals', 'less', 'less_equals', 'greater', 'greater_equals', 'between']);
        $self->setType("decimal");
        return $self;
    }

    public static function integer(string $columnName): self
    {
        $self = new self($columnName, ['equals', 'less', 'less_equals', 'greater', 'greater_equals', 'between']);
        $self->setType("integer");
        return $self;
    }

    public function __construct(string $columnName, array $allowedColumnFilters)
    {
        $this->columnName = $columnName;
        $this->allowedColumnFilters = $allowedColumnFilters;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @param string $icon
     */
    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }

    /**
     * @return string
     */
    public function getColumn(): string
    {
        return $this->columnName;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->allowedColumnFilters;
    }
}
