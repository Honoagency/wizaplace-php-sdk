<?php
/**
 * @copyright Copyright (c) Wizacha
 * @license Proprietary
 */
declare(strict_types = 1);

namespace Wizaplace\SDK\Catalog;

final class ProductAttribute
{
    /** @var string */
    private $name;
    /** @var null|string|array */
    private $value;
    /** @var ProductAttribute[] */
    private $children;
    /** @var string[] */
    private $imageUrls;

    /**
     * @internal
     */
    public function __construct(array $data)
    {
        $this->name = (string) $data['name'];
        $this->value = $data['value'];
        $this->imageUrls = $data['imageUrls'] ?? [];
        $this->children = array_map(static function (array $childrenData) : self {
            return new self($childrenData);
        }, $data['children']);
    }

    /**
     * @return ProductAttribute[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return null|string|array
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string[]
     */
    public function getImageUrls(): array
    {
        return $this->imageUrls;
    }
}
