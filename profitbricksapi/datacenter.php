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

use DateTime;
use DateTimeZone;

/**
 * Class DataCenter is a quite simple data container which wrap all available values.
 *
 * The variables can resist and can be used in future developed code even if ProfitBricks changes the api construction.
 * In this case just the variables here have to be modified and the code still works.
 */
class DataCenter
{
    public string $id;
    public string $type;
    public string $href;
    public DateTime $createdDate;
    public string $createdBy;
    public string $etag;
    public DateTime $lastModifiedDate;
    public string $lastModifiedBy;
    public string $name;
    public string $description = "";
    public string $location;
    public int    $version;
    public array  $features;
    public array  $virtualMachines;

    public function __construct(object $_profitBricksDataCenter)
    {
        $this->id = $_profitBricksDataCenter->id;
        $this->type = $_profitBricksDataCenter->type;
        $this->href = $_profitBricksDataCenter->href;
        $this->createdDate = new DateTime($_profitBricksDataCenter->metadata->createdDate);
        $this->createdDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $this->createdBy = $_profitBricksDataCenter->metadata->createdBy;
        $this->etag = $_profitBricksDataCenter->metadata->etag;
        $this->lastModifiedDate = new DateTime($_profitBricksDataCenter->metadata->lastModifiedDate);
        $this->lastModifiedDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $this->lastModifiedBy = $_profitBricksDataCenter->metadata->lastModifiedBy;
        $this->name = $_profitBricksDataCenter->properties->name;
        if ($_profitBricksDataCenter->properties->description)
            $this->description = $_profitBricksDataCenter->properties->description;
        $this->location = $_profitBricksDataCenter->properties->location;
        $this->version = $_profitBricksDataCenter->properties->version;
        $this->features = $_profitBricksDataCenter->properties->features;
        $this->virtualMachines = array();
    }
}
/**
 *  This object class is filled by values submitted by ProfitBricksAPI. The original values are:
 *  The depths of this objects are depending on the "?depths=x" value, submitted by GET to the API. (1-5)
 *
 *  $dataCenter->id                             id
 *  $dataCenter->type                           mostly 'snapshot'
 *  $dataCenter->href                           link
 *  $dataCenter->metadata                       object
 *  $dataCenter->metadata->createdDate          date of creation
 *  $dataCenter->metadata->createdBy            user name
 *  $dataCenter->metadata->etag                 cryptic number
 *  $dataCenter->metadata->lastModifiedDate     date of last modification
 *  $dataCenter->metadata->lastModifiedBy       user name
 *  $dataCenter->metadata->state                availability
 *  $dataCenter->properties                     object
 *  $dataCenter->properties->name               name
 *  $dataCenter->properties->description        description
 *  $dataCenter->properties->location           location
 *  $dataCenter->properties->version            version
 *  $dataCenter->properties->features           array of strings like ["SSD", "MULTIPLE_CPU"]
 *  $dataCenter->entities                       object
 *  $dataCenter->entities->servers              object
 *  $dataCenter->entities->servers->id          id
 *  $dataCenter->entities->servers->type        mostly just "collection"
 *  $dataCenter->entities->servers->href        href
 *  $dataCenter->entities->volumes              object
 *  $dataCenter->entities->volumes->id          id
 *  $dataCenter->entities->volumes->type        mostly just "collection"
 *  $dataCenter->entities->volumes->href        href
 *  $dataCenter->entities->loadbalancers        object
 *  $dataCenter->entities->loadbalancers->id    id
 *  $dataCenter->entities->loadbalancers->type  mostly just "collection"
 *  $dataCenter->entities->loadbalancers->href  href
 *  $dataCenter->entities->lans                 object
 *  $dataCenter->entities->lans->id             id
 *  $dataCenter->entities->lans->type           mostly just "collection"
 *  $dataCenter->entities->lans->href           href
 */
