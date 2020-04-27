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

        $indexed = 0;
        $rejected = 0;

        foreach ($this->reader() as $i => $document) {
            try {
                $this->manager->addDocument($document);
                $indexed++;
            } catch (\Ehann\RediSearch\Exceptions\RediSearchException $e) {
                $rejected++;
            }

            if ($indexed % 1000 === 0) {
                $io->text(sprintf("Imported : %d documents", $indexed));
            }
        }

        $io->success(sprintf('%d titles indexed - %d titles rejected', $indexed, $rejected));

        return 0;
    }

    private function reader()
    {
        $file = __DIR__ . '/../../fixtures/title.basics.tsv';
        $handler = fopen($file, 'r');
        $header = fgetcsv($handler, 0, "\t");
        $i = 0;

        while (($line = fgetcsv($handler, 0, "\t")) !== false) {
            $document = array_combine($header, $line);

            yield $document;
            $i++;

            if ($i === $this->limit) {
                break;
            }
        }

        fclose($handler);
    }
}
