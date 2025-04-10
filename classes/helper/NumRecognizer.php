<?php

/**
 * @author Vladyslav Zhyokin
 */

/**
 * @brief Helper class for identifying and processing various component number types.
 *
 * This class provides functionality for validating and extracting information from
 * different component identification numbers in the manufacturing system, including:
 * - WPN (Work Process Number)
 * - DID (Direct Identification)
 * - SSCC (Serial Shipping Container Code)
 * 
 * It connects to the database to retrieve complete information about components
 * based on their identification numbers, including warehouse location, part properties,
 * supplier information, and more. It also integrates with SAP/REST services for
 * SSCC code validation and additional data retrieval.
 * 
 * @class Helper_NumRecognizer
 */
class Helper_NumRecognizer
{
    /**
     * @brief Sanitized component number (digits only)
     * @var string|null
     * @access private
     */
    private $number = null;
    
    /**
     * @brief Original (unsanitized) component number as entered by user
     * @var string|null
     * @access private
     */
    private $onumber = null;
    
    /**
     * @brief Detected number type (WPN, DID, SSCC or ERROR)
     * @var string|null
     * @access private
     */
    private $type = null;
    
    /**
     * @brief Flag indicating if the number is valid
     * @var bool
     * @access private
     */
    private $valid = true;
    
    /**
     * @brief Component status information from database
     * @var array
     * @access private
     */
    private $nstatus = array();
    
    /**
     * @brief Warehouse status information from database
     * @var array
     * @access private
     */
    private $wstatus = array();
    
    /**
     * @brief Error code for validation issues
     * @var string
     * @access private
     */
    private $ecode = "0000";
    
    /**
     * @brief Database connection handler
     * @var DBManager|null
     * @access private
     */
    private $db = null;
    
    /**
     * @brief Collection of components associated with a WPN
     * @var array|null
     * @access private
     */
    private $wpnparts= null;
    
    /**
     * @brief SAP response data for SSCC identifiers
     * @var array
     * @access private
     */
    private $sap = array();
    
    /**
     * @brief Flag indicating SAP error state
     * @var bool
     * @access private
     */
    private $saperror = false;
    
    /**
     * @brief Error message from SAP service
     * @var string|null
     * @access private
     */
    private $sapemsg = null;
    
    /**
     * @brief Uniform Resource Name for EPCIS (Electronic Product Code Information Services)
     * @var string|null
     * @access private
     */
    private $urn = null;

    /**
     * @brief Constructs a new number recognizer instance.
     *
     * Initializes the database connection, stores the original number,
     * sanitizes the input by removing non-digit characters, and
     * automatically determines the number type.
     *
     * @param string $number Component identification number to process
     * @access public
     */
    public function __construct($number)
    {
        $this->db = new DBManager(DB, "trace", "spfnut");
        $this->onumber = $number;
        $this->number = preg_replace('/\D/', '', $number);
        $this->setNumType();
    }

    /**
     * @brief Determines the type of identification number.
     *
     * Categorizes the number as one of:
     * - SSCC (Serial Shipping Container Code): 20 digits starting with "00"
     * - WPN (Work Process Number): 10 digits, value >= 2000000000
     * - DID (Direct Identification): 10 digits, value < 2000000000
     * - ERROR: Other formats
     * 
     * After identifying the type, it calls the appropriate methods to retrieve
     * additional information about the component.
     *
     * @return void
     * @access private
     */
    private function setNumType()
    {
        $sscc = substr($this->number, 0, 2);
        if ($sscc == '00' and preg_match('/^\d{20}$/', $this->number)) {
            $this->type = "SSCC";
            $this->parseEpcId();
            $this->getRestData();
            //$this->setSAPStatus();
            $this->setNStatus();
            if($this->ecode === "0002"){
                $this->setNStatusForGTIN();
            }else {
                $this->setWarehouseStatus();
            }
        } else {
            if (preg_match('/^\d{10}$/', $this->number)) {
                if ($this->number >= 2000000000) {
                    $this->type = "WPN";
                    $this->setNStatusForWPN();
                } else {
                    $this->type = "DID";
                    $this->setNStatus();
                    $this->setWarehouseStatus();
                }
            } else {
                $this->valid = false;
                $this->type = "ERROR";
                $this->ecode = "0001"; // Invalid number format
            }
        }
    }

