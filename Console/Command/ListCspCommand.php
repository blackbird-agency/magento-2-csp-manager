<?php
declare(strict_types=1);

namespace Blackbird\CSPManager\Console\Command;

use Blackbird\CSPManager\Model\Config\CspConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * Class ListCspCommand
 * Command to list CSP rules in env.php
 */
class ListCspCommand extends Command
{
    /**
     * @param CspConfigManager $cspConfigManager
     * @param string|null $name
     */
    public function __construct(
        private readonly CspConfigManager $cspConfigManager,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('csp:rule:list')
            ->setDescription('List all CSP rules defined in env.php');

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rules = $this->cspConfigManager->getRules();

        if (empty($rules)) {
            $output->writeln('<info>No CSP rules found in env.php.</info>');
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['Directive', 'Value']);

        foreach ($rules as $directive => $value) {
            $table->addRow([$directive, $value]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
