<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace Tests\Unit\Core\Form\IdentifiableObject\CommandBuilder\Accessor;

use PHPUnit\Framework\TestCase;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\CommandBuilder\Accessor\CommandAccessor;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\CommandBuilder\Accessor\CommandAccessorConfig;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\CommandBuilder\Accessor\CommandField;

class CommandAccessorTest extends TestCase
{
    private const MULTI_SHOP_PREFIX = 'multi_shop_';
    private const SHOP_ID = 1;

    /**
     * @dataProvider getSingleCommandParameters
     *
     * @param CommandAccessorConfig $config
     * @param array $data
     * @param array $expectedCommands
     */
    public function testBuildSingleCommand(
        CommandAccessorConfig $config,
        array $data,
        array $expectedCommands
    ): void {
        $accessor = new CommandAccessor($config);
        $command = new CommandAccessorTestCommand(ShopConstraint::shop(self::SHOP_ID));
        $commands = $accessor->prepareCommands($data, $command);
        $this->assertEquals($expectedCommands, $commands);
    }

    public function getSingleCommandParameters(): iterable
    {
        $config = new CommandAccessorConfig(self::MULTI_SHOP_PREFIX);
        $config
            ->addField('[url]', 'setUrl', CommandField::TYPE_STRING)
            ->addField('[name]', 'setName', CommandField::TYPE_STRING)
            ->addField('[command][isValid]', 'setIsValid', CommandField::TYPE_BOOL)
            ->addField('[_number]', 'setCount', CommandField::TYPE_INT)
            ->addField('[parent][children]', 'setChildren', CommandField::TYPE_ARRAY)
        ;
        $children = [
            'bob',
            'steve',
        ];

        $command = new CommandAccessorTestCommand(ShopConstraint::shop(self::SHOP_ID));
        $command
            ->setUrl('http://localhost')
            ->setName('toto')
            ->setIsValid(true)
            ->setCount(42)
            ->setChildren($children)
        ;

        yield [
            $config,
            [
                'url' => 'http://localhost',
                'name' => 'toto',
                'command' => [
                    'isValid' => true,
                ],
                '_number' => 42,
                'parent' => [
                    'children' => $children,
                ],
            ],
            [$command],
        ];

        // prefix is not mandatory especially when dealing with single shop command
        $config = new CommandAccessorConfig();
        $config
            ->addField('[url]', 'setUrl', CommandField::TYPE_STRING)
            ->addField('[name]', 'setName', CommandField::TYPE_STRING)
            ->addField('[command][isValid]', 'setIsValid', CommandField::TYPE_BOOL)
            ->addField('[_number]', 'setCount', CommandField::TYPE_INT)
            ->addField('[parent][children]', 'setChildren', CommandField::TYPE_ARRAY)
        ;

        yield [
            $config,
            [
                'url' => 'http://localhost',
                'name' => 'toto',
                'command' => [
                    'isValid' => true,
                ],
                '_number' => 42,
                'parent' => [
                    'children' => $children,
                ],
            ],
            [$command],
        ];

        $command = new CommandAccessorTestCommand(ShopConstraint::shop(self::SHOP_ID));
        $command
            ->setName('toto')
            ->setIsValid(false)
        ;

        yield [
            $config,
            [
                'name' => 'toto',
                'command' => [
                    'isValid' => false,
                ],
                'unknown' => 45,
            ],
            [$command],
        ];

        $config = new CommandAccessorConfig(self::MULTI_SHOP_PREFIX);
        $config
            ->addMultiShopField('[url]', 'setUrl', CommandField::TYPE_STRING)
            ->addField('[name]', 'setName', CommandField::TYPE_STRING)
            ->addMultiShopField('[command][isValid]', 'setIsValid', CommandField::TYPE_BOOL)
            ->addField('[_number]', 'setCount', CommandField::TYPE_INT)
            ->addField('[parent][children]', 'setChildren', CommandField::TYPE_ARRAY)
        ;

        $command = new CommandAccessorTestCommand(ShopConstraint::shop(self::SHOP_ID));
        $command
            ->setUrl('http://localhost')
            ->setName('toto')
            ->setIsValid(true)
            ->setCount(42)
        ;

        // Same test but now some fields are multishop, since no multishop command is provided it shouldn't change the final result
        yield [
            $config,
            [
                'url' => 'http://localhost',
                'name' => 'toto',
                'command' => [
                    'isValid' => true,
                ],
                '_number' => 42,
                'unknown' => 45,
            ],
            [$command],
        ];
    }

    /**
     * @dataProvider getMultiShopCommandsParameters
     *
     * @param CommandAccessorConfig $config
     * @param array $data
     * @param array $expectedCommands
     */
    public function testBuildMultiShopCommands(
        CommandAccessorConfig $config,
        array $data,
        array $expectedCommands
    ): void {
        $accessor = new CommandAccessor($config);
        $command = new CommandAccessorTestCommand(ShopConstraint::shop(self::SHOP_ID));
        $allShopsCommand = new CommandAccessorTestCommand(ShopConstraint::allShops());
        $commands = $accessor->prepareCommands($data, $command, $allShopsCommand);
        $this->assertEquals($expectedCommands, $commands);
    }

