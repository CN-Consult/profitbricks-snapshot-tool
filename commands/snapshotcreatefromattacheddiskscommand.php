<?php
/**
 * @file
 * @version 0.1
 * @copyright 2017 CN-Consult GmbH
 * @author Jens Stahl <jens.stahl@cn-consult.eu>
 *
 * License: Please check the LICENSE file for more information.
 */

namespace PBST\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use PBST\ProfitBricksApi\ProfitBricksApi;
use PBST\ProfitBricksApi\DataCenter;
use PBST\ProfitBricksApi\VirtualMachine;
use PBST\ProfitBricksApi\VirtualDisk;
use Symfony\Component\Console\Style\SymfonyStyle;
use Exception;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class SnapshotCreateFromAttachedDisksCommand
 *
 * With this command you can easy take snapshots form a specific server at once.
 */
class SnapshotCreateFromAttachedDisksCommand extends CommandBase
{
    private $preDescription = "";

    protected function configure()
    {
        parent::configure();
        $this
            ->setName("snapshot:createFromAttachedDisks")
            ->setDescription("Creates a snapshot from all disks which are attached to a server.")
            ->addArgument("server", InputArgument::IS_ARRAY, "ID or name of servers, which should be snapshot.")
            ->addOption("description","d", InputOption::VALUE_REQUIRED, "This description will be added in front of the snapshot description.", null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption("description")!== null) $this->preDescription = $input->getOption("description");
        if ($input->getOption("quiet")=== false && count($input->getArgument("server"))===0)
        {// In quiet mode don't ask for continuing this action.
            $output->writeln("This will create a snapshot from every disk, which is attached to a virtual machine!");
            $helper = $this->getHelper("question");
            $question = new ConfirmationQuestion("Continue with this action?", false);
            if (!$helper->ask($input, $output, $question)) return;
        }

        $profitBricksApi = new ProfitBricksApi();
        $profitBricksApi->setUserName($this->config["api"]["user"]);
        $profitBricksApi->setPassword($this->config["api"]["password"]);

        $dataCenters = $profitBricksApi->dataCenters();
        $io =  new SymfonyStyle($input, $output);
        $io->title("Snapshot Creation Tool");
        $tableRows = array();
        foreach ($dataCenters as $dataCenter)
        {
            foreach ($profitBricksApi->virtualMachinesFor($dataCenter) as $virtualMachine)
            {// loop over all VMS and create snapshot from via arguments picked VMs
                if (count($input->getArgument("server"))>0)
                {
                    foreach ($input->getArgument("server") as $server)
                    {
                        if ($virtualMachine->name == $server || $virtualMachine->id == $server)
                        {
                            foreach ($profitBricksApi->virtualDisks($virtualMachine, $dataCenter->id) as $virtualDisk)
                            {
                                $tableRows[] = $this->snapshotResult($profitBricksApi, $dataCenter, $virtualMachine, $virtualDisk);
                            }
                        }
                    }
                }
                else
                {// no VMs picked! Create snapshot from every VM
                    foreach ($profitBricksApi->virtualDisks($virtualMachine, $dataCenter->id) as $virtualDisk)
                    {
                        $tableRows[] = $this->snapshotResult($profitBricksApi, $dataCenter, $virtualMachine, $virtualDisk);
                    }
                }
            }
        }
        $io->table(array("DataCenter", "VirtualHost", "VirtualDisk", "State"), $tableRows);
    }

    /**
     * Initiates snapshot via ProfitBricksApi and returns its result.
     *
     * @param ProfitBricksApi $_profitBricksApi
     * @param DataCenter      $_dataCenter
     * @param VirtualMachine  $_virtualMachine
     * @param VirtualDisk     $_virtualDisk
     * @return array          Result with disk and its membership
     */
    private function snapshotResult(ProfitBricksApi $_profitBricksApi, DataCenter $_dataCenter, VirtualMachine $_virtualMachine, VirtualDisk $_virtualDisk)
    {
        if ($_profitBricksApi->makeSnapshot($_dataCenter, $_virtualMachine, $_virtualDisk, $this->preDescription))
            return array($_dataCenter->name, $_virtualMachine->name, $_virtualDisk->name, "snapshot initiated!");
        else return array($_dataCenter->name, $_virtualMachine->name, $_virtualDisk->name, "snapshot failed!");
    }
}