    /**
     * @brief Retrieves component status information from database.
     *
     * Builds and executes a SQL query based on number type to get detailed
     * component information. Queries differ between SSCC and DID numbers.
     * Updates the validation status if the component is not found.
     *
     * @return void
     * @access private
     */
    private function setNStatus()
    {
        if($this->type == "SSCC"){
            $filter = "where sscc = :num";
            $params = array(':num' => $this->number);
        }else{
            $filter = "where reel_group_id = :rgid and reel_id = :rid";
            $params = array(':rgid' => substr($this->number, 0, 5), ':rid' => substr($this->number, 5, 5));
        }
        $sql = "select reel_group_id, 
                       reel_id, 
                       production_date, 
                       enter_date, 
                       to_char(scrap_date, 'YYYY/MM/DD HH24:MI') as scrap_date, 
                       lot_number, 
                       residue,
                       expire_date, 
                       sscc, 
                       wpn, 
                       on_production_limit, 
                       dry_chamber, 
                       dry_chamber_time, 
                       part_name, 
                       buffer_part,
                       trace_part,
                       description,
                       suplier_id,
                       part_supliers.name 
                  from part_reels 
                  join part_reel_groups using (reel_group_id) 
                  join part_types using (part_type_id)
                  join part_supliers using (suplier_id) $filter";
        $n = $this->db->doSql($sql, $res, $params);
        if ($n == 1) {
            $this->nstatus = $res;
        } else {
            $this->valid = false;
            $this->ecode = "0002"; // Number not found in database
        }
    }

    /**
     * @brief Retrieves WPN-specific component information from database.
     *
     * Executes a SQL query to get information about a Work Process Number (WPN).
     * WPNs require a different query compared to DID or SSCC numbers since they
     * represent a process/job rather than a specific physical component.
     * Updates validation status if the WPN is not found.
     *
     * @return void
     * @access private
     */
    private function setNStatusForWPN()
    {
        $sql = "select reel_group_id, 
                       null as reel_id,
                       null as production_date,
                       null as enter_date,
                       null as scrap_date,
                       null as lot_number,
                       null as expire_date,
                       null as sscc,
                       wpn,
                       on_production_limit,
                       dry_chamber,
                       dry_chamber_time,
                       part_name,
                       buffer_part,
                       trace_part,
                       description,
                       suplier_id,
                       name
                  from part_reel_groups 
                  join part_types using (part_type_id)
                  left join (
                    select base.reel_group_id, base.reel_id, prs.suplier_id from (
                   select max(pr.reel_group_id) as reel_group_id, max(reel_id) as reel_id
                        from part_reels pr 
                        join part_reel_groups prg on (pr.reel_group_id = prg.reel_group_id)
                       where wpn = :wpn) base
                   join part_reels prs on (base.reel_group_id = prs.reel_group_id and base.reel_id = prs.reel_id)) using (reel_group_id)
                  left join part_supliers using (suplier_id)
                 where wpn = :wpn";
        $params = array(':wpn' => $this->number);
        $n = $this->db->doSql($sql, $res, $params);
        if ($n == 1) {
            $this->nstatus = $res;
        } else {
            $this->valid = false;
            $this->ecode = "0002"; // Number not found in database
        }
    }

