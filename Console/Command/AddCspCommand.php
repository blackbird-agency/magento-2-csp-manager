<?php
declare(strict_types=1);

namespace Blackbird\CSPManager\Console\Command;

use Blackbird\CSPManager\Model\Config\CspConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AddCspCommand
 * Command to add CSP value in env.php
 */
class AddCspCommand extends Command
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
        $this->setName('csp:rule:add')
            ->setDescription('Add a CSP value to a directive in env.php')
            ->addArgument(
                self::ARGUMENT_DIRECTIVE,
                InputArgument::REQUIRED,
                'The CSP directive (e.g. img-src)'
            )
            ->addArgument(
                self::ARGUMENT_VALUE,
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'The CSP value(s) to add (e.g. "monsite.com" "anothersite.com")'
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
            foreach ($values as $value) {
                $this->cspConfigManager->addRuleValue($directive, (string)$value);
            }
            $output->writeln(sprintf('<info>CSP value(s) for "%s" has been added successfully.</info>', $directive));
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Error adding CSP value: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
