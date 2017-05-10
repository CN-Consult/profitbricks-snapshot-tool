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
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use PBST\ProfitBricksApi\ProfitBricksApi;
use Exception;

/**
 * Class SnapshotDeleteCommand
 *
 * This command deletes snapshot(s) at ProfitBricks by snapshot ID or filtered by date (deletes older than).
 */
class SnapshotDeleteCommand extends Command
{
    private $config;
    private $deleteBeforeTimestamp;

    public function __construct()
    {
        parent::__construct();
        $this->deleteBeforeTimestamp = new \DateTime();
    }

    protected function configure()
    {
        $this
            ->setName("snapshot:delete")
            ->setDescription("Deletes snapshots by ID or by date from ProfitBricks!")
            ->addArgument("snapshotId", InputArgument::IS_ARRAY, "snapshot IDs of the snapshots")
            ->addOption("before","b", InputOption::VALUE_REQUIRED, "lists all snapshot before this date time!", null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (is_readable('config.ini'))
        {
            $this->config = parse_ini_file('config.ini', true);
            if (!isset($this->config['api']['user']) || !isset($this->config['api']['password'])) throw new Exception("No user or no password configured to connect ProfitBricks!");
        }
        else throw new Exception("Error during reading config.ini!");

        $profitBricksApi = new ProfitBricksApi();
        $profitBricksApi->setUserName($this->config["api"]["user"]);
        $profitBricksApi->setPassword($this->config["api"]["password"]);

        if ($input->getOption("before")!==null)
        {
            if (strtotime($input->getOption("before"))===false)
                throw new Exception("Before must be a valid date time!");
            else
            {
                $this->deleteBeforeTimestamp = new \DateTime($input->getOption("before"));
                $questionOutput = "This will delete every snapshot before ".$this->deleteBeforeTimestamp->format("d.m.Y H:i")."!";
            }
        }
        else $questionOutput = "This will delete every snapshot!";

        $snapshots = $profitBricksApi->snapshots();

        $io =  new SymfonyStyle($input, $output);
        $tableRows = array ();
        $header = array ("ID", "Name", "Description", "Date       Time", "Size", "State");
        $sumSize = 0;
        $sumCount = 0;

        if (count($input->getArgument("snapshotId"))>0)
        {
            $io->title("Snapshots Deletion");
            foreach ($input->getArgument("snapshotId") as $id)
            {
                if (isset($snapshots[$id]) && $snapshots[$id]->createdDate<$this->deleteBeforeTimestamp)
                {
                    if ($profitBricksApi->deleteSnapshot($snapshots[$id]->id))
                    {
                        $tableRows[] = array ($id, $snapshots[$id]->name, $snapshots[$id]->description, $snapshots[$id]->createdDate->format("d.m.Y H:i"), $snapshots[$id]->size." GB", "deleted!");
                        $sumSize += (int)$snapshots[$id]->size;
                        $sumCount += 1;
                    }
                    else $tableRows[] = array ($id, $snapshots[$id]->name, $snapshots[$id]->description, $snapshots[$id]->createdDate->format("d.m.Y H:i"), $snapshots[$id]->size." GB", "deletion failed!");
                }
            }
        }
        else
        {
            if ($input->getOption("quiet")=== false)
            {// In quiet mode don't ask for continuing this action.
                $output->writeln($questionOutput);
                $helper = $this->getHelper("question");
                $question = new ConfirmationQuestion("Continue with this action?", false);
                if (!$helper->ask($input, $output, $question)) return;
            }
            $io->title("Snapshots Deletion");
            foreach ($snapshots as $snapshot)
            {
                if ($snapshot->createdDate<$this->deleteBeforeTimestamp)
                {
                    if ($profitBricksApi->deleteSnapshot($snapshot->id))
                    {
                        $tableRows[] = array ($snapshot->id, $snapshot->name, $snapshot->description, $snapshot->createdDate->format("d.m.Y H:i"), $snapshot->size." GB", "deleted!");
                        $sumSize += (int)$snapshot->size;
                        $sumCount += 1;
                    }
                    else $tableRows[] = array ($snapshot->id, $snapshot->name, $snapshot->description, $snapshot->createdDate->format("d.m.Y H:i"), $snapshot->size." GB", "deletion failed!");
                }
            }
        }
        $sumSize = $this->formatSize($sumSize);
        $tableRows[] = new TableSeparator();
        $tableRows[] = array("Counter:", $sumCount, "", "Total:", $sumSize." TB", "deleted");

        //$io->table($header, $tableRows);
        $table = new Table($output);
        $table->setHeaders($header);
        $table->addRows($tableRows);
        $tableStyle = new TableStyle();
        $colStyle = new TableStyle();
        $tableStyle->setVerticalBorderChar(" ");
        $tableStyle->setCrossingChar(" ");
        $table->setStyle($tableStyle);
        $colStyle->setPadType(STR_PAD_LEFT);
        $table->setColumnStyle(4, $colStyle);
        $table->render();
    }
}