    /**
     * @brief Retrieves GTIN-specific component information from database.
     *
     * Used for SSCC numbers when they aren't found directly in the database.
     * Attempts to look up the component by the GTIN number obtained from SAP.
     * Updates validation status based on whether the GTIN is found.
     *
     * @return void
     * @access private
     */
    private function setNStatusForGTIN()
    {
        $gtin = substr($this->sap->GTIN_NO, 1);
        $sql = "select reel_group_id, 
                       null as reel_id,
                       null as production_date,
                       null as enter_date,
                       null as scrap_date,
                       null as lot_number,
                       null as expire_date,
                       null as sscc,
                       wpn,
                       on_production_limit,
                       dry_chamber,
                       dry_chamber_time,
                       part_name,
                       buffer_part,
                       trace_part,
                       description,
                       suplier_id,
                       name
                  from part_reel_groups 
                  join part_types using (part_type_id)
                  left join (
                    select max(pr.reel_group_id) as reel_group_id, max(reel_id) as reel_id, suplier_id 
                      from part_reels pr 
                      join part_reel_groups prg on (pr.reel_group_id = prg.reel_group_id)
                     where gtin = :gtin group by suplier_id) using (reel_group_id)
                  left join part_supliers using (suplier_id)
                 where gtin = :gtin";
        $params = array(':gtin' => $gtin);
        $n = $this->db->doSql($sql, $res, $params);
        if ($n == 1) {
            $this->nstatus = $res;
            $this->valid = true;
            $this->ecode = "0000"; // OK
        } else {
            $this->valid = false;
            $this->ecode = "0023"; // GTIN number not found in the database
        }
    }

    /**
     * @brief Retrieves data from SAP using SOAP web service.
     *
     * Communicates with the SAP system to retrieve additional information
     * for SSCC numbers. Handles errors from the SOAP service.
     * 
     * Note: This method is currently commented out in the main flow.
     *
     * @return void
     * @access private
     */
    private function setSAPStatus()
    {
        if(strlen($this->number) === 20){
            try {
                $sap_sscc = substr($this->number, 2);
                $client = new SoapClient("classes\helper\PIP_SI_ReadData_Out_Sync.wsdl", array('login' => "MES-USERPI", 'password' => "Mesintegration17"));
                $params = array("SSCC_NO" => $sap_sscc, "HU_NO" => "");
                $res =  $client->__soapCall("SI_ReadData_Out_Sync", array($params));
                $this->sap = $res;
            }catch(SoapFault $e){
                $this->saperror = true;
                $this->sapemsg = $e->{'detail'}->{'_-SERKEM_-088_TRC_001_008.Exception'}->{'Text'};
            }
        }
    }

    /**
     * @brief Public method to initiate REST data retrieval.
     *
     * Calls setRestData to retrieve data from REST API and
     * stores the result in the sap property.
     *
     * @return void
     * @access public
     */
    public function getRestData()
    {
        $res = $this->setRestData("GET");
        $this->sap = $res;
    }

    /**
     * @brief Retrieves data from REST API.
     *
     * Makes an HTTP request to a REST API to get additional information
     * about an SSCC number, particularly its GTIN and other details.
     * Supports different HTTP methods (GET, POST, PUT) but primarily used with GET.
     *
     * @param string $method HTTP method to use (GET, POST, PUT)
     * @param mixed $data Optional data to send with the request
     * @return object Response data with GTIN and component information
     * @access private
     */
    private function setRestData($method, $data = false)
    {
        $curl = curl_init();
        $url = 'http://wpipsg.wabco.de:8174/RESTAdapter/SSCCInspector/hu/'.$this->urn;
        switch ($method)
        {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case "PUT":
                    curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, "MES-USERPI:Mesintegration17");
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = json_decode( curl_exec($curl) );
        $epcis = basename($res->{'MT_huContent_resp'}->{'batchItems'}->{'batchItemId'});
        $ex = explode("_", $epcis);
        $gtex = explode(".", $ex[0]);
        $gtin = $this->makeGTIN($gtex[0], $gtex[1]);
        //mtc::print_r($url, false);
        $x = (object) [
            'GTIN_NO' => $gtin,
            'ASN_NO' => $gtex[2],
            'BATCH_NO' => $ex[1],
            'SSCC_NO' => '',
            'LIFNR' => str_pad( $res->{'MT_huContent_resp'}->{'supplierId'}, "10", "0", STR_PAD_LEFT),
            'VEMNG' => $res->{'MT_huContent_resp'}->{'batchItems'}->{'quantity'}.".000"
        ];

        curl_close($curl);
        return $x;
    }

