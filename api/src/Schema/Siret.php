<?php

namespace App\Schema;

use MKCG\Model\GenericSchema;
use MKCG\Model\GenericEntity;
use MKCG\Model\FieldInterface;

class Siret extends GenericSchema
{
    protected $name = 'siret';
    protected $driverName = 'redisearch';
    protected $entityClass = GenericEntity::class;
    protected $primaryKeys = ['id'];

    protected $types = [
        'default' => [
            'id',
            'siren',
            'siret',
            'store_name',
            'society_name',
            'street_number',
            'street_type',
            'street_name',
            'zipcode',
            'city',
            'address',
            'names',
            'all'
        ],
    ];

    protected function initFields()
    {
        return $this
            ->setFieldDefinition('id', FieldInterface::TYPE_ENUM, true)
            ->setFieldDefinition('siren', FieldInterface::TYPE_ENUM)
            ->setFieldDefinition('siret', FieldInterface::TYPE_ENUM)
            ->setFieldDefinition('store_name', FieldInterface::TYPE_ENUM, true, true, true)
            ->setFieldDefinition('society_name', FieldInterface::TYPE_ENUM, true, true, true)
            ->setFieldDefinition('street_number', FieldInterface::TYPE_FLOAT, true, true, true)
            ->setFieldDefinition('street_type', FieldInterface::TYPE_ENUM, true, true, true)
            ->setFieldDefinition('street_name', FieldInterface::TYPE_ENUM, true, true, true)
            ->setFieldDefinition('zipcode', FieldInterface::TYPE_FLOAT, true, true, true)
            ->setFieldDefinition('city', FieldInterface::TYPE_ENUM, true, true, true)
            ->setFieldDefinition('address', FieldInterface::TYPE_STRING, true)
            ->setFieldDefinition('names', FieldInterface::TYPE_STRING, true)
            ->setFieldDefinition('all', FieldInterface::TYPE_STRING, true)
        ;
    }
}
