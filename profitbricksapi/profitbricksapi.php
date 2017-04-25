<?php
/**
 * @file
 * @version 0.1
 * @copyright 2017 CN-Consult GmbH
 * @author Jens Stahl <jens.stahl@cn-consult.eu>
 *
 * License: Please check the LICENSE file for more information.
 */

namespace ProfitBricksApi;

use Exception;

/**
 * Class ProfitBricksApi connects to the official ProfitBricksApi and returns easy usable objects.
 */
class ProfitBricksApi
{
    private $user = "";
    private $password = "";
    const profitBricksSnapshotApi = "https://api.profitbricks.com/cloudapi/v3/snapshots?depth=1";
    const profitBricksDataCenterApi = "https://api.profitbricks.com/cloudapi/v3/datacenters?depth=2";
    const profitBricksServerApi = "https://api.profitbricks.com/cloudapi/v3/datacenters/[data-center-id]/servers?depth=2";
    const profitBricksDiskApi = "https://api.profitbricks.com/cloudapi/v3/datacenters/[data-center-id]/servers/[server-id]/volumes?depth=1";
    const profitBricksDeleteSnapshot = "https://api.profitbricks.com/cloudapi/v3/snapshots/[snapshot-id]";
    const profitBricksCreateSnapshot = "https://api.profitbricks.com/cloudapi/v3/datacenters/[data-center-id]/volumes/[volume-id]/create-snapshot";

    public function __construct()
    {
    }

    /**
     * Reads all information about snapshots from ProfitBricks into usable objects.
     *
     * @return Snapshot[]|bool
     */
    public function snapshots()
    {
        $snapshots = false;
        $profitBricksSnapshots = $this->readFromProfitBricks(self::profitBricksSnapshotApi);
        if ($profitBricksSnapshots!==false)
        {
            $snapshots = array();
            foreach ($profitBricksSnapshots as $snapshot)
            {
                $snapshots[$snapshot->id] = new Snapshot($snapshot);
            }
        }
        return $snapshots;
    }

    /**
     * Reads all information about DataCenters from ProfitBricks into usable objects.
     *
     * @return DataCenter[]|bool
     */
    public function dataCenters()
    {
        $dataCenters = false;
        $profitBricksDataCenters = $this->readFromProfitBricks(self::profitBricksDataCenterApi);
        if ($profitBricksDataCenters!==false)
        {
            $dataCenters = array();
            foreach ($profitBricksDataCenters as $dataCenter)
            {
                $dataCenters[$dataCenter->id] = new DataCenter($dataCenter);
            }
        }
        return $dataCenters;
    }

     /**
     * Reads all information about virtual machines/servers from ProfitBricks into usable objects.
     *
     * @param DataCenter $_dataCenter determines the data center for which the virtual machines should be searched.
     * @return VirtualMachine[]|bool
     */
    public function virtualMachines(DataCenter $_dataCenter)
    {
        $virtualMachines = false;
        $api = str_replace("[data-center-id]", $_dataCenter->id, self::profitBricksServerApi);
        foreach ($this->readFromProfitBricks($api) as $virtualMachine)
        {
            $virtualMachines[$virtualMachine->id] = new VirtualMachine($virtualMachine);
        }
        return $virtualMachines;
    }

    /**
     * Reads all information about virtual disks (called volumes) regarding to a virtual machine from ProfitBricks into usable objects.
     *
     * @param string $_dataCenterId determines the data center, in which the virtual machine is.
     * @param VirtualMachine $_virtualMachine determines the virtual machine, which are the disks attached to.
     * @return VirtualDisk[]|bool
     */
    public function virtualDisks(VirtualMachine $_virtualMachine, $_dataCenterId)
    {
        $virtualDisks = false;
        $api = str_replace("[data-center-id]", $_dataCenterId, self::profitBricksDiskApi);
        $api = str_replace("[server-id]", $_virtualMachine->id, $api);
        $latestFullSnapshot = null;
        foreach ($this->readFromProfitBricks($api) as $virtualDisk)
        {
            $virtualDisks[$virtualDisk->id] = new VirtualDisk($virtualDisk);
        }
        return $virtualDisks;
    }


