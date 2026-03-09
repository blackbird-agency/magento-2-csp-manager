<?php
declare(strict_types=1);

namespace Blackbird\CSPManager\Console\Command;

use Blackbird\CSPManager\Model\Config\CspConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SetCspCommand
 * Command to set (overwrite) CSP rule in env.php
 */
class SetCspCommand extends Command
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
        $this->setName('csp:rule:set')
            ->setDescription('Set (overwrite) a CSP rule in env.php')
            ->addArgument(
                self::ARGUMENT_DIRECTIVE,
                InputArgument::REQUIRED,
                'The CSP directive (e.g. img-src)'
            )
            ->addArgument(
                self::ARGUMENT_VALUE,
                InputArgument::REQUIRED,
                'The CSP value to set (e.g. "monsite.com cdn.monsite.dam")'
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directive = (string)$input->getArgument(self::ARGUMENT_DIRECTIVE);
        $value = (string)$input->getArgument(self::ARGUMENT_VALUE);

        if (!$this->cspConfigManager->isValidDirective($directive)) {
            $output->writeln(sprintf(
                '<error>Invalid CSP directive: "%s". Allowed directives are: %s</error>',
                $directive,
                implode(', ', $this->cspConfigManager->getAllowedDirectives())
            ));
            return Command::FAILURE;
        }

        try {
            $this->cspConfigManager->setRuleValue($directive, $value);
            $output->writeln(sprintf('<info>CSP rule for "%s" has been set successfully.</info>', $directive));
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Error setting CSP rule: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
