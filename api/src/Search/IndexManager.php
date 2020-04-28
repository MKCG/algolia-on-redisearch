<?php

namespace App\Search;

use Ehann\RedisRaw\PredisAdapter;
use Ehann\RediSearch\RediSearchRedisClient;
use Ehann\RediSearch\Index;
use Ehann\RediSearch\Document\AbstractDocumentFactory;
use Ehann\RediSearch\Fields\NumericField;

class IndexManager
{
    private $client;

    public function __construct(PredisAdapter $client)
    {
        $this->client = $client;
        $this->index = (new Index($this->client))
            ->setIndexName('imdb_titles');
    }

    public function createImdbTitleIndex()
    {
        $this->index
            ->setStopWords([])
            ->addTagField('id', true)
            ->addTagField('titleType', true)
            ->addTextField('primaryTitle')
            ->addTextField('originalTitle')
            ->addTagField('isAdult')
            ->addNumericField('startYear', true)
            ->addNumericField('endYear', true)
            ->addNumericField('runtimeMinutes', true)
            ->addTagField('genres', true, false, ',');

        try {
            $this->index->drop();
        } catch (\Exception $e) {

        }

        $this->index->create();
    }

    public function addDocument(array $document)
    {
        $instance = $this->makeDocInstance($document);
        $this->index->add($instance);
    }

    private function makeDocInstance(array $document)
    {
        $id = $document['id'];
        unset($document['id']);

        $fields = get_object_vars($this->index);

        foreach ($document as $field => $value) {
            if (!isset($fields[$field])) {
                unset($document[$field]);
            } else if ($fields[$field] instanceof NumericField && !is_numeric($value)) {
                unset($document[$field]);
            }
        }

        return AbstractDocumentFactory::makeFromArray($document, $fields, $id);
    }
}

