<?php
declare(strict_types=1);

namespace Blackbird\CSPManager\Console\Command;

use Blackbird\CSPManager\Model\Config\CspConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RemoveCspCommand
 * Command to remove CSP rule from env.php
 */
class RemoveCspCommand extends Command
{
    private const ARGUMENT_DIRECTIVE = 'directive';
    private const ARGUMENT_VALUE = 'value';

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
        $this->setName('csp:rule:remove')
            ->setDescription('Remove a CSP rule or value from env.php')
            ->addArgument(
                self::ARGUMENT_DIRECTIVE,
                InputArgument::REQUIRED,
                'The CSP directive to remove (e.g. img-src)'
            )
            ->addArgument(
                self::ARGUMENT_VALUE,
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'The specific CSP value(s) to remove. If not provided, the whole directive will be removed.'
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directive = (string)$input->getArgument(self::ARGUMENT_DIRECTIVE);
        $values = (array)$input->getArgument(self::ARGUMENT_VALUE);

        if (!$this->cspConfigManager->isValidDirective($directive)) {
            $output->writeln(sprintf(
                '<error>Invalid CSP directive: "%s". Allowed directives are: %s</error>',
                $directive,
                implode(', ', $this->cspConfigManager->getAllowedDirectives())
            ));
            return Command::FAILURE;
        }

        try {
            if (empty($values)) {
                $this->cspConfigManager->removeRuleValue($directive, null);
                $output->writeln(sprintf('<info>CSP directive "%s" has been removed successfully.</info>', $directive));
            } else {
                foreach ($values as $value) {
                    $this->cspConfigManager->removeRuleValue($directive, (string)$value);
                }
                $output->writeln(sprintf('<info>CSP value(s) for directive "%s" has been removed successfully.</info>', $directive));
            }
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Error removing CSP: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
