<?php
/**
 * @file
 * @version 0.2
 * @copyright 2023 CN-Consult GmbH
 * @author Jens Stahl <jens.stahl@cn-consult.eu>
 *
 * License: Please check the LICENSE file for more information.
 */

namespace PBST\ProfitBricksApi;

use Exception;
use DateTime;

/**
 * Class ProfitBricksApi connects to the official ProfitBricksApi and returns easy usable objects.
 */
class ProfitBricksApi
{
    private string $user = "";
    private string $password = "";
    const profitBricksSnapshotApi = "https://api.profitbricks.com/cloudapi/v3/snapshots?depth=1";
    const profitBricksDataCenterApi = "https://api.profitbricks.com/cloudapi/v3/datacenters?depth=2";
    const profitBricksServerApi = "https://api.profitbricks.com/cloudapi/v3/datacenters/{data-center-id}/servers?depth=2";
    const profitBricksDiskApi = "https://api.profitbricks.com/cloudapi/v3/datacenters/{data-center-id}/servers/{server-id}/volumes?depth=1";
    const profitBricksDeleteSnapshot = "https://api.profitbricks.com/cloudapi/v3/snapshots/{snapshot-id}";
    const profitBricksCreateSnapshot = "https://api.profitbricks.com/cloudapi/v3/datacenters/{data-center-id}/volumes/{volume-id}/create-snapshot";
    const profitBricksServerStart = "https://api.profitbricks.com/cloudapi/v4/datacenters/{dataCenterId}/servers/{serverId}/start";
    const profitBricksServerStop = "https://api.profitbricks.com/cloudapi/v4/datacenters/{dataCenterId}/servers/{serverId}/stop";

    /**
     * Reads all information about snapshots from ProfitBricks into usable objects.
     *
     * @return Snapshot[]|bool False or all snapshots independent of its status.
     * @throws Exception
     */
    public function snapshots(): array | bool
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
     * @return DataCenter[]|bool Array of DataCenter of false when an error occurred.
     * @throws Exception
     */
    public function dataCenters(): array | bool
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
     * Provides all information of all VMs in every DC in class property.
     *
     * @return VirtualMachine[] Array of virtual machines
     * @throws Exception
     */
    public function allVirtualMachines(): array
    {
        $virtualMachines = array();
        foreach ($this->dataCenters() as $dataCenter)
        {
            $virtualMachines = array_merge($virtualMachines, $this->virtualMachinesFor($dataCenter));
        }
        return $virtualMachines;
    }

    /**
     * Reads all information about virtual machines/servers from ProfitBricks into usable objects.
     *
     * @param DataCenter $_dataCenter determines the data center for which the virtual machines should be searched.
     * @return VirtualMachine[]|bool Array of VirtualMachine or false when an error occurred.
     * @throws Exception
     */
    public function virtualMachinesFor(DataCenter $_dataCenter): array|bool
    {
        $virtualMachines = false;
        $api = str_replace("{data-center-id}", $_dataCenter->id, self::profitBricksServerApi);
        foreach ($this->readFromProfitBricks($api) as $virtualMachine)
        {
            $virtualMachines[$virtualMachine->id] = new VirtualMachine($virtualMachine);
        }
        return $virtualMachines;
    }

    /**
     * Reads all information about virtual disks (called volumes) belonging to a virtual machine from ProfitBricks into usable objects.
     *
     * @param string $_dataCenterId determines the data center, in which the virtual machine is.
     * @param VirtualMachine $_virtualMachine determines the virtual machine, which are the disks attached to.
     * @return VirtualDisk[]|bool Array of VirtualDisk or false when an error occurred.
     * @throws Exception
     */
    public function virtualDisks(VirtualMachine $_virtualMachine, string $_dataCenterId): array | bool
    {
        $virtualDisks = false;
        $api = str_replace("{data-center-id}", $_dataCenterId, self::profitBricksDiskApi);
        $api = str_replace("{server-id}", $_virtualMachine->id, $api);
        foreach ($this->readFromProfitBricks($api) as $virtualDisk)
        {
            $virtualDisks[$virtualDisk->id] = new VirtualDisk($virtualDisk);
        }
        return $virtualDisks;
    }


