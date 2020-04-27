<?php

namespace App\Search;

use Ehann\RedisRaw\PredisAdapter;
use Ehann\RediSearch\RediSearchRedisClient;
use Ehann\RediSearch\Index;

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
            ->addTagField('tconst', true)
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
        foreach (['startYear', 'endYear', 'runtimeMinutes'] as $numericField) {
            if (!is_numeric($document[$numericField])) {
                unset($document[$numericField]);
            }
        }

        $this->index->add($document);
    }
}

