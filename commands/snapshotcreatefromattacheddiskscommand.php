<?php
/**
 * @file
 * @version 0.1
 * @copyright 2017 CN-Consult GmbH
 * @author Jens Stahl <jens.stahl@cn-consult.eu>
 *
 * License: Please check the LICENSE file for more information.
 */

namespace PBST\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use ProfitBricksApi\ProfitBricksApi;
use ProfitBricksApi\DataCenter;
use ProfitBricksApi\VirtualMachine;
use ProfitBricksApi\VirtualDisk;
use Symfony\Component\Console\Style\SymfonyStyle;
use Exception;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class SnapshotCreateFromAttachedDisksCommand
 *
 * With this command you can easy take snapshots form a specific server at once.
 */
class SnapshotCreateFromAttachedDisksCommand extends Command
{
    private $config;
    private $preDescription = "";

    protected function configure()
    {
        $this
            ->setName("snapshot:createFromAttachedDisks")
            ->setDescription("Creates a snapshot from all disks which are attached to a server.")
            ->addArgument("server", InputArgument::IS_ARRAY, "ID or name of servers, which should be snapshot.")
            ->addOption("description","d", InputOption::VALUE_REQUIRED, "This description will be added in front of the snapshot description.", null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (is_readable('config.ini'))
        {
            $this->config = parse_ini_file('config.ini', true);
            if (!isset($this->config['api']['user']) || !isset($this->config['api']['password'])) throw new Exception("No user or no password configured to connect ProfitBricks!");
        }
        else  throw new Exception("Error during reading config.ini!");

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

        /** @var DataCenter[] $dataCenters */
        $dataCenters = $profitBricksApi->dataCenters();
        $io =  new SymfonyStyle($input, $output);
        $io->title("Snapshot Creation Tool");
        $tableRows = array();
        foreach ($dataCenters as $dataCenter)
        {   /** @var VirtualMachine $virtualMachine */
            foreach ($profitBricksApi->virtualMachines($dataCenter) as $virtualMachine)
            {   /** @var VirtualDisk $virtualDisk */
                // check if servers have been selected manually via arguments
                if (count($input->getArgument("server"))>0)
                {
                    foreach ($input->getArgument("server") as $server)
                    {
                        if ($virtualMachine->name == $server || $virtualMachine->id == $server)
                        {
                            foreach ($profitBricksApi->virtualDisks($virtualMachine, $dataCenter->id) as $virtualDisk)
                            {
                                $tableRows[] = array($dataCenter->name, $virtualMachine->name, $virtualDisk->name, "snapshot initiated!");
                                $profitBricksApi->makeSnapshot($dataCenter, $virtualMachine, $virtualDisk, $this->preDescription);
                            }
                        }
                    }
                }
                else
                {
                    foreach ($profitBricksApi->virtualDisks($virtualMachine, $dataCenter->id) as $virtualDisk)
                    {
                        if ($profitBricksApi->makeSnapshot($dataCenter, $virtualMachine, $virtualDisk, $this->preDescription))
                            $tableRows[] = array($dataCenter->name, $virtualMachine->name, $virtualDisk->name, "snapshot initiated!");
                        else $tableRows[] = array($dataCenter->name, $virtualMachine->name, $virtualDisk->name, "snapshot failed!");
                    }
                }
            }
        }
        $io->table(array("DataCenter", "VirtualHost", "VirtualDisk", "State"), $tableRows);
    }
}