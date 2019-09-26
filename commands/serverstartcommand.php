<?php
/**
 * @file
 * @version 0.1
 * @copyright 2019 CN-Consult GmbH
 * @author Jens Stahl <jens.stahl@cn-consult.eu>
 *
 * License: Please check the LICENSE file for more information.
 */

namespace PBST\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ServerStartCommand
 *
 * This command starts an IONOS virtual server.
 */
class ServerStartCommand extends ServerCommandBase
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setName("server:start")
            ->setDescription("Starts one or more profitbricks server!")
            ->addArgument("serverName", InputArgument::IS_ARRAY | InputArgument::REQUIRED, "Names or IDs of server, which should be started.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setServerPowerState(ServerCommandBase::on, $input, $output);
    }
}