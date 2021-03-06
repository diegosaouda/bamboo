<?php

/*
 * This file is part of the Elcodi package.
 *
 * Copyright (c) 2014-2015 Elcodi.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 * @author Aldo Chiecchia <zimage@tiscali.it>
 * @author Elcodi Team <tech@elcodi.com>
 */

namespace Elcodi\Common\BambooBundle\Behat;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\ArrayInput;

use Elcodi\Common\BambooBundle\Behat\abstracts\AbstractElcodiContext;

/**
 * Class DoctrineContext
 */
class DoctrineContext extends AbstractElcodiContext
{
    /**
     * @var boolean
     *
     * Debug mode
     */
    protected $debug = false;

    /**
     * @BeforeScenario
     */
    public function prepare(BeforeScenarioScope $scope)
    {
        gc_collect_cycles();

        $this
            ->executeCommand('assets:install')
            ->executeCommand('assetic:dump')
            ->checkDoctrineConnection()
            ->executeCommand('doctrine:database:drop', [
                '--force' => true,
            ])
            ->checkDoctrineConnection()
            ->executeCommand('doctrine:database:create')
            ->executeCommand('doctrine:schema:create')
            ->executeCommand('doctrine:fixtures:load', [
                '--fixtures' => $this
                        ->kernel
                        ->getRootDir() . '/../src/Elcodi/Fixtures',
            ])
            ->executeCommand('elcodi:templates:load')
            ->executeCommand('elcodi:templates:enable', [
                'template' => 'StoreTemplateBundle',
            ])
            ->executeCommand('elcodi:plugins:load');
    }

    /**
     * @AfterScenario
     */
    public function cleanDB(AfterScenarioScope $scope)
    {
        $this
            ->getContainer()
            ->get('doctrine')
            ->getConnection()
            ->close();

        $this->application->run(new ArrayInput([
            'command'          => 'doctrine:database:drop',
            '--env'            => 'test',
            '--no-interaction' => true,
            '--force'          => true,
            '--quiet'          => true,
        ]));
    }

    /**
     */
    public function prepareSuite(BeforeSuiteScope $scope)
    {
        $this
            ->executeCommand('assets:install')
            ->executeCommand('assetic:dump');
    }

    /**
     * Execute a command
     *
     * @param string $command    Command
     * @param array  $parameters Parameters
     *
     * @return $this Self object
     */
    protected function executeCommand(
        $command,
        array $parameters = []
    ) {
        $definition = array_merge(
            [
                'command'          => $command,
                '--no-interaction' => true,
                '--quiet'          => true,
            ], $parameters
        );

        if (!$this->debug) {
            $definition['--quiet'] = true;
        }

        $this
            ->application
            ->run(new ArrayInput($definition));

        return $this;
    }

    /**
     * Check the doctrine connection
     *
     * @return $this Self object
     */
    protected function checkDoctrineConnection()
    {
        /**
         * @var Connection $doctrineConnection
         */
        $doctrineConnection = $this
            ->getContainer()
            ->get('doctrine')
            ->getConnection();

        if ($doctrineConnection->isConnected()) {
            $doctrineConnection->close();
        }

        return $this;
    }
}
