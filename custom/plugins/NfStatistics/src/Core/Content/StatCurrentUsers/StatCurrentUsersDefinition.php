<?php declare(strict_types=1);

namespace Nf\Statistics\Core\Content\StatCurrentUsers;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Checkout\Customer\CustomerDefinition;

class StatCurrentUsersDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'nf_stat_current_users';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return StatCurrentUsersEntity::class;
    }

    public function getCollectionClass(): string
    {
        return StatCurrentUsersCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('remote_addr', 'remoteAddr'))->addFlags(new Required()),
            (new FkField('user_id', 'userId', CustomerDefinition::class)),
            (new StringField('token', 'token')),
            (new StringField('device_type', 'deviceType')),
            ]);
    }
}