    /**
     * @brief Calculates a GTIN check digit and formats the number.
     *
     * Implements the GTIN check digit algorithm to generate a properly
     * formatted Global Trade Item Number from component parts.
     *
     * @param string $gt1 First part of the GTIN
     * @param string $gt2 Second part of the GTIN
     * @return string Complete GTIN with check digit
     * @access private
     */
    private function makeGTIN($gt1, $gt2)
    {
        $gtin_no_check = str_pad( $gt1.ltrim($gt2, '0'), "13", "0", STR_PAD_LEFT);
        $gtsum = 0;
        for($i=0; $i<strlen($gtin_no_check); $i++){
            $multiplier = ($i%2 === 0)? 3 : 1;
            $n = (int) substr($gtin_no_check, $i, 1);
            $m = $n * $multiplier;
            $gtsum = $gtsum + $m;
        }
        $check_sum = 10-($gtsum%10);
        return $gtin_no_check.$check_sum;
    }

    /**
     * @brief Retrieves warehouse storage information for a component.
     *
     * Queries the database for warehouse location details for non-scrapped
     * components. Updates validation status if the component is not found in
     * any warehouse location.
     *
     * @return void
     * @access private
     */
    private function setWarehouseStatus()
    {
        if (isset($this->nstatus['SCRAP_DATE']) and $this->nstatus['SCRAP_DATE'][0] == '' and $this->valid) {
            $sql = "select ew.shelf_id, es.shelf_name, ew.area_id, ea.area_name, ea.hostname 
                      from ewa_warehouse ew
                      join ewa_shelfs es on (ew.shelf_id = es.shelf_id) 
                      join ewa_areas ea on (ew.area_id = ea.area_id) 
                     where ew.reel_group_id = :rgid 
                       and ew.reel_id = :rid";
            $params = array(':rgid' => $this->nstatus['REEL_GROUP_ID'][0], ':rid' => $this->nstatus['REEL_ID'][0]);
            $n = $this->db->doSql($sql, $res, $params);
            if ($n == 1) {
                $this->wstatus = $res;
            } else {
                $this->valid = false;
                $this->ecode = "0005"; // Number not found in warehouse
            }
        }
    }

    /**
     * @brief Retrieves all non-scrapped physical components for a WPN.
     *
     * For Work Process Numbers, retrieves all related physical components
     * that are currently in inventory (not scrapped). Includes warehouse
     * location for each component.
     *
     * @return void
     * @access private
     */
    private function setWPNParts()
    {
        if($this->type == 'WPN' and $this->valid) {
            $sql = "select prg.wpn, 
                           pr.reel_group_id, 
                           pr.reel_id, 
                           pr.production_date, 
                           pr.enter_date, 
                           pr.expire_date, 
                           pr.lot_number, 
                           nvl(sscc, full_did) as num, 
                           prg.on_production_limit, 
                           prg.dry_chamber,
                           prg.part_name,
                           prg.buffer_part,
                           prg.trace_part,
                           prg.gtin,
                           ew.reel_id,
                           es.shelf_name,
                           ew.area_id,
                           ea.area_name
                      from part_reels pr
                      left join part_reel_groups prg on (pr.reel_group_id = prg.reel_group_id)
                      left join ewa_warehouse ew on (pr.reel_group_id = ew.reel_group_id and pr.reel_id = ew.reel_id)
                      join ewa_areas ea on (ew.area_id = ea.area_id)
                      join ewa_shelfs es on (ew.shelf_id = es.shelf_id)
                     where scrap_date is null and wpn = :wpn";
            $params = array(':wpn' => $this->number);
            $this->db->doSql($sql, $res, $params);
            $this->wpnparts = $res;
        }
    }

    /**
     * @brief Parses an SSCC number to create an EPC URN.
     *
     * Converts an SSCC (Serial Shipping Container Code) into an EPC URN
     * (Electronic Product Code Uniform Resource Name) format for use with
     * external systems like the REST API.
     *
     * @param string|bool $sscc Optional SSCC to parse (uses $this->number if false)
     * @return void
     * @access private
     */
    private function parseEpcId($sscc = false)
    {
        $num = ($sscc === false)? $this->number : $sscc;
        $str = ltrim($num, '0');
        $gln = substr($str, 0, 7);
        $spart = sprintf('%010d', substr(substr($str, 7), 0, -1));
        $this->urn = "urn:epc:id:sscc:".$gln.".".$spart;
    }

