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
        $this->indexes = [
            'imdb' => (new Index($this->client))->setIndexName('imdb_titles'),
            'siret' => (new Index($this->client))->setIndexName('siret')
        ];
    }

    public function createImdbTitleIndex()
    {
        $this->indexes['imdb']
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
            $this->indexes['imdb']->drop();
        } catch (\Exception $e) {

        }

        $this->indexes['imdb']->create();
    }

    public function createSiretIndex()
    {
        $this->indexes['siret']
            ->setStopWords([])
            ->addTagField('id', true)
            ->addTagField('siren')
            ->addTagField('siret')
            ->addTagField('store_name', true)
            ->addTagField('society_name', true)
            ->addNumericField('street_number')
            ->addTagField('street_type')
            ->addTagField('street_name', true)
            ->addNumericField('zipcode', true)
            ->addTagField('city', true)
            ->addTextField('address')
            ->addTextField('names')
            ->addTextField('all')
        ;

        try {
            $this->indexes['siret']->drop();
        } catch (\Exception $e) {

        }

        $this->indexes['siret']->create();
    }

    public function addDocument($index, array $document)
    {
        $instance = $this->makeDocInstance($index, $document);
        $this->indexes[$index]->add($instance);
    }

    private function makeDocInstance($index, array $document)
    {
        $id = $document['id'];
        unset($document['id']);

        $fields = get_object_vars($this->indexes[$index]);

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

