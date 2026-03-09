<?php
declare(strict_types=1);

namespace Blackbird\CSPManager\Console\Command;

use Blackbird\CSPManager\Model\Config\CspConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UnsetCspCommand
 * Command to remove a full CSP directive from env.php
 */
class UnsetCspCommand extends Command
{
    private const ARGUMENT_DIRECTIVE = 'directive';

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
        $this->setName('csp:rule:unset')
            ->setDescription('Remove a full CSP directive from env.php')
            ->addArgument(
                self::ARGUMENT_DIRECTIVE,
                InputArgument::REQUIRED,
                'The CSP directive to remove (e.g. img-src)'
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directive = (string)$input->getArgument(self::ARGUMENT_DIRECTIVE);

        if (!$this->cspConfigManager->isValidDirective($directive)) {
            $output->writeln(sprintf(
                '<error>Invalid CSP directive: "%s". Allowed directives are: %s</error>',
                $directive,
                implode(', ', $this->cspConfigManager->getAllowedDirectives())
            ));
            return Command::FAILURE;
        }

        try {
            $this->cspConfigManager->removeRuleValue($directive, null);
            $output->writeln(sprintf('<info>CSP directive "%s" has been removed successfully.</info>', $directive));
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Error removing CSP directive: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
