<?php

/**
 * @author Vladyslav Zhyokin
 */

/**
 * Main controller class for the Barcode Instruction Management (BIM) project.
 *
 * This controller is responsible for handling all administration-related
 * operations including barcode management, instruction editing, copying,
 * history tracking, and note management.
 *
 * @class Controllers_AdministrationControl
 * @extends W_Controller_Controller
 */
class Controllers_AdministrationControl extends W_Controller_Controller {
    
    /**
     * @var array $validationArr Stores validation results during processing
     * @access private
     */
    private $validationArr = [];
    
    /**
     * @var array $savedBarcodes Collection of successfully saved barcodes
     * @access private
     */
    private $savedBarcodes = [];
    
    /**
     * @var array $noSavedBarcodes Collection of barcodes that couldn't be saved
     * @access private
     */
    private $noSavedBarcodes = [];

    /**
     * Initializes the controller.
     * 
     * This method is called before any action is executed.
     * Override this method to perform setup operations.
     *
     * @return void
     */
    public function init(){
        // Initialization code if needed
    }

    /**
     * Main index action for the administration controller.
     * 
     * Prepares session data, error messages, and retrieves typical notes
     * for display on the main administration page.
     *
     * @return void
     */
    public function indexAction() {
        $this->pagec->sessionData = isset($_SESSION["FormPrepare"]) ? $_SESSION["FormPrepare"] : array();
        $this->pagec->error = isset($_SESSION["error"]) ? $_SESSION["error"] : null;
        $this->pagec->savedBarcodes = isset($_SESSION["savedBarcodes"]) ? $_SESSION["savedBarcodes"] : null;
        $this->pagec->noSavedBarcodes = isset($_SESSION["noSavedBarcodes"]) ? $_SESSION["noSavedBarcodes"] : null;
        $this->findBarcViaWpnAfterLoad();

        // Get typical notes
        $this->pagec->getTypicalNotes = $getTypicalNotes = $this->sqlc->getTypicalNotes();
    }
   
    /**
     * Displays detailed information about a specific history record.
     * 
     * Retrieves a history record by ID and compares it with the previous record
     * to identify changes. Prepares data about lines, machine programs, notes,
     * and other elements related to the history record.
     *
     * @return void
     * @throws Exception When an error occurs during data retrieval or processing
     */
    public function historyDetailAction() {
        try {
            $id = $_GET['id'];
            if (isset($id)) {
                // Get current history record by ID_HISTORY
                $currentRecord = $this->sqlc->getHistoryRecordById($id);
                if ($currentRecord !== false) {
                    $this->pagec->historyRecord = $currentRecord;

                    // Get previous history record for the same product and side
                    $previousRecord = $this->sqlc->getPreviousHistoryRecord($currentRecord['BARC4'], $currentRecord['SIDE'], $currentRecord['CHANGE_DATE']);
                    $this->pagec->previousRecord = $previousRecord;

                    // Determine which fields have changed
                    $changedFields = [];
                    if ($previousRecord !== false) {
                        foreach ($currentRecord as $field => $currentValue) {
                            // Skip fields that shouldn't be compared
                            if (in_array($field, ['ID_HISTORY', 'CHANGE_DATE'])) {
                                continue;
                            }

                            $previousValue = isset($previousRecord[$field]) ? $previousRecord[$field] : null;
                            if ($previousValue != $currentValue) {
                                $changedFields[] = $field;
                            }
                        }
                    }
                    $this->pagec->changedFields = $changedFields;

                    // Get list of lines
                    $lines = explode(',', $currentRecord['LINE']);
                    $this->pagec->devices = Helper_AddFunct::prepareListDeviceOnPage($lines);

                    // Get print programs
                    $this->pagec->printProg = [
                        'PROGRAM' => [$currentRecord['PROGRAM_PRINT_PROG']],
                        'LINE' => $lines
                    ];

                    // Get machine programs
                    $machinePrograms = [];
                    $machineProgramFields = [];
                    foreach ($lines as $line) {
                        $line = strtolower(trim($line));
                        $machineProgramField = 'MACHINE_PROGRAM_' . strtoupper($line);
                        $machineProgramFields[] = $machineProgramField;
                        if (isset($currentRecord[$machineProgramField])) {
                            $machinePrograms[] = $currentRecord[$machineProgramField];
                        } else {
                            $machinePrograms[] = '';
                        }
                    }

                    $this->pagec->dockPro = [
                        'LINE' => $lines,
                        'PROGRAM' => $machinePrograms
                    ];

                    // Identify changed machine programs
                    $changedLines = [];
                    if ($previousRecord !== false) {
                        foreach ($machineProgramFields as $index => $machineProgramField) {
                            $currentProgram = $currentRecord[$machineProgramField];
                            $previousProgram = isset($previousRecord[$machineProgramField]) ? $previousRecord[$machineProgramField] : null;
                            if ($currentProgram != $previousProgram) {
                                $changedLines[] = $lines[$index];
                            }
                        }
                    }
                    $this->pagec->changedLines = $changedLines;

                    // Get typical notes
                    $getTypicalNotesData = $this->sqlc->getTypicalNotes();
                    $getTypicalNotesArray = [];
                    if ($getTypicalNotesData !== false && isset($getTypicalNotesData['NOTE'])) {
                        for ($i = 0; $i < count($getTypicalNotesData['NOTE']); $i++) {
                            $getTypicalNotesArray[] = [
                                'ID_COMMENT' => $getTypicalNotesData['ID_COMMENT'][$i],
                                'CATEGORY' => $getTypicalNotesData['CATEGORY'][$i],
                                'COLOR' => $getTypicalNotesData['COLOR'][$i],
                                'NOTE' => $getTypicalNotesData['NOTE'][$i],
                            ];
                        }
                    }
                    $this->pagec->getTypicalNotes = $getTypicalNotesArray;

                    // Get notes by their IDs
                    $notesIds = [];
                    for ($i = 1; $i <= 4; $i++) {
                        $notesIdKey = 'NOTES_ID_' . $i;
                        if (!empty($currentRecord[$notesIdKey])) {
                            $notesIds[] = $currentRecord[$notesIdKey];
                        }
                    }

                    if (!empty($notesIds)) {
                        $notesData = $this->sqlc->getNotesByIds($notesIds);
                        if ($notesData === false) {
                            throw new Exception("Błąd podczas pobierania notatek według ich ID.");
                        }

                        // Prepare notes array for use with {foreach}
                        // Create associative array of notes by their ID_NOTE
                        $notesDataById = [];
                        for ($i = 0; $i < count($notesData['ID_NOTE']); $i++) {
                            $idNote = $notesData['ID_NOTE'][$i];
                            $notesDataById[$idNote] = [
                                'ID_NOTE' => $idNote,
                                'ID_PARENT_NOTE' => $notesData['ID_PARENT_NOTE'][$i],
                                'CATEGORY' => $notesData['CATEGORY'][$i],
                                'NOTE' => $notesData['NOTE'][$i],
                                'CAT_TEXT' => $notesData['CAT_TEXT'][$i],
                                'IMG_NOTE' => $notesData['IMG_NOTE'][$i]
                            ];
                        }

                        // Sort notes in the order of $notesIds
                        $notesArray = [];
                        foreach ($notesIds as $id) {
                            if (isset($notesDataById[$id])) {
                                $notesArray[] = $notesDataById[$id];
                            }
                        }

                        $this->pagec->notes = $notesArray;
                    } else {
                        $this->pagec->notes = [];
                    }

                } else {
                    $this->pagec->error = "Nie udało się pobrać rekordu historii";
                }
            } else {
                $this->pagec->error = "Identyfikator rekordu nie został określony.";
            }
        } catch (Exception $e) {
            // Log error and display on page
            error_log("Exception in historyDetailAction: " . $e->getMessage());
            $this->pagec->error = "Nastąpiła pomyłka: " . $e->getMessage();
        }
    }

