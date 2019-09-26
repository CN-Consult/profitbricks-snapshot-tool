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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Exception;

/**
 * Base-Class for all IONOS-related server commands.
 *
 * We inherit from our own base class instead direct use of symfony command because there are some lines of code
 * which has to be executed always. All server commmands should use this class for a base class.
 *  - Implement method configure() with command-specific additions as needed
 *      - provide a valid name, check/parse of new input arguments, arbitrary setup code before running the command
 *  - Implement method execute() with what gets done if the command is triggered
 */
class ServerCommandBase extends CommandBase
{
    const on = 1;
    const off = 2;

    /**
     * @param integer $_serverState
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     */
    protected function setServerPowerState($_serverState, InputInterface $input, OutputInterface $output)
    {
        foreach ($input->getArgument("serverName") as $server)
        {
            $servers[] = array("server" => $server, "byID" => false, "byName" => false);
        }
        $dataCenters = $this->profitBricksApi->dataCenters();
        $tableColumns = array ();
        foreach ($dataCenters as $dataCenter)
        {
            foreach ($this->profitBricksApi->virtualMachinesFor($dataCenter) as $virtualMachine)
            {
                foreach ($servers as $index => $server)
                {
                    if ($server["server"] == $virtualMachine->id)
                    {// found Server by ID
                        switch ($_serverState)
                        {
                            case self::on:  $this->profitBricksApi->startServer($dataCenter->id, $virtualMachine->id);
                                            break;
                            case self::off: $this->profitBricksApi->stopServer($dataCenter->id, $virtualMachine->id);
                                            break;
                        }
                        $servers[$index]['byID']=true;
                        $tableColumns[] = array ($dataCenter->name, $virtualMachine->id, $virtualMachine->name, "ID");
                    }
                    if ($server["server"] == $virtualMachine->name)
                    {// found Server by name
                        switch ($_serverState)
                        {
                            case self::on:  $this->profitBricksApi->startServer($dataCenter->id, $virtualMachine->id);
                                            break;
                            case self::off: $this->profitBricksApi->stopServer($dataCenter->id, $virtualMachine->id);
                                            break;
                        }
                        $servers[$index]['byName']=true;
                        $tableColumns[] = array ($dataCenter->name, $virtualMachine->id, $virtualMachine->name, "Name");
                    }
                }
            }
        }
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE)
        {
            $tableHeaders = array ("DataCenter", "ServerID", "VirtualHost","found by");
            $io =  new SymfonyStyle($input, $output);
            $io->title("Virtual Machines");
            $io->table($tableHeaders, $tableColumns);
        }
        //Check if all arguments have been matched
        $matchedAllArguments = true;
        $tableColumns = array ();
        foreach ($servers as $server)
        {
            if ($server["byID"] == false and $server["byName"] == false) $matchedAllArguments = false;
            $tableColumns[] = array ($server["server"], ($server["byID"] ? "true" : "false"), ($server["byName"] ? "true" : "false"));
        }
        if (!$matchedAllArguments) $output->writeln("<error>Did not found all arguments!</>");
        if (!$matchedAllArguments or $output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE)
        {
            $tableHeaders = array ("Argument", "found by ID", "found by name");
            $io =  new SymfonyStyle($input, $output);
            $io->title("Matching table");
            $io->table($tableHeaders, $tableColumns);
        }
    }
}