    /**
     * @brief Returns only the group ID
     * @return int
     */
    public function getGroupId()
    {
        return $this->nstatus['REEL_GROUP_ID'][0];
    }

    /**
     * @brief Returns only the part ID
     * @return int
     */
    public function getPartId()
    {
        return $this->nstatus['REEL_ID'][0];
    }

    /**
     * @brief Returns the full part ID
     * @return int
     */
    public function getFullId()
    {
        return $this->nstatus['REEL_GROUP_ID'][0] . $this->nstatus['REEL_ID'][0];
    }

    /**
     * @brief Returns the SSCC number of the part
     * @return string
     */
    public function getSSCC()
    {
        return (strlen($this->nstatus['SSCC'][0]) != 20)? $this->nstatus['REEL_GROUP_ID'][0] . $this->nstatus['REEL_ID'][0] : $this->nstatus['SSCC'][0];
    }

    /**
     * @brief Returns the WPN number of the part
     * @return int
     */
    public function getWPN()
    {
        return $this->nstatus['WPN'][0];
    }

    /**
     * @brief Returns the production date of the part
     * @return string
     */
    public function getProductionDate()
    {
        return $this->nstatus['PRODUCTION_DATE'][0];
    }

    /**
     * @brief Returns the receipt date of the part
     * @return string
     */
    public function getEnterDate()
    {
        return $this->nstatus['ENTER_DATE'][0];
    }

    /**
     * @brief Returns the scrapping date of the part
     * @return string
     */
    public function getScrapDate()
    {
        return $this->nstatus['SCRAP_DATE'][0];
    }

    /**
     * @brief Returns the expiration date of the part
     * @return string
     */
    public function getExpireDate()
    {
        return $this->nstatus['EXPIRE_DATE'][0];
    }

    /**
     * @brief Returns the LOT number of the part
     * @return string
     */
    public function getLot()
    {
        return $this->nstatus['LOT_NUMBER'][0];
    }

    /**
     * @brief Returns the production limit for the part
     * @return int
     */
    public function getProductionLimit()
    {
        return $this->nstatus['ON_PRODUCTION_LIMIT'][0];
    }

    /**
     * @brief Whether the part must be stored in a dry chamber
     * @return bool
     */
    public function isDryChamberPart()
    {
        return ($this->nstatus['DRY_CHAMBER'][0] == 1)? true : false;
    }

    /**
     * @brief Returns the required dry chamber time for the part
     * @return int
     */
    public function getDryChamberTime()
    {
        return $this->nstatus['DRY_CHAMBER_TIME'][0];
    }

    /**
     * @brief Returns the name of the part
     * @return string
     */
    public function getPartName()
    {
        return $this->nstatus['PART_NAME'][0];
    }

    /**
     * @brief Returns the description of the part
     * @return string
     */
    public function getPartDescription()
    {
        return $this->nstatus['DESCRIPTION'][0];
    }

    /**
     * @brief Returns the name of the part supplier (if defined)
     * @return string
     */
    public function getSuplierName()
    {
        return $this->nstatus['NAME'][0];
    }

    /**
     * @brief Returns the ID of the part supplier (if defined)
     * @return string
     */
    public function getSuplierId()
    {
        return $this->nstatus['SUPLIER_ID'][0];
    }

    /**
     * @brief Returns the shelf ID in the warehouse for the specified part
     * @return int
     */
    public function getShelfId()
    {
        return $this->wstatus['SHELF_ID'][0];
    }

    /**
     * @brief Returns the shelf name in the warehouse for the specified part
     * @return string
     */
    public function getShelfName()
    {
        return (isset($this->wstatus['SHELF_NAME'][0]))? $this->wstatus['SHELF_NAME'][0] : null;
    }

