<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use App\Search\IndexManager;

class SearchIndexerCommand extends Command
{
    protected static $defaultName = 'search:indexer';

    private $manager;
    private $limit = 100000;

    public function __construct(IndexManager $manager)
    {
        parent::__construct();
        $this->manager = $manager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Populate IMDB titles index')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->manager->createImdbTitleIndex();
        $this->manager->createSiretIndex();

        $io->text("Indexing SIRET establishments");
        $this->index($io, $this->siretReader(), 'siret');

        $io->text("Indexing IMDB titles");
        $this->index($io, $this->imdbReader(), 'imdb');

        return 0;
    }

    private function index($io, $reader, $index)
    {
        $indexed = 0;
        $rejected = 0;

        foreach ($reader as $i => $document) {
            try {
                $this->manager->addDocument($index, $document);
                $indexed++;
            } catch (\Ehann\RediSearch\Exceptions\RediSearchException $e) {
                $rejected++;
            }

            if ($indexed % 1000 === 0) {
                $io->text(sprintf("Imported : %d documents", $indexed));
            }
        }

        $io->success(sprintf('%d documents indexed - %d documents rejected', $indexed, $rejected));
    }


    private function siretReader()
    {
        $file = __DIR__ . '/../../fixtures/stores.csv';
        $handler = fopen($file, 'r');
        $header = fgetcsv($handler, 0, ",");
        $i = 0;


        $fieldMap = [
            'siren'                         => 'siren',
            'siret'                         => 'siret',
            'denominationUniteLegale'       => 'store_name',
            'enseigne1Etablissement'        => 'society_name',
            'numeroVoieEtablissement'       => 'street_number',
            'typeVoieEtablissement'         => 'street_type',
            'libelleVoieEtablissement'      => 'street_name',
            'codePostalEtablissement'       => 'zipcode',
            'libelleCommuneEtablissement'   => 'city',
        ];

        while (($line = fgetcsv($handler, 0, ",")) !== false) {
            $row = array_combine($header, $line);
            $document = [];

            foreach ($fieldMap as $from => $to) {
                $document[$to] = $row[$from];
            }

            $document['id'] = $document['siret'];
            $document['address'] = implode(' ', [
                $document['street_number'],
                $document['street_type'],
                $document['street_name'],
                $document['zipcode'],
                $document['city']
            ]);

            $document['names'] = implode(' ', [
                $document['store_name'],
                $document['society_name']
            ]);

            $document['all'] = implode(' ', [
                $document['address'],
                $document['names']
            ]);

            yield $document;
            $i++;

            if ($i === $this->limit) {
                break;
            }
        }

        fclose($handler);
    }

    private function imdbReader()
    {
        $file = __DIR__ . '/../../fixtures/title.basics.tsv';
        $handler = fopen($file, 'r');
        $header = fgetcsv($handler, 0, "\t");
        $i = 0;

        while (($line = fgetcsv($handler, 0, "\t")) !== false) {
            $document = array_combine($header, $line);
            $document['id'] = $document['tconst'];
            unset($document['tconst']);
            $document['genres'] = strtoupper($document['genres']);

            yield $document;
            $i++;

            if ($i === $this->limit) {
                break;
            }
        }

        fclose($handler);
    }
}
