<?php
/**
 * @file
 * @version 0.1
 * @copyright 2018 CN-Consult GmbH
 * @author Jens Stahl <jens.stahl@cn-consult.eu>
 *
 * License: Please check the LICENSE file for more information.
 */

namespace PBST\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;
use PBST\ProfitBricksApi\ProfitBricksApi;
use PBST\ProfitBricksApi\Snapshot;
use Exception;

/**
 * Class ServerStartCommand
 *
 * This command starts a profitbricks virtual server.
 */
class ServerStopCommand extends CommandBase
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setName("server:stop")
            ->setDescription("Stops one or more IONOS server(s)!")
            ->addArgument("serverName", InputArgument::IS_ARRAY, "Names or IDs of server, which should be started.");
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);
        if (!$input->getArgument("serverName")) throw new Exception("Argument server name(s) missed!");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($input->getArgument("serverName") as $server)
        {
            $stoppingServers[] = array("server" => $server, "byID" => false, "byName" => false);
        }
        $dataCenters = $this->profitBricksApi->dataCenters();
        $tableHeaders = array ("DataCenter", "ServerID", "VirtualHost","found by");
        $tableColumns = array ();
        foreach ($dataCenters as $dataCenter)
        {
            foreach ($this->profitBricksApi->virtualMachinesFor($dataCenter) as $virtualMachine)
            {
                foreach ($stoppingServers as $index => $server)
                {
                    if ($server["server"] == $virtualMachine->id)
                    {// found Server by ID stopping it
                        $this->profitBricksApi->stopServer($dataCenter->id, $virtualMachine->id);
                        $stoppingServers[$index]['byID']=true;
                        $tableColumns[] = array ($dataCenter->name, $virtualMachine->id, $virtualMachine->name, "ID");
                    }
                    if ($server["server"] == $virtualMachine->name)
                    {// found Server by ID stopping it
                        $this->profitBricksApi->stopServer($dataCenter->id, $virtualMachine->id);
                        $stoppingServers[$index]['byName']=true;
                        $tableColumns[] = array ($dataCenter->name, $virtualMachine->id, $virtualMachine->name, "Name");
                    }
                }
            }
        }
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE)
        {
            $io =  new SymfonyStyle($input, $output);
            $io->title("Virtual Machines");
            $io->table($tableHeaders, $tableColumns);
        }
        //Check if all arguments have been matched
        $matchedAllArguments = true;
        $tableHeaders = array ("Argument", "found by ID", "found by name");
        $tableColumns = array ();
        foreach ($stoppingServers as $server)
        {
            if ($server["byID"] == false and $server["byName"] == false) $matchedAllArguments = false;
            $tableColumns[] = array ($server["server"], ($server["byID"] ? "true" : "false"), ($server["byName"] ? "true" : "false"));
        }
        if (!$matchedAllArguments or $output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE)
        {
            $io =  new SymfonyStyle($input, $output);
            $io->title("Did not found all servers!");
            $io->table($tableHeaders, $tableColumns);
        }
    }
}