    /**
     * @brief Returns the area ID where the specified part is located
     * @return int
     */
    public function getAreaId()
    {
        return $this->wstatus['AREA_ID'][0];
    }

    /**
     * @brief Returns the area name where the specified part is located
     * @return string
     */
    public function getAreaName()
    {
        return (isset($this->wstatus['AREA_NAME'][0]))? $this->wstatus['AREA_NAME'][0] : null;
    }

    /**
     * @brief Returns the hostname that ordered the specified part (if defined)
     * @return string|null
     */
    public function getHostName()
    {
        return $this->wstatus['HOSTNAME'][0];
    }

    /**
     * @brief Whether the part is buffered
     * @return bool
     */
    public function isBufferPart()
    {
        return ($this->nstatus['BUFFER_PART'][0] == '0') ? false : true;
    }

    /**
     * @brief Whether the part is covered by the trace system
     * @return bool
     */
    public function isTracePart()
    {
        return ($this->nstatus['TRACE_PART'][0] == 'N') ? false : true;
    }

    /**
     * @brief Whether the part is scrapped
     * @return bool
     */
    public function isScrapped()
    {
        return ($this->nstatus['SCRAP_DATE'][0] == '') ? false : true;
    }

    /**
     * @brief Returns the quantity of parts in the reel
     * @return float|int
     */
    public function getVolume()
    {
        return $this->nstatus['RESIDUE'][0];        
    }
    
    /**
     * @brief Whether the entered part number is valid
     * @return bool
     */
    public function isValidNumber()
    {
        return $this->valid;
    }

    /**
     * @brief Returns the part number cleaned of non-digit characters
     * @return string
     */
    public function getCleanNumber()
    {
        return $this->number;
    }

    /**
     * @brief Returns the original number as entered by the user
     * @return string
     */
    public function getEnteredNumber()
    {
        return $this->onumber;
    }

    /**
     * @brief Returns the type of entered number (WPN, DID, or SSCC)
     * @return string
     */
    public function getNumberType()
    {
        return $this->type;
    }

    /**
     * @brief Returns the error code
     * @return string
     */
    public function getEcode()
    {
        return $this->ecode;
    }

    /**
     * @brief Returns a list of all barcodes for the given WPN that are currently in stock
     * @return array
     */
    public function getWPNParts()
    {
        $this->setWPNParts();
        return $this->wpnparts;
    }

    /**
     * @brief Returns the SAP status
     * @return bool
     */
    public function getSapStatus()
    {
        return $this->saperror;
    }

    /**
     * @brief Returns the GTIN number from SAP
     * @return string|null
     */
    public function getSAP_GTIN_NO()
    {
        return (isset($this->sap->{'GTIN_NO'}))? $this->sap->{'GTIN_NO'} : null;
    }

    /**
     * @brief Returns the ASN number from SAP
     * @return string|null
     */
    public function getSAP_ASN_NO()
    {
        return (isset($this->sap->{'ASN_NO'}))? $this->sap->{'ASN_NO'} : null;
    }

    /**
     * @brief Returns the batch number from SAP
     * @return string|null
     */
    public function getSAP_BATCH_NO()
    {
        return (isset($this->sap->{'BATCH_NO'}))? $this->sap->{'BATCH_NO'} : null;
    }

    /**
     * @brief Returns the lifnr number from SAP
     * @return string|null
     */
    public function getSAP_LIFNR()
    {
        return (isset($this->sap->{'LIFNR'}))? $this->sap->{'LIFNR'} : null;
    }

    /**
     * @brief Returns the vemng number from SAP
     * @return string|null
     */
    public function getSAP_VEMNG()
    {
        return (isset($this->sap->{'VEMNG'}))? $this->sap->{'VEMNG'} : null;
    }

    /**
     * @brief Returns the SAP error message
     * @return string|null
     */
    public function getSAP_ERROR_MSG()
    {
        return $this->sapemsg;
    }

    /**
     * @brief Destructor to clean up resources.
     * 
     * Ensures the database connection is properly closed when
     * the object is destroyed.
     *
     * @access public
     */
    public function __destruct()
    {
        $this->db = null;
    }
}