    /**
     * This function imports strings and JSON from ProfitBricks, which it cuts down to an usable array of objects.
     *
     * @param string $_profitBricksApi predefined const values
     * @return mixed Array of objects
     * @throws Exception when something with the connection process fails.
     */
    private function readFromProfitBricks($_profitBricksApi)
    {
        $authorisation = base64_encode($this->user.":".$this->password);
        $curl = curl_init($_profitBricksApi);
        curl_setopt($curl, CURLOPT_HTTPGET, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_SSLVERSION, 6);  // forcing TLS 1.2 can lead to issues, but we should avoid sending a password unencrypted.
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Basic $authorisation"));
        if (curl_exec($curl))
        {
            $response = curl_multi_getcontent($curl);

            if (strpos($response, "HTTP/1.1 200 OK")!==false)
            {// remove leading HTTP response lines until JSON begins (with leading '{')
                while (!preg_match('/^\{.*/', $response))
                {
                    $response = preg_replace('/^.*\n/', '', $response);
                }
                $contentObject = json_decode($response);
                if ($contentObject===false) throw new Exception ("JSON decode failed - debug needed");
            }
            else
            {
                $token = strtok($response, "\n");
                throw new Exception((strpos($response, "HTTP/1.1 401 Unauthorized")!==false) ? $token."\nCredentials for ProfitBricks are invalid!" : $token);
            }
        }
        else
        {
            throw  new Exception("Curl Error ".curl_errno($curl)." occurred!\n".curl_error($curl));
        }
        curl_close($curl);
        return $contentObject->items;
    }

    /**
     * Initiates a snapshot at ProfitBricks.
     *
     * @param DataCenter $_dataCenter
     * @param VirtualMachine $_virtualMachine
     * @param VirtualDisk $_virtualDisk
     * @param string $_preDescription
     * @return Snapshot
     * @throws Exception when something goes wrong with the curl connection
     */
    public function makeSnapshot(DataCenter $_dataCenter, VirtualMachine $_virtualMachine, VirtualDisk $_virtualDisk, $_preDescription = "")
    {// calculate KW
        $now = new \DateTime();
        $kw = $now->format('Y-W');

        $snapShotName = $_virtualMachine->name . "_" . $_virtualDisk->name . "_KW$kw";
        // @todo "Auto-Script: "
        $snapShotDescription = $_preDescription . $_dataCenter->name . "-->" . $_virtualMachine->name . "-->" . $_virtualDisk->name;

        $postData = "name=".$snapShotName."&description=".$snapShotDescription;
        $authorisation = base64_encode($this->user.":".$this->password);
        $api = str_replace("[data-center-id]", $_dataCenter->id, self::profitBricksCreateSnapshot);
        $api = str_replace("[volume-id]", $_virtualDisk->id, $api);
        $curl = curl_init($api);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_SSLVERSION, 6);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Basic $authorisation","Content-Type: application/x-www-form-urlencoded"));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        if (curl_exec($curl))
        {
            $response = curl_multi_getcontent($curl);
            if (strpos($response, "HTTP/1.1 202 Accepted")!==false)
            {
                while (!preg_match('/^\{.*/', $response))
                {
                    $response = preg_replace('/^.*\n/', '', $response);
                }
                $contentObject = json_decode($response);
                if ($contentObject===false) throw new Exception ("JSON decode failed - debug needed");
            }
            else
            {
                $token = strtok($response, "\n");
                throw new Exception((strpos($response, "HTTP/1.1 401 Unauthorized")!==false) ? $token."\nCredentials for ProfitBricks are invalid!" : $token);
            }
        }
        else
        {
            throw new Exception("Curl Error ".curl_errno($curl)." occurred!\n".curl_error($curl));
        }
        curl_close($curl);
        return new Snapshot($contentObject);
    }

    /**
     * Deletes a snapshot on the ProfitBricks servers with a curl request.
     *
     * @param string $_snapShotId ID of the snapshot, which should be deleted
     * @return bool whether the snapshot is deleted successfully or not
     * @throws \Exception
     */
    public function deleteSnapshot($_snapShotId)
    {
        $api = str_replace("[snapshot-id]", $_snapShotId, self::profitBricksDeleteSnapshot);
        $authorisation = base64_encode($this->user.":".$this->password);
        $curl = curl_init($api);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_SSLVERSION, 6);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Basic $authorisation"));
        if (curl_exec($curl))
        {
            $response = curl_multi_getcontent($curl);
            if (strpos($response, "HTTP/1.1 202 Accepted")===false)
            {
                $token = strtok($response, "\n");
                throw new Exception((strpos($response, "HTTP/1.1 401 Unauthorized")!==false) ? $token."\nCredentials for ProfitBricks are invalid!" : $token);
            }
        }
        else
        {
            throw new Exception("Curl Error ".curl_errno($curl)." occurred!\n".curl_error($curl));
        }
        curl_close($curl);
        return true;
    }

    /**
     * @param string $_user
     */
    public function setUserName($_user)
    {
        $this->user = $_user;
    }

    /**
     * @param string $_password
     */
    public function setPassword($_password)
    {
        $this->password = $_password;
    }
}