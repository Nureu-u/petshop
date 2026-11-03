<?php
declare(strict_types=1);

final class ProductCategory extends BaseCategory
{
    protected const TABLE = 'product_categories';
    protected const ITEM_TABLE = 'products';
    protected const PREFIX = IdGenerator::PRODUCT_CATEGORY;
}
