<?php

namespace App\Schema;

use MKCG\Model\GenericSchema;
use MKCG\Model\GenericEntity;
use MKCG\Model\FieldInterface;

class IMDB extends GenericSchema
{
    protected $name = 'imdb_titles';
    protected $driverName = 'redisearch';
    protected $entityClass = GenericEntity::class;
    protected $primaryKeys = ['id'];

    protected $types = [
        'default' => [
            'id',
            'titleType',
            'primaryTitle',
            'originalTitle',
            'isAdult',
            'startYear',
            'endYear',
            'runtimeMinutes',
            'genres'
        ],
    ];

    protected function initFields()
    {
        return $this
            ->setFieldDefinition('id', FieldInterface::TYPE_ENUM, true)
            ->setFieldDefinition('titleType', FieldInterface::TYPE_ENUM, true, true, true)
            ->setFieldDefinition('primaryTitle', FieldInterface::TYPE_STRING, true)
            ->setFieldDefinition('originalTitle', FieldInterface::TYPE_STRING, true)
            ->setFieldDefinition('isAdult', FieldInterface::TYPE_ENUM, true)
            ->setFieldDefinition('startYear', FieldInterface::TYPE_FLOAT, true, true, true)
            ->setFieldDefinition('endYear', FieldInterface::TYPE_FLOAT, true, true, true)
            ->setFieldDefinition('runtimeMinutes', FieldInterface::TYPE_FLOAT, true, true, true)
            ->setFieldDefinition('genres', FieldInterface::TYPE_ENUM, true, false, true)
        ;
    }
}