    /**
     * Displays history records for all barcode instructions.
     * 
     * Retrieves all history records from the database and prepares them
     * for display on the history page.
     *
     * @return void
     */
    public function historyAction() {
        $historyRecords = $this->sqlc->getHistoryRecords();
        if ($historyRecords !== false && !empty($historyRecords['ID_HISTORY'])) {
            $this->pagec->historyRecords = $historyRecords;
        } else {
            $this->pagec->error = "Nie udało się pobrać rekordów historii.";
        }
    }
    
    /**
     * Finds a barcode based on input string.
     * 
     * Searches for barcodes matching the provided partial barcode string.
     * Used for AJAX-based barcode lookup.
     *
     * @return void
     */
    public function findBarcodeAction() {
        $barc4 = $_POST["barc4"];
        $barc4 = Helper_AddFunct::removeWhitespace($barc4);
        $findBarcode = $this->sqlc->findBarcode($barc4);
        $this->pagec->findBarcode = $findBarcode['BARC4'];
    }

    /**
     * Finds new barcodes based on input string.
     * 
     * Searches for new barcodes matching the provided partial barcode string.
     * Determines if found barcodes exist in both the product database and
     * instruction database.
     *
     * @return void
     */
    public function findnewBarcodeAction() {
        $newbarc4 = $_POST["newbarc4"];
        $newbarc4 = Helper_AddFunct::removeWhitespace($newbarc4);
        $findnewBarcode = $this->sqlc->findnewBarcode($newbarc4);

        // Structure the results
        $structuredResults = [];
        if (!empty($findnewBarcode['BARC4'])) {
            foreach ($findnewBarcode['BARC4'] as $index => $barcode) {
                $structuredResults[] = [
                    'BARC4' => $barcode,
                    'SITUATION' => $findnewBarcode['SITUATION'][$index]
                ];
            }
        }
        $this->pagec->findnewBarcodeResults = $structuredResults;
    }

    /**
     * Retrieves and prepares instruction data for display.
     * 
     * Loads general information, notes, print programs, and machine programs
     * for a specific barcode and side combination.
     *
     * @return void
     */
    public function getDataInstructionsAction() {
        $barc4 = Helper_AddFunct::removeWhitespace($_POST["barc4"]);
        $side = $_POST["side"];
        $devices = explode(",", $_POST["devices"]);
        $checkDevices = Helper_AddFunct::checkDevices($devices);
        $deviceAll = Helper_AddFunct::deviceAll($devices);
        if ($checkDevices) {
            if ($deviceAll != 1) {
                $devices = $deviceAll;
            }
            $this->pagec->getTypicalNotes = $getTypicalNotes = $this->sqlc->getTypicalNotes();
            $this->pagec->devices = Helper_AddFunct::prepareListDeviceOnPage($devices);
            $findWpn = $this->sqlc->findBarcode($barc4);
            $this->pagec->findWpn = $findWpn["WABCONR"];
            $this->pagec->loadGeneralInformation = $loadGeneralInformation = $this->sqlc->loadGeneralInformation($barc4, $side);
            $this->pagec->notes = $notes = $this->sqlc->notes($barc4, $side);
            $this->pagec->printProg = $printProg = $this->sqlc->printProg($barc4, $side, $devices);
            $this->pagec->dockPro = $dockPro = $this->sqlc->dockPro($barc4, $side, $devices);
        }
    }

    /**
     * Configures notice settings.
     * 
     * Loads note categories and typical notes for the configuration page.
     *
     * @return void
     */
    public function confNoticesAction() {
        $this->pagec->categoryNotes = $categoryNotes = $this->sqlc->categoryNotes();
        $this->pagec->getTypicalNotes = $getTypicalNotes = $this->sqlc->getTypicalNotes();
    }