    public function getMultiShopCommandsParameters(): iterable
    {
        $config = new CommandAccessorConfig(self::MULTI_SHOP_PREFIX);
        $config
            ->addField('[url]', 'setUrl', CommandField::TYPE_STRING)
            ->addMultiShopField('[name]', 'setName', CommandField::TYPE_STRING)
            ->addMultiShopField('[command][isValid]', 'setIsValid', CommandField::TYPE_BOOL)
            ->addMultiShopField('[_number]', 'setCount', CommandField::TYPE_INT)
            ->addMultiShopField('[parent][children]', 'setChildren', CommandField::TYPE_ARRAY)
        ;
        $children = [
            'bob',
            'steve',
        ];

        $command = new CommandAccessorTestCommand(ShopConstraint::shop(self::SHOP_ID));
        $command
            ->setUrl('http://localhost')
            ->setName('toto')
            ->setIsValid(true)
            ->setCount(42)
            ->setChildren($children)
        ;

        yield [
            $config,
            [
                'url' => 'http://localhost',
                'name' => 'toto',
                'command' => [
                    'isValid' => true,
                ],
                '_number' => 42,
                'parent' => [
                    'children' => $children,
                ],
            ],
            [$command],
        ];

        $command = new CommandAccessorTestCommand(ShopConstraint::shop(self::SHOP_ID));
        $command
            ->setUrl('http://localhost')
            ->setName('toto')
            ->setIsValid(false)
        ;

        $allShopsCommand = new CommandAccessorTestCommand(ShopConstraint::allShops());
        $allShopsCommand
            ->setCount(42)
            ->setChildren($children)
        ;

        yield [
            $config,
            [
                'url' => 'http://localhost',
                'name' => 'toto',
                'command' => [
                    'isValid' => false,
                ],
                '_number' => 42,
                self::MULTI_SHOP_PREFIX . '_number' => true,
                'parent' => [
                    'children' => $children,
                    self::MULTI_SHOP_PREFIX . 'children' => true,
                ],
            ],
            [$command, $allShopsCommand],
        ];

        yield [
            $config,
            [
                '_number' => 42,
                self::MULTI_SHOP_PREFIX . '_number' => true,
                'parent' => [
                    'children' => $children,
                    self::MULTI_SHOP_PREFIX . 'children' => true,
                ],
            ],
            [$allShopsCommand],
        ];

        // More advanced use, multishop field is present but not always true, and url is not a multishop field
        $command = new CommandAccessorTestCommand(ShopConstraint::shop(self::SHOP_ID));
        $command
            ->setUrl('http://localhost')
            ->setName('toto')
            ->setCount(42)
            ->setIsValid(false)
        ;

        $allShopsCommand = new CommandAccessorTestCommand(ShopConstraint::allShops());
        $allShopsCommand
            ->setChildren($children)
        ;

        yield [
            $config,
            [
                'url' => 'http://localhost',
                self::MULTI_SHOP_PREFIX . 'url' => true,
                'name' => 'toto',
                'command' => [
                    'isValid' => false,
                ],
                '_number' => 42,
                self::MULTI_SHOP_PREFIX . '_number' => false,
                'parent' => [
                    'children' => $children,
                    self::MULTI_SHOP_PREFIX . 'children' => true,
                ],
            ],
            [$command, $allShopsCommand],
        ];

        // Same test but now url is a multishop field
        $config = new CommandAccessorConfig(self::MULTI_SHOP_PREFIX);
        $config
            ->addMultiShopField('[url]', 'setUrl', CommandField::TYPE_STRING)
            ->addMultiShopField('[name]', 'setName', CommandField::TYPE_STRING)
            ->addMultiShopField('[command][isValid]', 'setIsValid', CommandField::TYPE_BOOL)
            ->addMultiShopField('[_number]', 'setCount', CommandField::TYPE_INT)
            ->addMultiShopField('[parent][children]', 'setChildren', CommandField::TYPE_ARRAY)
        ;

        $command = new CommandAccessorTestCommand(ShopConstraint::shop(self::SHOP_ID));
        $command
            ->setName('toto')
            ->setCount(42)
            ->setIsValid(false)
        ;

        $allShopsCommand = new CommandAccessorTestCommand(ShopConstraint::allShops());
        $allShopsCommand
            ->setUrl('http://localhost')
            ->setChildren($children)
        ;

        yield [
            $config,
            [
                'url' => 'http://localhost',
                self::MULTI_SHOP_PREFIX . 'url' => true,
                'name' => 'toto',
                'command' => [
                    'isValid' => false,
                ],
                '_number' => 42,
                self::MULTI_SHOP_PREFIX . '_number' => false,
                'parent' => [
                    'children' => $children,
                    self::MULTI_SHOP_PREFIX . 'children' => true,
                ],
            ],
            [$command, $allShopsCommand],
        ];
    }
}

class CommandAccessorTestCommand
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $url;

    /**
     * @var bool
     */
    private $isValid;

    /**
     * @var int
     */
    private $count;

    /**
     * @var array
     */
    private $children;

    /**
     * @var ShopConstraint
     */
    private $shopConstraint;

    /**
     * @param ShopConstraint $shopConstraint
     */
    public function __construct(ShopConstraint $shopConstraint)
    {
        $this->shopConstraint = $shopConstraint;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return static
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * @param bool $isValid
     *
     * @return static
     */
    public function setIsValid(bool $isValid): self
    {
        $this->isValid = $isValid;

        return $this;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @param int $count
     *
     * @return static
     */
    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    /**
     * @return array
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param array $children
     *
     * @return static
     */
    public function setChildren(array $children): self
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return ShopConstraint
     */
    public function getShopConstraint(): ShopConstraint
    {
        return $this->shopConstraint;
    }
}
