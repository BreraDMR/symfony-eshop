<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ProductRepository;
use App\Search\ProductIndexer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;

#[AsCommand(
    name: 'app:search:reindex',
    description: 'Rebuilds the Elasticsearch product index from the database.',
)]
final class ReindexProductsCommand extends Command
{
    public function __construct(
        private readonly ProductIndexer $indexer,
        private readonly ProductRepository $products,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->indexer->resetIndex();

        $count = 0;
        foreach ($this->products->findAll() as $product) {
            $this->indexer->index($product);
            ++$count;
        }

        $this->indexer->refresh();

        $io->success(sprintf('Indexed %d product(s).', $count));

        return Command::SUCCESS;
    }
}