    /**
     * Copies instruction data from one barcode to another.
     * 
     * Handles the complete copy process including header information,
     * general data, print programs, machine programs, and notes.
     *
     * @return void
     */
    public function copyAction() {
        $post = $_POST;
        $this->pagec->devicesForMultiselect = Helper_AddFunct::devices();
    
        if (isset($post["barc4"])) {
            $barc4 = trim($post["newbarc4"]); // Use only newbarc4
            $side = $post["side"];
            $barc = $post["barc4"];
            $documentNr = isset($post["documentNr"]) ? trim($post["documentNr"]) : ''; // Get document number
    
            // Get user information
            $userName = isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'System';
            $userLastName = isset($_SESSION['user']['lastname']) ? $_SESSION['user']['lastname'] : '';
            $author = trim($userName . ' ' . $userLastName);
            
            // Get current date
            $dateCreated = date('Y/m/d H:i:s');
            
            // Insert header information
            $insertHeaderSuccess = $this->sqlc->insertHeaderIM(
                $barc4,
                $side,
                $documentNr,
                $dateCreated,
                1 // Author ID
            );
            
            if (!$insertHeaderSuccess) {
                echo "<script>alert('Error inserting header information!');</script>";
                return;
            }
            
            // Copy general information
            $copyGeneralInfoSuccess = $this->sqlc->copyGeneralInformation(
                $barc4, 
                $side, 
                $post["name"], 
                $post["subgroup"], 
                $post["tile"], 
                $post["numTilesInPanel"], 
                $post["tileWidth"], 
                $post["assembly_order"]
            );
    
            if (!$copyGeneralInfoSuccess) {
                $this->sqlc->rollback();
                echo "<script>alert('Error copying general data');</script>";
                return;
            }
    
            $allOperationsSuccessful = true;
    
            // Copy print programs
            foreach ($post as $key => $value) {
                if (strpos($key, "print_program_") === 0 && $value == 'on') {
                    $line = str_replace("print_program_", "", $key);
                    $programName = $post["print_prog"];
                    
                    $copyPrintProgIM = $this->sqlc->insertPrintProgIM($barc4, $side, $programName, $line);
                    if (!$copyPrintProgIM) {
                        $allOperationsSuccessful = false;
                        $this->sqlc->rollback();
                        echo "<script>alert('Error copying print_program data');</script>";
                        return;
                    }
                }
            }
    
            // Copy machine programs
            foreach ($post as $key => $value) {
                if (strpos($key, "machine_program_") === 0 && !empty(trim($value))) {
                    $lineName = str_replace("machine_program_", "", $key);
                    $programValue = trim($value);
                    
                    $copyDockProIM = $this->sqlc->insertDockProIM($barc4, $side, $programValue, $lineName);
                    if (!$copyDockProIM) {
                        $allOperationsSuccessful = false;
                        $this->sqlc->rollback();
                        echo "<script>alert('Error copying machine_program data');</script>";
                        return;
                    }
                }
            }
    
            // Copy notes
            // Get the ID_PARENT_NOTE values for the old barcode and side
            $oldNotes = $this->sqlc->notes($barc, $side);
            if ($oldNotes !== false && isset($oldNotes['ID_PARENT_NOTE'])) {
                $copyNotes = 0;
                foreach ($oldNotes['ID_PARENT_NOTE'] as $index => $idParentNote) {
                    // Get the IMG_NOTE for the old barcode and side
                    $imageComment = isset($oldNotes['IMG_NOTE'][$index]) ? $oldNotes['IMG_NOTE'][$index] : null;
                    
                    // Insert a new note with the new barcode, side, and the old ID_PARENT_NOTE
                    $copyNotes += $this->sqlc->insertNoteIM($barc4, $side, $imageComment, $idParentNote);
                }
    
                // Check if all notes were copied successfully
                if ($copyNotes != count($oldNotes['ID_PARENT_NOTE'])) {
                    $allOperationsSuccessful = false;
                    echo "<script>alert('Error copying notes');</script>";
                    $this->sqlc->rollback();
                    return;
                }
            }
    
            // Final check for all operations
            if ($allOperationsSuccessful) {
                $this->sqlc->commit();
                echo "<script>alert('Copying completed successfully!');</script>";
            } else {
                echo "<script>alert('Error during data copying');</script>";
            }
        }
    }

    /**
     * Adds a new notice to the system.
     * 
     * Validates and processes the addition of a new notice/comment
     * to the typical comments database.
     *
     * @return void
     */
    public function addNoticesAction() {
        $post = $_POST;

        if (!empty($post["addNotice"]) && $post["catNotice"] == 0) {
            echo "<script>alert('Proszę wybrać kategorię dla dodawanej uwagi.'); window.location.href = 'index.php?controller=administration&action=confNotices';</script>";
        } else {
            $addNewNote = $this->sqlc->addNewNote($post["catNotice"], $post["addNotice"]);
             if ($addNewNote) {
                echo "<script>alert('Moje gratulacje, dodałeś uwagę.'); window.location.href = 'index.php?controller=administration&action=confNotices&status=1';</script>";
            } else {
                echo "<script>alert('Wystąpił błąd podczas dodawania uwagi.'); window.location.href = 'index.php?controller=administration&action=confNotices';</script>";
            }
        }
    }

    /**
     * Deletes an existing notice from the system.
     * 
     * Processes the deletion of a notice/comment from the database
     * and redirects to the configuration page.
     *
     * @return void
     */
    public function deleteNoticesAction() {
        $post = $_POST;
        $deleteNote = $this->sqlc->deleteNotice($post["deleteNotice"]);
        $this->redirectPageTo("confNotices&status=2");
    }

    /**
     * Deletes an image associated with a note.
     * 
     * Removes the image from both the database and the FTP server.
     *
     * @return void
     */
    public function deleteImageFromNoticeAction() {
        $post = $_POST;
        $getNameNoteImage = $this->sqlc->getNameNoteImage($post['idRowNotice']);
        $deleteNameImageFromNote = $this->sqlc->deleteNameImageFromNote($post['idRowNotice']);
        if ($deleteNameImageFromNote == 1) {
            $ftpMover = new Helper_FtpMovesFiles($getNameNoteImage, null);
            $ftpDelete = $ftpMover->ftpDelete();
            if ($ftpDelete == 1) {
                echo 1;
            }
            $ftpMover->ftpClose();
        } else {
            echo 0;
        }
    }

    /**
     * Edits instruction data.
     * 
     * Handles the complete edit process for an instruction including
     * general information, print programs, notes, and machine programs.
     * Performs validation at each step.
     *
     * @return void
     */
    public function editAction() {
        $post = $_POST;
    
        // Get devices for multiselect (if necessary for UI)
        $this->pagec->devicesForMultiselect = Helper_AddFunct::devices();
    
        // Ensure the barc4 value exists in the POST request
        if (isset($post["barc4"])) {
            // Validate num_tiles_in_panel
            if (!isset($post["numTilesInPanel"]) || !is_numeric($post["numTilesInPanel"]) || $post["numTilesInPanel"] < 1) {
                echo "<script>alert('Ilość w panelu must be a valid number greater than 0');</script>";
                return;
            }
            
            // Save general information
            $saveGeneralInformation = $this->sqlc->saveGeneralInformation(
                $post["barc4"],
                $post["side"],
                $post["name"],
                $post["subgroup"],
                $post["tile"],
                intval($post["numTilesInPanel"]),
                $post["tileWidth"],
                $post["assembly_order"]
            );
    
            if ($saveGeneralInformation == 1) {
                // Delete old print programs
                $this->areaTablePrintProgramSmt(
                    "old_print_prog_", 
                    "deletePrintProgIM", 
                    $post["barc4"], 
                    $post["side"]
                );
    
                // Check if validation passed
                if (!in_array(0, $this->validationArr)) {
                    // Insert new print programs
                    $this->areaTablePrintProgramSmt(
                        "print_program_", 
                        "insertPrintProgIM", 
                        $post["barc4"], 
                        $post["side"]
                    );
    
                    // Check again after inserting print programs
                    if (!in_array(0, $this->validationArr)) {
                        // Save notes
                        $noteIndex = 0;
                        $saveNotes = 0;
                        foreach ($post["notes"] as $val) {
                            $saveNotes += $this->sqlc->saveNotes(
                                $post["id"][$noteIndex], 
                                $post["notes"][$noteIndex]
                            );
                            $noteIndex++;
                        }
    
                        // Check if all notes were saved
                        if ($saveNotes == count($post["notes"])) {
                            // Delete old machine programs
                            $this->areaTableSmt(
                                "old_mach_prog_", 
                                "deleteInsertDockProIM", 
                                $post["barc4"], 
                                $post["side"]
                            );
    
                            // Check after deleting/inserting machine programs
                            if (!in_array(0, $this->validationArr)) {
                                // Insert new machine programs
                                $this->areaTableSmt(
                                    "machine_program_", 
                                    "insertDockProIM", 
                                    $post["barc4"], 
                                    $post["side"]
                                );
    
                                // Final validation check before committing the transaction
                                if (!in_array(0, $this->validationArr)) {
                                    // All operations were successful, commit the transaction
                                    $this->commit();
                                    $this->resetDataTempOK();
                                    // $this->saveHistory(); // Save history after all data is committed
                                } else {
                                    // Validation failed after inserting machine programs
                                    echo "<p>Error: Validation failed after inserting machine program.</p>";
                                }
                            } else {
                                // Validation failed after deleting/inserting machine programs
                                echo "<p>Error: Validation failed after deleting/inserting dock program.</p>";
                            }
                        } else {
                            // Not all notes were saved correctly
                            echo "<p>Error: Not all notes were saved.</p>";
                        }
                    } else {
                        // Validation failed after inserting print programs
                        echo "<p>Error: Validation failed after inserting print program.</p>";
                    }
                } else {
                    // Validation failed after deleting old print programs
                    echo "<p>Error: Validation failed after deleting print program.</p>";
                }
            } else {
                // If required fields are missing or saveGeneralInformation failed
                echo "<script>alert('Required fields: Name, Quantity in panel, Subgroup, PCB number, PCB width, Assembly order');</script>";
            }
        } 
    }
    
