<?php

namespace Aubry\EnvVarsDebug\Bundle\Console;

use Aubry\EnvVarsDebug\Domain\EnvVar;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerDebugCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @todo Handle all transformers (https://symfony.com/blog/new-in-symfony-3-4-advanced-environment-variables)
 * @todo Output values as well (optional)
 * @todo Check if a native Sf parser can be used
 */
class DebugEnvVarsCommand extends ContainerDebugCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName("aubry:debug:env-vars");
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled()
    {
        return $this->getContainer()->getParameter("kernel.environment") === "dev"
            && parent::isEnabled();
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $containerBuilder = $this->getContainerBuilder();

        /** @var EnvVar[] $envVars */
        $envVars = [];
        $biggestNameLength = 0;

        // Browsing all parameters
        foreach ($containerBuilder->getParameterBag()->all() as $key => $value) {

            if (!is_string($value)) {
                continue;
            }

            if (preg_match('/%(env\(((\w+:)*)([^:]+)\))%/', $value, $matches) === 1) {
                // 1 : env(...)
                $envCallString = $matches[1];
                // 2 : "" / "int:" / "bool:" / ...
                $transformersString = $matches[2];
                // 3 : var name
                $varName = $matches[4];

                $defaultParamName = sprintf("env(%s)", $varName);
                $hasDefault = $containerBuilder->getParameterBag()->has($defaultParamName);

                // Parsing transformers
                $transformers = $this->extractTransformers($transformersString);

                // Multiple transformers not handled !
                if (count($transformers) > 1 && $output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                    $output->writeln(sprintf(
                        'Ignoring string <comment>"%s"</comment> : multiple transformers not handled yet.',
                            $envCallString)
                    );
                }

                // Build environment variables
                $type = current($transformers) ?: EnvVar::TYPE_STRING;
                try {
                    $envVars[$varName] = new EnvVar(
                        $varName,
                        !$hasDefault,
                        $type
                    );
                } catch (DomainException $e) {
                    if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                        $output->writeln(sprintf('Ignoring string <comment>"%s"</comment> : type "%s" not handled.',
                            $envCallString, $type));
                    }
                }

                // Update the biggest name length
                $biggestNameLength = max($biggestNameLength, strlen($varName));
            }
        }

        // Display the output
        $this->displayOutput($output, $envVars, $biggestNameLength);
    }

    /**
     * @param string $transformersString
     *
     * @return array|string[]
     */
    private function extractTransformers(string $transformersString): array
    {
        return explode(":", trim($transformersString, ":"));
    }

    /**
     * @param OutputInterface $output
     * @param array|EnvVar[] $envVars
     * @param int $biggestNameLength
     */
    protected function displayOutput(OutputInterface $output, array $envVars, int $biggestNameLength): void
    {
        if ($output->getVerbosity() < OutputInterface::VERBOSITY_NORMAL) {
            return;
        }

        // Mandatory variables first
        usort($envVars, function (EnvVar $envVar1, EnvVar $envVar2) {
            return $envVar2->isMandatory() <=> $envVar1->isMandatory();
        });

        // Headers
        $col1Header = "Variable";
        $col1Length = max($biggestNameLength, $col1Header);

        $col2Header = "Mandatory";
        $col2Length = max(strlen($col2Header), strlen("true"), strlen("false"));

        $col3Header = "Type";
        $col3Length = strlen($col3Header);

        $output->writeln("");
        // Header row
        $this->writeTableRow(
            $output,
            [
                $this->buildCell($col1Header, $col1Length),
                $this->buildCell($col2Header, $col2Length),
                $this->buildCell($col3Header, $col3Length),
            ]
        );
        $this->writeTableRow(
            $output,
            [
                $this->buildCell("", $col1Length, "-", "-"),
                $this->buildCell("", $col2Length, "-", "-"),
                $this->buildCell("", $col3Length, "-", "-"),
            ]
        );

        // Variables
        foreach ($envVars as $envVar) {
            $this->writeTableRow(
                $output,
                [
                    $this->buildCell($envVar->getName(), $col1Length),
                    $this->buildCell($envVar->isMandatory() ? "true" : "false", $col2Length),
                    $this->buildCell($envVar->getType(), 0),
                ]
            );
        }
    }

    /**
     * @param OutputInterface $output
     * @param array $cells
     */
    private function writeTableRow(OutputInterface $output, array $cells): void
    {
        // Pattern
        $pattern = implode("  ", array_fill(0, count($cells), "%s"));

        $sprintfArgs = $cells;
        array_unshift($sprintfArgs, $pattern);

        $output->writeln(
            call_user_func_array("sprintf", $sprintfArgs)
        );
    }

    /**
     * @param string $value
     * @param int $length
     * @param string $fillChar
     *
     * @param string $marginChar
     * @return string
     */
    protected function buildCell(string $value, int $length, string $fillChar = " ", string $marginChar = " "): string
    {
        return $marginChar . str_pad($value, $length, $fillChar, STR_PAD_RIGHT) . $marginChar;
    }
}
