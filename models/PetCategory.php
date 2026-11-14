<?php
declare(strict_types=1);

final class PetCategory extends BaseCategory
{
    protected const TABLE = 'pet_categories';
    protected const ITEM_TABLE = 'pets';
    protected const PREFIX = IdGenerator::PET_CATEGORY;
}