    /**
     * Saves history record for tracking changes.
     * 
     * Creates a history record with details of all fields that have been
     * modified, including general information, programs, and notes.
     *
     * @return bool True if history was successfully saved, false otherwise
     */
    public function saveHistoryAction() {
        $post = $_POST;
        
        // Create data array for database record
        $data = [
            'BARC4' => isset($post['BARC4']) ? $post['BARC4'] : '',
            'SIDE' => isset($post['SIDE']) ? $post['SIDE'] : 0,
            'AUTHOR' => isset($post['AUTHOR']) ? $post['AUTHOR'] : 'Unknown',
            'COMMENTS' => isset($post['COMMENTS']) ? $post['COMMENTS'] : '',
            'LINE' => isset($post['LINE']) ? $post['LINE'] : null,
            'NAME_I' => isset($post['NAME_I']) ? $post['NAME_I'] : null,
            'WPN' => isset($post['WPN']) ? $post['WPN'] : null,
            'SUBGROUP' => isset($post['SUBGROUP']) ? $post['SUBGROUP'] : null,
            'TILE' => isset($post['TILE']) ? $post['TILE'] : null,
            'NUM_TILES_IN_PANEL' => isset($post['NUM_TILES_IN_PANEL']) ? $post['NUM_TILES_IN_PANEL'] : null,
            'WIDTH_TILES' => isset($post['WIDTH_TILES']) ? $post['WIDTH_TILES'] : null,
            'ASSEMBLY_ORDER' => isset($post['ASSEMBLY_ORDER']) ? $post['ASSEMBLY_ORDER'] : null,
            'PROGRAM_PRINT_PROG' => isset($post['PROGRAM_PRINT_PROG']) ? $post['PROGRAM_PRINT_PROG'] : null,
            
            // Explicitly check machine programs, using empty string instead of null
            'MACHINE_PROGRAM_1R' => isset($post['MACHINE_PROGRAM_1R']) ? $post['MACHINE_PROGRAM_1R'] : '',
            'MACHINE_PROGRAM_2R' => isset($post['MACHINE_PROGRAM_2R']) ? $post['MACHINE_PROGRAM_2R'] : '',
            'MACHINE_PROGRAM_3R' => isset($post['MACHINE_PROGRAM_3R']) ? $post['MACHINE_PROGRAM_3R'] : '',
            'MACHINE_PROGRAM_4R' => isset($post['MACHINE_PROGRAM_4R']) ? $post['MACHINE_PROGRAM_4R'] : '',
            'MACHINE_PROGRAM_1G' => isset($post['MACHINE_PROGRAM_1G']) ? $post['MACHINE_PROGRAM_1G'] : '',
            'MACHINE_PROGRAM_2G' => isset($post['MACHINE_PROGRAM_2G']) ? $post['MACHINE_PROGRAM_2G'] : '',
            'MACHINE_PROGRAM_3G' => isset($post['MACHINE_PROGRAM_3G']) ? $post['MACHINE_PROGRAM_3G'] : '',
            
            // Explicitly check note IDs
            'NOTES_ID_1' => isset($post['NOTES_ID_1']) && $post['NOTES_ID_1'] !== "" ? $post['NOTES_ID_1'] : null,
            'NOTES_ID_2' => isset($post['NOTES_ID_2']) && $post['NOTES_ID_2'] !== "" ? $post['NOTES_ID_2'] : null,
            'NOTES_ID_3' => isset($post['NOTES_ID_3']) && $post['NOTES_ID_3'] !== "" ? $post['NOTES_ID_3'] : null,
            'NOTES_ID_4' => isset($post['NOTES_ID_4']) && $post['NOTES_ID_4'] !== "" ? $post['NOTES_ID_4'] : null,
        ];
        
        // Save data to database
        $result = $this->sqlc->insertHistoryRecord($data);
        
        return $result;
    }



   
/**
 * @brief Deletes instruction data for a specified barcode and side.
 *
 * Performs a complete deletion of an instruction record including all related
 * data (general information, header, print programs, dock programs, and notes).
 * The deletion process is performed as a transaction - if any step fails, all
 * changes are rolled back. Records the deletion in the history table regardless
 * of success or failure.
 *
 * @param array $_POST Required parameters:
 *        - barc4: string - Barcode identifier to be deleted
 *        - side: int - Side identifier (1 or 2)
 *        - currentUserName: string (optional) - User's first name
 *        - currentUserLastName: string (optional) - User's last name
 * 
 * @return void Redirects to delete page with status:
 *         - status=1: Deletion successful
 *         - status=2: Instruction does not exist
 *         - status=3: Deletion failed (with details of which step failed)
 *
 * @see saveDeletionHistory() Records the deletion event
 * @see commit() Confirms the transaction
 * @see rollback() Cancels the transaction
 * @access public
 */
public function deleteAction() {
    $post = $_POST;
    if (isset($post["barc4"])) {
        $barc4 = $post["barc4"];
        $side = $post["side"];

        // Get user name and surname from session
        $userName = isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'System';
        $userLastName = isset($_SESSION['user']['lastname']) ? $_SESSION['user']['lastname'] : '';

        // Set variables in page context
        $this->pagec->userName = $userName;
        $this->pagec->userLastName = $userLastName;

        $author = trim($userName . ' ' . $userLastName);

        // Check if instruction exists
        $instructionExists = $this->sqlc->checkBarcodeInstruction($barc4, $side);

        if ($instructionExists) {
            // Create comment for history
            $comment = "Usunięto instrukcję dla BARC4: " . $barc4 . ", Strona: " . $side;

            $deleteGeneralInformation = $this->sqlc->deleteGeneralInformation($barc4, $side);
            if ($deleteGeneralInformation  > 0) {
                $deleteHeaderInformation = $this->sqlc->deleteHeaderInformation($barc4, $side);
                if ($deleteHeaderInformation  > 0) {
                    $deletePrintProg = $this->sqlc->deletePrintProg($barc4, $side);
                    if ($deletePrintProg  > 0) {
                        $deletDockProg = $this->sqlc->deletDockProg($barc4, $side);
                        if ($deletDockProg  > 0) {
                            $deletNotes = $this->sqlc->deletNotes($barc4, $side);
                            if ($deletNotes  > 0) {
                                $this->commit();
                                 // Save deletion history record
                                $this->saveDeletionHistory($barc4, $side, $comment);
                                $this->redirectPageTo("delete&status=1"); // Status 1 - deletion successful
                            } else {
                                $this->rollback();
                                 // Save deletion history record
                                $this->saveDeletionHistory($barc4, $side, $comment);
                                $this->redirectPageTo("delete&status=3"); // Status 3 - error deleting notes
                            }
                        } else {
                            $this->rollback();
                             // Save deletion history record
                            $this->saveDeletionHistory($barc4, $side, $comment);
                            $this->redirectPageTo("delete&status=3"); // Status 3 - error deleting dockProg
                        }
                    } else {
                        $this->rollback();
                         // Save deletion history record
                        $this->saveDeletionHistory($barc4, $side, $comment);
                        $this->redirectPageTo("delete&status=3"); // Status 3 - error deleting printProg
                    }
                } else {
                    $this->rollback();
                     // Save deletion history record
                    $this->saveDeletionHistory($barc4, $side, $comment);
                    $this->redirectPageTo("delete&status=3"); // Status 3 - error deleting headerInformation
                }
            } else {
                $this->rollback();
                 // Save deletion history record
                $this->saveDeletionHistory($barc4, $side, $comment);
                $this->redirectPageTo("delete&status=3"); // Status 3 - error deleting generalInformation
            }
        } else {
            // Instruction does not exist
            $this->redirectPageTo("delete&status=2"); // Status 2 - instruction does not exist
        }
     }
}

/**
 * @brief Records a history entry for instruction deletion.
 *
 * Creates a history record with information about the deleted instruction.
 * Only basic information is stored (barcode, side, author, and comment),
 * while all other fields are set to null since the detailed data has been
 * removed from the database.
 *
 * @param string $barc4 Barcode identifier of the deleted instruction
 * @param int $side Side identifier (1 or 2) of the deleted instruction
 * @param string $comment Descriptive comment about the deletion action
 * 
 * @return void
 * 
 * @see insertHistoryRecord() Inserts the history data into database
 * @access private
 */
private function saveDeletionHistory($barc4, $side, $comment) {
    // Get user name and surname from POST request
    $userName = isset($_POST['currentUserName']) ? $_POST['currentUserName'] : 'System';
    $userLastName = isset($_POST['currentUserLastName']) ? $_POST['currentUserLastName'] : '';
    
    // Form full user name
    $author = trim($userName . ' ' . $userLastName);
    
    // If name is still empty, use fallback
    if (trim($author) == '') {
        $author = "System";
    }
    
    $data = [
        'BARC4' => $barc4,
        'SIDE' => $side,
        'AUTHOR' => $author,
        'COMMENTS' => $comment,
        'LINE' => null,
        'NAME_I' => null,
        'WPN' => null,
        'SUBGROUP' => null,
        'TILE' => null,
        'NUM_TILES_IN_PANEL' => null,
        'WIDTH_TILES' => null,
        'ASSEMBLY_ORDER' => null,
        'PROGRAM_PRINT_PROG' => null,
        'MACHINE_PROGRAM_1R' => null,
        'MACHINE_PROGRAM_2R' => null,
        'MACHINE_PROGRAM_3R' => null,
        'MACHINE_PROGRAM_4R' => null,
        'MACHINE_PROGRAM_1G' => null,
        'MACHINE_PROGRAM_2G' => null,
        'MACHINE_PROGRAM_3G' => null,
        'NOTES_ID_1' => null,
        'NOTES_ID_2' => null,
        'NOTES_ID_3' => null,
        'NOTES_ID_4' => null,
    ];

    $this->sqlc->insertHistoryRecord($data);
}

/**
 * @brief Uploads an image file to the FTP server and updates the database.
 *
 * Handles the upload of image files associated with notes in the BIM system.
 * The method processes the uploaded file, generates a unique filename based on
 * barcode, drive number, side and row identifier, moves the file to the FTP
 * server, and updates the database record with the new image name.
 *
 * @param array $_POST Required parameters:
 *        - barc4: string - Barcode identifier
 *        - wpn: string - Drive number
 *        - side: int - Side identifier (1 or 2)
 *        - l: string - Row identifier
 *        - id_row: int - Database record ID to update
 * @param array $_FILES['file'] The uploaded file data
 * 
 * @return void Outputs "1" on success, nothing on failure
 * 
 * @see Helper_FtpMovesFiles For FTP operations
 * @access public
 */
public function uploadFileOnServerAction() {
    $barc4 = $_POST["barc4"];
    $driveNumber = $_POST["wpn"];
    $side = $_POST["side"];
    $l = $_POST["l"];
    $id_row = $_POST["id_row"];
    $target_file = basename($_FILES["file"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $imgName = "$barc4" . "_" . "$driveNumber" . "_" . $side . "_" . $l . "." . "$imageFileType";
    $ftpMover = new Helper_FtpMovesFiles($imgName, $_FILES["file"]["tmp_name"]);
    $updateImageInNotes = $this->sqlc->updateImageInNotes($id_row, $imgName);
    $ftpMover->ftpPut();

    if ($ftpMover->ftpPut() == 1 && $updateImageInNotes == 1) {
        echo 1;
    }
    $ftpMover->ftpClose();
}

/**
 * @brief Finds barcodes associated with a specified WPN (drive number).
 *
 * Searches the database for barcodes that match the given drive number
 * and prepares them for display in the template. Removes whitespace from 
 * the input to ensure consistent matching.
 *
 * @param array $_POST Required parameters:
 *        - driveNumber: string - WPN/drive number to search for
 * 
 * @return void Sets $this->pagec->barcs4 with found barcodes
 * 
 * @see Helper_AddFunct::removeWhitespace() For input sanitization
 * @access public
 */
public function findBarcViaWpnAction() {
    $driveNumber = Helper_AddFunct::removeWhitespace($_POST["driveNumber"]);
    $checkBarcodeForWpn = $this->sqlc->checkBarcodeForWpn($driveNumber);
    $barc4 = array();
    for ($j = 0; $j < count($checkBarcodeForWpn); $j++) {
        array_push($barc4, $checkBarcodeForWpn[$j]);
    }
    $this->pagec->barcs4 = $barc4;
}

/**
 * @brief Processes machine program assignments for selected lines.
 *
 * Iterates through POST data to find machine program assignments 
 * that match the specified separator pattern. For each matching entry,
 * it extracts the line identifier, sanitizes the program value,
 * and calls the specified SQL method to perform database operations.
 * Records validation results for each operation.
 *
 * @param string $seperate Separator string to identify relevant POST fields
 * @param string $sqlName Name of the SQL method to call for processing
 * @param string $barc4 Barcode identifier
 * @param int $side Side identifier (1 or 2)
 * 
 * @return void Updates $this->validationArr with results
 * 
 * @see Helper_AddFunct::removeWhitespace() For input sanitization
 * @access private
 */
private function areaTableSmt($seperate, $sqlName, $barc4, $side) {
    $n = 0;
    $insertSql = 0;
    foreach ($_POST as $key => $program) {
        if (strstr($key, $seperate)) {
            $program = Helper_AddFunct::removeWhitespace($program);
            $line = explode($seperate, $key);

            // Perform operation: insert, update or delete
            $result = $this->sqlc->$sqlName($barc4, $side, $program, $line[1]);

            if ($result === false) {
                array_push($this->validationArr, "0");
            } else {
                array_push($this->validationArr, "1");
            }
        }
    }
}

/**
 * @brief Processes print program assignments for selected lines.
 *
 * Similar to areaTableSmt but specifically handles print programs
 * with special logic for checkbox handling. It distinguishes between
 * checkbox fields (value='on') and regular input fields, and applies
 * the appropriate program value based on the field type. Only 
 * processes non-empty program values.
 *
 * @param string $seperate Separator string to identify relevant POST fields
 * @param string $sqlName Name of the SQL method to call for processing
 * @param string $barc4 Barcode identifier
 * @param int $side Side identifier (1 or 2)
 * 
 * @return void Updates $this->validationArr with results
 * 
 * @see Helper_AddFunct::removeWhitespace() For input sanitization
 * @access private
 */
private function areaTablePrintProgramSmt($seperate, $sqlName, $barc4, $side) {
    $n = 0;
    $insertSql = 0;
    foreach ($_POST as $key => $value) {
        if (strstr($key, $seperate)) {
            // Check if this is a checkbox or input field
            if ($value === 'on') {
                // If checkbox, use value from the main print_prog field
                $program = Helper_AddFunct::removeWhitespace($_POST["print_prog"]);
            } else {
                // If input field, use its own value
                $program = Helper_AddFunct::removeWhitespace($value);
            }
            
            if ($program != '') {
                $n++;
                $line = explode($seperate, $key);
                $insertSql += $this->sqlc->$sqlName($barc4, $side, $program, $line[1]);
                $n == $insertSql ? array_push($this->validationArr, "1") : array_push($this->validationArr, "0");
            }
        }
    }
}

/**
 * @brief Records history for new instruction creation.
 *
 * Creates a historical record when a new instruction is created in the system.
 * Collects all relevant information from the provided POST data and user information
 * to build a comprehensive history record. Note IDs are set to null as they are 
 * assigned separately.
 *
 * @param string $barc4 Barcode identifier of the new instruction
 * @param int $side Side identifier (1 or 2) of the new instruction
 * @param array $post POST data containing instruction details
 * @param string $userName User's first name who created the instruction
 * @param string $userLastName User's last name who created the instruction
 * 
 * @return bool|int False on failure, number of affected rows on success
 * 
 * @throws Exception Logs errors but does not throw exceptions to caller
 * @access private
 */
private function saveCreationHistory($barc4, $side, $post, $userName, $userLastName) {
    try {
        // Get user name from parameters
        $author = trim($userName . ' ' . $userLastName);
        
        // Create simple comment about instruction creation
        $comment = "Utworzono nową instrukcję dla BARC4: " . $barc4 . ", Strona: " . $side;
        
        // Collect data for history record
        $data = [
            'BARC4' => $barc4,
            'SIDE' => $side,
            'AUTHOR' => $author,
            'COMMENTS' => $comment,
            'LINE' => isset($this->pagec->devices) ? implode(',', $this->pagec->devices) : '',
            'NAME_I' => isset($post["name"]) ? $post["name"] : '',
            'WPN' => isset($post["wpn"]) ? $post["wpn"] : '',
            'SUBGROUP' => isset($post["subgroup"]) ? $post["subgroup"] : '',
            'TILE' => isset($post["tile"]) ? $post["tile"] : '',
            'NUM_TILES_IN_PANEL' => isset($post["numberTilesPanel"]) ? $post["numberTilesPanel"] : '',
            'WIDTH_TILES' => isset($post["tileWidth"]) ? $post["tileWidth"] : '',
            'ASSEMBLY_ORDER' => isset($post["assemblyOrder"]) ? $post["assemblyOrder"] : '',
            'PROGRAM_PRINT_PROG' => isset($post["print_prog"]) ? $post["print_prog"] : '',
            'MACHINE_PROGRAM_1R' => isset($post["machine_program_1r"]) ? $post["machine_program_1r"] : '',
            'MACHINE_PROGRAM_2R' => isset($post["machine_program_2r"]) ? $post["machine_program_2r"] : '',
            'MACHINE_PROGRAM_3R' => isset($post["machine_program_3r"]) ? $post["machine_program_3r"] : '',
            'MACHINE_PROGRAM_4R' => isset($post["machine_program_4r"]) ? $post["machine_program_4r"] : '',
            'MACHINE_PROGRAM_1G' => isset($post["machine_program_1g"]) ? $post["machine_program_1g"] : '',
            'MACHINE_PROGRAM_2G' => isset($post["machine_program_2g"]) ? $post["machine_program_2g"] : '',
            'MACHINE_PROGRAM_3G' => isset($post["machine_program_3g"]) ? $post["machine_program_3g"] : '',
            'NOTES_ID_1' => null,
            'NOTES_ID_2' => null,
            'NOTES_ID_3' => null,
            'NOTES_ID_4' => null
        ];
        
        // Write history to database
        return $this->sqlc->insertHistoryRecord($data);
    } catch (Exception $e) {
        error_log("Error in saveCreationHistory: " . $e->getMessage());
        return false;
    }
}
    
/**
 * @brief Processes and saves notes associated with an instruction.
 *
 * Handles the processing and storage of notes attached to a barcode instruction.
 * For each note, the method:
 * 1. Extracts note content and any associated image
 * 2. Processes image filenames for consistent naming
 * 3. Saves notes to the database
 * 4. If successful, uploads any associated images to FTP server
 * 5. Records the action in history
 * 6. Redirects to appropriate page based on success/failure
 *
 * @param string $barc4 Barcode identifier
 * @param string $driveNumber Drive/WPN number associated with the instruction
 * @param int $side Side identifier (1 or 2) of the PCB
 * 
 * @return void Redirects to another page after processing
 * 
 * @see saveCreationHistory() Records history of the note creation
 * @see commit() Confirms database changes on success
 * @see rollback() Cancels database changes on failure
 * @access private
 */
private function prepareNoteToSave($barc4, $driveNumber, $side) {
    if (isset($_POST["note"])) {
        $n = 0;
        $insertNoteIM = 0;
        for ($k = 0; $k < count($_POST["note"]); $k++ ) {
            $note = $_POST["note"][$k];
            $imageComment = $_FILES["imageComment"]["name"][$k];
            if (!empty($imageComment)) {
                $target_file = basename($_FILES["imageComment"]["name"][$k]);
                $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                $imageComment = "$barc4" . "_" . "$driveNumber" . "_" . $side . "_" . $k . "." . "$imageFileType";
            }
            $n++;
            $insertNoteIM  += $this->sqlc->insertNoteIM($barc4, $side, $imageComment, $note);
            $n == $insertNoteIM ? array_push($this->validationArr, "1") : array_push($this->validationArr, "0");
        }

        if (!in_array(0, $this->validationArr)) {
            for ($l = 0; $l < count($_POST["note"]); $l++) {
                if (!empty($_FILES["imageComment"]["name"][$l])) {
                    $target_file = basename($_FILES["imageComment"]["name"][$l]);
                    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                    $imgName = "$barc4" . "_" . "$driveNumber" . "_" . $side . "_" . $l . "." . "$imageFileType";
                    $ftpMover = new Helper_FtpMovesFiles($imgName, $_FILES["imageComment"]["tmp_name"][$l]);
                    $ftpMover->ftpPut();
                    $ftpMover->ftpClose();
                }
            }
            
            try {
                // Save history record before commit
                $this->saveCreationHistory($barc4, $side, $_POST, $this->pagec->userName, $this->pagec->userLastName);
            } catch (Exception $e) {
                // Continue execution even if history saving fails
                error_log("Failed to save history: " . $e->getMessage());
            }
            
            array_push($this->savedBarcodes, $barc4);
            $this->commit();
            $this->resetDataTempOK();
            $this->resetFormPrepareSession();
            $this->redirectPageTo("index");
        } else {
            array_push($this->noSavedBarcodes, $barc4);
            $this->rollback();
            $this->resetDataTempNOOK();
            $this->resetFormPrepareSession();
            $this->redirectPageTo();
        }
    }
}

/**
 * @brief Processes barcode instructions based on WPN/drive numbers from POST data.
 *
 * Method that runs after page load to automatically process drive numbers provided 
 * in POST data. For each drive number:
 * 1. Looks up associated barcodes
 * 2. Creates header information for each barcode
 * 3. Creates general information, print programs, and machine programs
 * 4. Processes and saves notes
 * 5. Handles transaction commits or rollbacks based on success
 * 
 * Main entry point for bulk instruction creation based on drive numbers.
 *
 * @return void
 * 
 * @see prepareNoteToSave() Handles note saving for each barcode
 * @see areaTablePrintProgramSmt() Processes print program assignments
 * @see areaTableSmt() Processes machine program assignments
 * @access private
 */
private function findBarcViaWpnAfterLoad() {
    if (isset($_POST["driveNumber"])) {
        $_SESSION["error"] = null;
        $_SESSION["savedBarcodes"] = null;
        $_SESSION["noSavedBarcodes"] = null;
        $dataHeader = $_SESSION["FormPrepare"];
        $date = date('Y/m/d h:i:s', strtotime($dataHeader["date"]));
        $post = $_POST;

        for ($i = 0; $i < count($_POST["driveNumber"]); $i++ ) {
            $driveNumber = Helper_AddFunct::removeWhitespace($_POST["driveNumber"][$i]);
            $barc4 = $this->sqlc->checkBarcodeForWpn($driveNumber);
            for ($j = 0; $j < count($barc4); $j++) {
                $insertHeaderIM = $this->sqlc->insertHeaderIM($barc4[$j], $dataHeader["side"], $dataHeader["documentNr"], $date, $dataHeader["author"]);
                if ($insertHeaderIM) {
                    $insertGenInformIM = $this->sqlc->insertGenInformIM($barc4[$j], $dataHeader["side"], $post["name"], $post["subgroup"], $post["tile"], $post["numberTilesPanel"], $post["tileWidth"], $post["assemblyOrder"]);
                    if ($insertGenInformIM) {
                        // Handle print programs
                        $this->areaTablePrintProgramSmt("print_program_", "insertPrintProgIM", $barc4[$j], $dataHeader["side"]);

                        // Handle machine programs
                        $this->areaTableSmt("machine_program_", "insertDockProIM", $barc4[$j], $dataHeader["side"]);

                        // Handle notes
                        $this->prepareNoteToSave($barc4[$j], $driveNumber, $dataHeader["side"]);
                    } else {
                        $this->rollback();
                        $this->resetDataTempNOOK();
                        $this->resetFormPrepareSession();
                        $this->redirectPageTo();
                    }
                } else {
                    $this->rollback();
                    $this->resetDataTempNOOK();
                    $this->resetFormPrepareSession();
                    $this->redirectPageTo();
                }
            }
        }
    }
}

/**
 * @brief Stores form data in session for later processing.
 *
 * Action method that captures form data from a POST request and stores it
 * in the session under the "FormPrepare" key. After storing, redirects to
 * the index page where the data can be processed.
 *
 * @param array $_POST Form data to be stored
 *
 * @return void Redirects to index page
 *
 * @see findBarcViaWpnAfterLoad() Uses the stored session data for processing
 * @access public
 */
public function prepareAction() {
    $_SESSION["FormPrepare"] = $_POST;
    header("Location: index.php?controller=administration&action=index");
}

/**
 * @brief Redirects user to a specific page within the administration controller.
 *
 * Helper method to handle common redirection needs within the application.
 * Generates a proper URL and redirects the browser to the specified action.
 *
 * @param string $redirect The action name to redirect to (defaults to "index")
 * 
 * @return void
 * 
 * @access public
 */
public function redirectPageTo($redirect = "index") {
    header("Location: index.php?controller=administration&action=$redirect");
}

/**
 * @brief Resets the form preparation session data.
 *
 * Clears the "FormPrepare" key from the session, effectively removing
 * any stored form data. Used when processing is complete or needs to be
 * aborted.
 *
 * @return void
 * 
 * @access public
 */
public function resetFormPrepareSession() {
    $_SESSION["FormPrepare"] = null;
}

/**
 * @brief Commits the current database transaction.
 *
 * Wrapper method for the SQL connector's commit function. Finalizes all
 * pending database changes, making them permanent.
 *
 * @return void
 * 
 * @see rollback() Counterpart method for canceling transactions
 * @access private
 */
private function commit() {
    $this->sqlc->commit();
}

/**
 * @brief Resets temporary data after successful operations.
 *
 * Clears validation array, sets error session to 0 (success), and stores
 * the list of successfully saved barcodes in session for display to the user.
 *
 * @return void
 * 
 * @see resetDataTempNOOK() Counterpart method for handling failure cases
 * @access private
 */
private function resetDataTempOK() {
    $this->validationArr = [];
    $_SESSION["error"] = 0;
    $_SESSION["savedBarcodes"] = implode(",", $this->savedBarcodes);
}

/**
 * @brief Resets session data and redirects to a specified page.
 *
 * Action method that clears form preparation session data and redirects
 * the user to another page (defaults to index).
 *
 * @param string $redirect The action to redirect to (defaults to "index")
 * 
 * @return void Redirects to the specified page
 * 
 * @see resetFormPrepareSession() Used to clear session data
 * @see redirectPageTo() Used to handle the redirect
 * @access public
 */
public function resetSessionAction($redirect = "index") {
    $_SESSION["FormPrepare"] = null;
    $this->resetFormPrepareSession();
    $this->redirectPageTo("index");
}

/**
 * @brief Rolls back the current database transaction.
 *
 * Wrapper method for the SQL connector's rollback function. Cancels all
 * pending database changes, reverting to the pre-transaction state.
 * Used when errors are detected during processing.
 *
 * @return void
 * 
 * @see commit() Counterpart method for finalizing transactions
 * @access private
 */
private function rollback() {
    $this->sqlc->rollback();
}

/**
 * @brief Resets temporary data after failed operations.
 *
 * Clears validation array, sets error session to 1 (failure), and stores
 * the list of barcodes that couldn't be saved in session for display to the user.
 *
 * @return void
 * 
 * @see resetDataTempOK() Counterpart method for handling success cases
 * @access private
 */
private function resetDataTempNOOK() {
    $this->validationArr = [];
    $_SESSION["error"] = 1;
    $_SESSION["noSavedBarcodes"] = implode(",", $this->noSavedBarcodes);
}

/**
 * @brief Handles deletion of a note via AJAX request.
 *
 * Action method that receives note ID and side from POST data,
 * validates the input, attempts to delete the note from the database,
 * and returns JSON response indicating success or failure.
 * 
 * @param array $_POST Required parameters:
 *        - note_id: int - ID of the note to delete
 *        - side: int - Side identifier (1 or 2)
 *        - barc4: string (optional) - Barcode identifier for logging
 * 
 * @return void Outputs JSON response with success status
 * 
 * @throws Exception Catches and logs exceptions without propagating them
 * @access public
 */
public function deleteNoteAction() {
    try {
        // Validate required parameters
        if (!isset($_POST['note_id']) || !isset($_POST['side'])) {
            error_log("Missing required parameters for deleteNote: note_id or side");
            echo json_encode(['success' => false, 'error' => 'Missing parameters']);
            return;
        }

        $noteId = intval($_POST['note_id']);
        $side = $_POST['side'];
        
        // Optional parameter, can be used for logging
        $barc4 = isset($_POST['barc4']) ? $_POST['barc4'] : '';
        
        // Log the deletion attempt
        error_log("Attempting to delete note ID: $noteId, Side: $side, BARC4: $barc4");
        
        // Delete the note
        $result = $this->sqlc->deleteNote($noteId, $side);
        
        // Log the result
        error_log("Delete note result: " . ($result ? "Success" : "Failed"));
        
        // Return result to client
        echo json_encode(['success' => $result]);
        exit;
    } catch (Exception $e) {
        error_log("Exception in deleteNoteAction: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Server exception']);
        exit;
    }
}

/**
 * @brief Handles creation of a new note via AJAX request.
 *
 * Action method that receives barcode, side, and parent note ID from POST data,
 * validates the input, creates a new note in the database, and returns JSON
 * response with the new note ID or error information.
 *
 * @param array $_POST Required parameters:
 *        - barc4: string - Barcode identifier
 *        - side: int - Side identifier (1 or 2)
 *        - parent_note_id: int - ID of the parent typical note
 * 
 * @return void Outputs JSON response with success status and new note ID
 * 
 * @throws Exception Catches and logs exceptions without propagating them
 * @access public
 */
public function addNoteAction() {
    try {
        // Parameter validation
        if (!isset($_POST['barc4']) || !isset($_POST['side']) || !isset($_POST['parent_note_id'])) {
            error_log("Missing required parameters for addNote");
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            return;
        }

        $barc4 = $_POST['barc4'];
        $side = $_POST['side'];
        $parentNoteId = $_POST['parent_note_id'];
        
        // Log request
        error_log("Attempting to add note: BARC4=$barc4, Side=$side, Parent_Note_ID=$parentNoteId");
        
        // Perform insertion and get ID
        $result = $this->sqlc->addNote($barc4, $side, $parentNoteId);
        
        // Check result and respond to client
        if ($result !== false) {
            error_log("Successfully added note with ID: $result");
            echo json_encode(['success' => true, 'id' => $result]);
        } else {
            error_log("Failed to add note");
            echo json_encode(['success' => false, 'error' => 'Failed to add note']);
        }
        exit;
    } catch (Exception $e) {
        error_log("Exception in addNoteAction: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}

/**
 * @brief Saves multiple notes via AJAX request.
 *
 * Action method that receives a JSON array of notes from POST data,
 * decodes it, and passes it to the SQL connector for batch saving.
 * Returns JSON response indicating success or failure.
 *
 * @param array $_POST Required parameters:
 *        - notes: string - JSON encoded array of note objects to save
 * 
 * @return void Outputs JSON response with success status
 * 
 * @access public
 */
public function saveNotesAction() {
    if (!isset($_POST['notes'])) {
        echo json_encode(['success' => false]);
        return;
    }

    $notes = json_decode($_POST['notes'], true);
    $result = $this->sqlc->saveNotes($notes);
    
    echo json_encode(['success' => $result]);
    exit;
}

/**
 * @brief Checks if an instruction exists for a given barcode and side.
 *
 * Action method used via AJAX to determine if an instruction already exists.
 * Removes whitespace from the barcode input for consistent matching,
 * checks the database, and returns a JSON response with the result and
 * an appropriate message for the user.
 *
 * @param array $_POST Required parameters:
 *        - barc4: string - Barcode identifier to check
 *        - side: int - Side identifier (1 or 2)
 * 
 * @return void Outputs JSON response with existence status and message
 * 
 * @see Helper_AddFunct::removeWhitespace() For input sanitization
 * @access public
 */
public function checkInstructionAction() {
    $barc4 = Helper_AddFunct::removeWhitespace($_POST["barc4"]);
    $side = $_POST["side"];
    
    $exists = $this->sqlc->checkBarcodeInstruction($barc4, $side);
    
    header('Content-Type: application/json');
    echo json_encode([
        'exists' => $exists,
        'message' => $exists 
            ? 'Instrukcja dla tego produktu już istnieje w bazie danych, użyj zakładki do edycji'
            : 'Nie ma jeszcze instrukcji dla tego produktu'
    ]);
    exit;
}
}