    /**
     * This function imports strings and JSON from ProfitBricks, which it cuts down to a usable array of objects.
     *
     * @param string $_profitBricksApi predefined const values
     * @return mixed Array of objects
     * @throws Exception when something with the connection process fails.
     */
    private function readFromProfitBricks(string $_profitBricksApi): mixed
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

            if (str_contains($response, "HTTP/2 200"))
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
                throw new Exception((str_contains($response, "HTTP/2 401 Unauthorized")) ? $token."\nCredentials for ProfitBricks are invalid!" : $token);
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
    public function makeSnapshot(DataCenter $_dataCenter,
                                 VirtualMachine $_virtualMachine,
                                 VirtualDisk $_virtualDisk,
                                 string $_preDescription = ""): Snapshot
    {// calculate KW
        $now = new DateTime();
        $kw = $now->format('Y-W');

        $snapShotName = $_virtualMachine->name . "_" . $_virtualDisk->name . "_KW$kw";
        $snapShotDescription = $_preDescription . $_dataCenter->name . "-->" . $_virtualMachine->name . "-->" . $_virtualDisk->name;

        $postData = "name=".$snapShotName."&description=".$snapShotDescription;
        $authorisation = base64_encode($this->user.":".$this->password);
        $api = str_replace("{data-center-id}", $_dataCenter->id, self::profitBricksCreateSnapshot);
        $api = str_replace("{volume-id}", $_virtualDisk->id, $api);
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
            if (str_contains($response, "HTTP/2 202 Accepted"))
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
                throw new Exception((str_contains($response, "HTTP/2 401 Unauthorized")) ? $token."\nCredentials for ProfitBricks are invalid!" : $token);
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
     * @throws Exception
     */
    public function deleteSnapshot(string $_snapShotId): bool
    {
        $api = str_replace("{snapshot-id}", $_snapShotId, self::profitBricksDeleteSnapshot);
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
            if (!str_contains($response, "HTTP/2 202 Accepted"))
            {
                $token = strtok($response, "\n");
                throw new Exception((str_contains($response, "HTTP/2 401 Unauthorized")) ? $token."\nCredentials for ProfitBricks are invalid!" : $token);
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
     * Starts a server on IONOS
     *
     * @param string $_dataCenterId DataCenterID
     * @param string $_serverId ServerID
     * @return bool True on success
     * @throws Exception
     */
    public function startServer(string $_dataCenterId, string $_serverId): bool
    {
        return $this->startStopServer($_dataCenterId, $_serverId, "on");
    }

    /**
     * Stops a server on IONOS
     *
     * @param string $_dataCenterId DataCenterID
     * @param string $_serverId ServerID
     * @return bool True on success
     * @throws Exception
     */
    public function stopServer(string $_dataCenterId, string $_serverId): bool
    {
        return $this->startStopServer($_dataCenterId, $_serverId, "off");
    }

    /**
     * Starts or stops a server on IONOS.
     *
     * @param string $_dataCenterId DataCenterID
     * @param string $_serverId ServerID
     * @param string $_action "On" or "off" dependent to your wishes
     * @return bool True on success
     * @throws Exception
     */
    private function startStopServer(string $_dataCenterId, string $_serverId, string $_action): bool
    {
        if (strtolower($_action)=="on") $api = self::profitBricksServerStart;
        elseif (strtolower($_action)=="off") $api = self::profitBricksServerStop;
        else throw new Exception("Wrong state in calling this method.");
        $api = str_replace("{dataCenterId}", $_dataCenterId, $api);
        $api = str_replace("{serverId}", $_serverId, $api);
        $authorisation = base64_encode($this->user.":".$this->password);
        $curl = curl_init($api);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_SSLVERSION, 6);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Basic $authorisation"));
        if (curl_exec($curl))
        {
            $response = curl_multi_getcontent($curl);
            if (!str_contains($response, "HTTP/2 202 Accepted"))
            {
                $token = strtok($response, "\n");
                throw new Exception((str_contains($response, "HTTP/2 401 Unauthorized")) ? $token."\nCredentials for IONOS are invalid!" : $token);
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
    public function setUserName(string $_user): void
    {
        $this->user = $_user;
    }

    /**
     * @param string $_password
     */
    public function setPassword(string $_password): void
    {
        $this->password = $_password;
    }
}
