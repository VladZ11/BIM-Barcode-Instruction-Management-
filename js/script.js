/**
 * @class Bim
 * @description Main constructor for the BIM (Barcode Instruction Management) application.
 * Manages barcode processing, form handling, and UI interactions for instruction management.
 * @constructor
 */
function Bim() {
    /** @private Reference to the current object instance for use within nested functions */
    var _self = this;
    
    /** @private {HTMLElement|null} Reference to the currently active barcode input field */
    var barc4Current = null,
        /** @private {string} ID of the main barcode suggestions dropdown */
        autougesterNameId = "autougester",
        /** @private {string} ID of the secondary barcode suggestions dropdown */
        autougester2NameId = "autougester2",
        /** @private {jQuery} jQuery reference to the main barcode input field */
        barc4 = $("#barc4"),
        /** @private {jQuery} jQuery reference to the new barcode input field */
        newbarc4 = $("#newbarc4"),
        /** @private {jQuery} jQuery reference to the side selector */
        side = $("#side"),
        /** @private {jQuery} jQuery reference to the devices selector */
        devices = $("#devices"),
        /** @private {jQuery|null} Reference to the currently active drive number field */
        driveNumber = null,
        /** @private {string|null} ID of the currently active notice row */
        idRowNotice = null,
        /** @private {Object|null} Reference to the currently chosen component */
        chosenComponent = null,
        /** @private {jQuery} jQuery collection of all drive number input fields */
        driveNumberFields = $("input[name='driveNumber[]']");

    /** @private {string} Current user's first name from hidden field */
    var userName = $("#userName").val();
    /** @private {string} Current user's last name from hidden field */
    var userLastName = $("#userLastName").val();

    /** @private {string} HTML template for notes dropdown options */
    var notesOptionsTemplate = '';
    
    /**
     * @function initializeNotesOptions
     * @memberof Bim
     * @description Initializes the template for notes options by extracting HTML 
     * from the first notes dropdown. This template is used when creating new notes dynamically.
     * @private
     */
    function initializeNotesOptions() {
        var firstSelect = $("#notes-list select[name='notes[]']").first();
        if (firstSelect.length) {
            notesOptionsTemplate = firstSelect.html();
        }
    }
    
    // Call initialization on document ready
    $(document).ready(initializeNotesOptions);
    
    /**
     * @function loadDataForm
     * @memberof Bim
     * @description Function that also initializes note options after form content is loaded via AJAX.
     * @param {string} response - HTML content to be loaded into the form
     * @private
     */
    var originalLoadDataForm = loadDataForm;
    function loadDataForm(response) {
        originalLoadDataForm(response);
        initializeNotesOptions();
    }

    /**
 * @function getNoteTemplate
 * @memberof Bim
 * @description Generates HTML template for a new note item based on stored options.
 * Used when dynamically adding new notes to the list.
 * @param {number|string} index - The index/identifier for the new note
 * @returns {string} HTML template string for the note list item
 * @private
 */
function getNoteTemplate(index) {
    // If template is empty, try to get it again
    if (!notesOptionsTemplate) {
        initializeNotesOptions();
    }
    
    return `
        <li class="margin-bottom-15" data-note-index="${index}">
            <div>
                <select class="form-control" name="notes[]" data-history="0">
                    ${notesOptionsTemplate}
                </select>
                <input id="id_row_${index}" type="hidden" name="id[]" value="">
                <button type="button" class="btn btn-xs btn-danger btn-remove-note" data-note-index="${index}">-</button>
            </div>
            <div class="position-relative js-image-new">
                <label for="imageNotice_${index}">Załaduj plik:</label>
                <input type="file" id="imageNotice_${index}" name="imageNotice">
            </div>
        </li>
    `;
}

/**
 * @event btn-remove-note.click
 * @memberof Bim
 * @description Handler for note deletion. Removes notes from the DOM and database.
 * Records changes in history when in edit mode.
 * @private
 */
$("#searchedData").on("click", ".btn-remove-note", function(e) {
    e.preventDefault();
    var noteElement = $(this).closest("li");
    var noteId = noteElement.find("input[name='id[]']").val();
    var side = $("#side").val();
    var barc4 = $("#barc4").val();
    
    // Check if it's the last note
    if ($("#notes-list li").length <= 1) {
        swal("Ostrzeżenie!", "Nie można usunąć ostatniej uwagi. Każda instrukcja musi zawierać przynajmniej jedną uwagę.", "warning");
        return;
    }

    if (confirm("Czy na pewno chcesz usunąć tę uwagę? Ta akcja spowoduje natychmiastowe usunięcie z bazy danych!")) {
        if (noteId) {
            // Add visual indicator that deletion is in progress
            noteElement.addClass("deleting");
            
            $.ajax({
                url: 'index.php?controller=administration&action=deleteNote',
                method: 'POST',
                data: {
                    note_id: noteId,
                    side: side,
                    barc4: barc4
                },
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        // Successfully deleted from database
                        noteElement.fadeOut(300, function() {
                            $(this).remove();
                            reindexNotes();
                            
                            // Record in history after note deletion (only in edit mode)
                            if (window.location.href.indexOf("action=edit") > -1) {
                                saveEditHistory(barc4, side);
                            }
                        });
                    } else {
                        // Server returned error
                        noteElement.removeClass("deleting");
                        console.error("Błąd zwrócony przez serwer:", response);
                        alert('Błąd usuwania uwagi: Serwer zwrócił błąd');
                    }
                },
                error: function(xhr, status, error) {
                    // AJAX request failed
                    noteElement.removeClass("deleting");
                    console.error("Błąd AJAX:", status, error);
                    console.log("Odpowiedź:", xhr.responseText);
                    alert('Błąd komunikacji z serwerem');
                }
            });
        } else {
            // Local-only note (not saved to database yet)
            noteElement.fadeOut(300, function() {
                $(this).remove();
                reindexNotes();
            });
        }
    }
});

/**
 * @function reindexNotes
 * @memberof Bim
 * @description Updates note indices after note addition or removal.
 * Updates data attributes and IDs for all note elements in the list.
 * @private
 */
function reindexNotes() {
    $("#notes-list li").each(function(index) {
        var note = $(this);
        note.attr("data-note-index", index);
        note.find(".btn-remove-note").attr("data-note-index", index);
        note.find("input[id^='id_row_']").attr("id", `id_row_${index}`);
        note.find("input[id^='imageNotice_']").attr("id", `imageNotice_${index}`);
        note.find("label[for^='imageNotice_']").attr("for", `imageNotice_${index}`);
    });
}

/**
 * @function logMessage
 * @memberof Bim
 * @description Logs messages to browser console.
 * Used for debugging and monitoring application flow.
 * @param {string} message - The message to log
 * @private
 */
function logMessage(message) {
    console.log(message);
}

/**
 * @function ajax
 * @memberof Bim
 * @description Simplified wrapper for jQuery AJAX requests with logging.
 * Handles standard AJAX operations with callbacks and error handling.
 * @param {string} urlAjax - The URL to send the request to
 * @param {Object|string} dataAjax - The data to send with the request
 * @param {Function|null} beforeAjax - Function to execute before sending request
 * @param {Function|null} successAjax - Function to execute on successful response
 * @param {Function|null} completeAjax - Function to execute when request completes
 * @param {string} dataTypeValue - Expected data type of response
 * @private
 */
function ajax(urlAjax, dataAjax, beforeAjax, successAjax, completeAjax, dataTypeValue) {
    logMessage("Wysłanie żądania AJAX do URL: " + urlAjax);
    logMessage("Dane do wysłania: " + JSON.stringify(dataAjax));

    $.ajax({
        type: 'POST',
        url: urlAjax,
        data: dataAjax,
        dataType: dataTypeValue,
        beforeSend: function () {
            if (beforeAjax) beforeAjax();
        },
        success: function (response) {
            logMessage("Odpowiedź sukcesu: " + response);
            if (successAjax) successAjax(response);
        },
        complete: function () {
            logMessage("Żądanie zakończone.");
            if (completeAjax) completeAjax();
        },
        error: function (xhr, status, error) {
            logMessage("Błąd żądania AJAX: " + status + " - " + error);
        }
    });
}

/**
 * @function resetFields
 * @memberof Bim
 * @description Resets all input fields in the search data area.
 * Clears all form input values.
 * @private
 */
function resetFields() {
    $("#searchedData input").each(function () {
        $(this).val("");
    });
}

/**
 * @function findBarcodeSuccess
 * @memberof Bim
 * @description Handles successful barcode search response.
 * Updates the suggestion dropdown with search results.
 * @param {string} response - HTML content containing search results
 * @private
 */
function findBarcodeSuccess(response) {
    logMessage("Kod kreskowy znaleziony pomyślnie: " + response);
    var autougester = $("#" + autougesterNameId);
    autougester.removeClass("hide");
    autougester.html(response);
}

/**
 * @function findBarcodeComplete
 * @memberof Bim
 * @description Handles completion of barcode search request.
 * Automatically selects single result if it matches the current input.
 * @private
 */
function findBarcodeComplete() {
    var autougester = $("#" + autougesterNameId);
    if (autougester.find("li").length == 1) {
        if (barc4Current.val() == autougester.find("li:first").data("barcode")) {
            autougester.addClass("hide");
            getDataToInputs();
        }
    }
}

/**
 * @function setController
 * @memberof Bim
 * @description Determines the controller name based on current context.
 * @returns {string} Controller name to use for AJAX requests
 * @private
 */
function setController() {
    return controller !== "administration" ? "index" : "administration";
}

/**
 * @event barc4.keyup newbarc4.keyup
 * @memberof Bim
 * @description Handles keyup events on barcode input fields.
 * Triggers barcode search and displays suggestions as user types.
 * @private
 */
barc4.add(newbarc4).on("keyup", function () {
    barc4Current = $(this);
    var controller = setController();
    var autougesterName = $(this).attr('id') === 'barc4' ? autougesterNameId : autougester2NameId;
    
    ajax(
        "index.php?controller=" + controller + "&action=findBarcode",
        "barc4=" + barc4Current.val(),
        null,
        function(response) {
            var autougester = $("#" + autougesterName);
            autougester.removeClass("hide");
            autougester.html(response);
        },
        function() {
            var autougester = $("#" + autougesterName);
            if (autougester.find("li").length == 1) {
                if (barc4Current.val() == autougester.find("li:first").data("barcode")) {
                    autougester.addClass("hide");
                    if (barc4Current.attr('id') === 'barc4') {
                        getDataToInputs(1);
                    }
                }
            }
        },
        "html"
    );
});

/**
 * @event barcode-suggestions.click
 * @memberof Bim
 * @description Handles clicks on barcode suggestion items.
 * Sets the selected barcode value and triggers data loading if appropriate.
 * @private
 */
$(".barcode-information, .new-barcode").on("click", "li", function () {
    var parent = $(this).closest('div');
    var input = parent.hasClass('barcode-information') ? barc4 : newbarc4;
    var autougester = parent.find("[id^='autougester']");
    
    autougester.addClass("hide");
    input.val($(this).data("barcode"));
    
    if (input.attr('id') === 'barc4') {
        getDataToInputs(1);
    }
});

/**
 * @event file-input.change
 * @memberof Bim
 * @description Handles file uploads for notes.
 * Uploads the selected file to the server via AJAX.
 * @private
 */
$("#searchedData").on("change", "input[type='file']", function () {
    var form_data = new FormData(),
        files = $(this)[0].files[0],
        id_el = $(this).attr("id").split("_")[1],
        controller = setController();

    form_data.append('file', files);
    form_data.append('barc4', barc4.val());
    form_data.append('side', $("#side").val());
    form_data.append('wpn', $("#wpn").val());
    form_data.append('l', id_el);
    form_data.append('id_row', $("#id_row_" + id_el).val());

    $.ajax({
        url: "index.php?controller=" + controller + "&action=uploadFileOnServer",
        type: 'post',
        data: form_data,
        contentType: false,
        processData: false,
        success: function (response) {
            if (response == 1) {
                refreshForm();
            } else {
                alert('Plik nie został przesłany');
            }
        },
    });
});

/**
 * @event delete-image.click
 * @memberof Bim
 * @description Handles deletion of note images.
 * Confirms deletion with user and removes image from server.
 * @private
 */
$("#searchedData").on("click", ".js-delete-image", function () {
    var askAboutRemoveImage = confirm("Czy chcesz usunąć obrazek z tej uwagi?");
    if (askAboutRemoveImage == true) {
        var controller = setController();
        idRowNotice = $(this).closest("li").find("input[name='id[]']").val();

        ajax(
            "index.php?controller=" + controller + "&action=deleteImageFromNotice",
            "idRowNotice=" + idRowNotice,
            function () {},
            function (response) {
                if (response == 1) {
                    $("li:has(input[name='id[]'][value='" + idRowNotice + "'])").remove();
                } else {
                    alert("Plik został usunięty");
                }
            },
            function () {}
        );
    }
});

   /**
 * @function loadDataForm
 * @memberof Bim
 * @description Loads HTML content into the form area and sets up data history tracking.
 * Also ensures required hidden fields exist in the form.
 * @param {string} response - HTML content to be loaded into the form area
 * @private
 */
function loadDataForm(response) {
    $("#searchedData").html(response);
    setDataHistoryAttributes();

    if ($('input[name="author"]').length === 0) {
        $('<input>').attr({
            type: 'hidden',
            name: 'author'
        }).appendTo('form');
    }
    if ($('input[name="comments"]').length === 0) {
        $('<input>').attr({
            type: 'hidden',
            name: 'comments'
        }).appendTo('form');
    }
}

/**
 * @function setDataHistoryAttributes
 * @memberof Bim
 * @description Sets data-history attributes on form elements to track changes.
 * Handles different field types including checkboxes and stores detailed note information.
 * @private
 */
function setDataHistoryAttributes() {
    // Save initial field values for change tracking
    $('#searchedData').find('input, select, textarea').each(function() {
        var $field = $(this);
        var name = $field.attr('name');
        
        // Skip fields without name or service fields
        if (!name || name === 'id[]' || name === 'saveData' || name === 'imageNotice' || 
            name === 'notes[]' || name.startsWith('note_')) {
            return;
        }
        
        var value = $field.val() || '';
        
        // For checkboxes, save state rather than value
        if ($field.attr('type') === 'checkbox') {
            value = $field.is(':checked') ? '1' : '0';
        }
        
        // Save initial value for change tracking
        $field.attr('data-history', value);
    });
    
    // Create and save detailed information about notes
    if ($("#notes-list").length) {
        var notesData = [];
        
        $("#notes-list li").each(function() {
            var noteId = $(this).find('input[name="id[]"]').val();
            var noteElement = $(this).find('select[name="notes[]"]');
            var noteText = noteElement.find('option:selected').text() || '';
            
            if (noteId) {
                notesData.push({
                    id: noteId,
                    text: noteText.trim()
                });
            }
        });
        
        // Save note data for tracking
        $("#notes-list").attr('data-history-notes', JSON.stringify(notesData));
    }
}

/**
 * @event barcode-item.click
 * @memberof Bim
 * @description Handles clicks on barcode suggestion items in the dropdown list.
 * Selects the barcode and triggers data loading for the selected item.
 * @private
 */
$(".barcode-information").on("click", "li", function () {
    var autougester = $("#" + autougesterNameId);
    autougester.addClass("hide");
    barc4.val($(this).data("barcode"));
    getDataToInputs(1);
});

/**
 * @function getDataToInputs
 * @memberof Bim
 * @description Loads form data from server based on selected barcode, side and devices.
 * Checks if conditions are met before making the AJAX request.
 * @param {boolean} confirmed - Flag to force data loading even if conditions aren't fully met
 * @private
 */
function getDataToInputs(confirmed) {
    var sideVal = $("#side").val(),
        barc4Val = $("#barc4").val(),
        devicesVal = $("#devices").val(),
        autougester = $("#" + autougesterNameId),
        condition = barc4Val !== "" && !autougester.find("li").hasClass("js-error") && !autougester.find("li").hasClass("js-list-barcode");

    if (condition || confirmed) {
        var controller = setController();
        ajax(
            "index.php?controller=" + controller + "&action=getDataInstructions",
            "barc4=" + barc4Val + "&side=" + sideVal + "&devices=" + devicesVal,
            function () { },
            function(response) {
                loadDataForm(response);
                setInitialProgramStates();
            },
            function () { },
            "html"
        );
    }
}

/**
 * @event side-devices.change
 * @memberof Bim
 * @description Handles changes to side or devices selectors.
 * Triggers reloading of data when these values change.
 * @private
 */
side.add(devices).on("change", function () {
    getDataToInputs();
});

/**
 * @event numTilesInPanel.input
 * @memberof Bim
 * @description Validates input for the tiles in panel field.
 * Ensures only numeric values are entered.
 * @private
 */
$("#numTilesInPanel").on("input", function() {
    var value = $(this).val();
    // Remove any non-numeric characters
    value = value.replace(/[^0-9]/g, '');
    $(this).val(value);
});

/**
 * @event print_program_checkbox.change
 * @memberof Bim
 * @description Handles changes to print program checkboxes.
 * Enables or disables corresponding machine program input fields based on checkbox state.
 * @private
 */
$(document).on("change", "input[id^='print_program_'], input[name^='print_program_']", function() {
    var checkboxElement = $(this);
    var line = "";

    // Extract line identifier
    if (checkboxElement.attr("id")) {
        line = checkboxElement.attr("id").replace("print_program_", "").toLowerCase();
    } else if (checkboxElement.attr("name")) {
        line = checkboxElement.attr("name").replace("print_program_", "").toLowerCase();
    }

    if (!line) return;

    // Find machine input field
    var machineInput = $("#machine_program_" + line);
    if (!machineInput.length) {
        machineInput = $("[name='machine_program_" + line + "']");
    }

    if (machineInput.length) {
        var isChecked = checkboxElement.prop("checked");
        machineInput.prop("disabled", !isChecked);
        machineInput.css("background-color", isChecked ? "#ffffff" : "#f0f0f0");
    }
});

/**
 * @function setInitialProgramStates
 * @memberof Bim
 * @description Sets initial states for program checkboxes and machine input fields.
 * Disabled machine input fields when their corresponding checkboxes are unchecked.
 * @private
 */
function setInitialProgramStates() {
    console.log("Ustawianie początkowych stanów programu");

    // First, disable all machine program fields by default
    $("[name^='machine_program_']").prop("disabled", true).css("background-color", "#f0f0f0");

    // Iterate through all checkboxes
    $("input[id^='print_program_'], input[name^='print_program_']").each(function() {
        var checkboxElement = $(this);
        var line = "";

        // Extract line identifier
        if (checkboxElement.attr("id")) {
            line = checkboxElement.attr("id").replace("print_program_", "").toLowerCase();
        } else if (checkboxElement.attr("name")) {
            line = checkboxElement.attr("name").replace("print_program_", "").toLowerCase();
        }

        if (!line) return;

        // Find machine input field
        var machineInput = $("#machine_program_" + line);
        if (!machineInput.length) {
            machineInput = $("[name='machine_program_" + line + "']");
        }

        if (machineInput.length) {
            var isChecked = checkboxElement.prop("checked");
            machineInput.prop("disabled", !isChecked);
            machineInput.css("background-color", isChecked ? "#ffffff" : "#f0f0f0");
        }
    });
}

/**
 * @event document.ready
 * @memberof Bim
 * @description Initializes program states when document is ready.
 * Uses setTimeout to ensure DOM is fully loaded.
 * @private
 */
$(document).ready(function() {
    setTimeout(setInitialProgramStates, 200);
});

/**
 * @event ajax.complete
 * @memberof Bim
 * @description Reinitializes program states after AJAX requests complete.
 * Ensures states are correctly set after dynamic content is loaded.
 * @private
 */
$(document).ajaxComplete(function(event, xhr, settings) {
    setTimeout(setInitialProgramStates, 200);
});

/**
 * @event tab.shown
 * @memberof Bim
 * @description Reinitializes program states when a tab is activated.
 * Ensures states are correct when switching between tabs.
 * @private
 */
$(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function(e) {
    setTimeout(setInitialProgramStates, 200);
});

/**
 * @function loadDataForm
 * @memberof window
 * @description Global override for loadDataForm to ensure states are set after loading.
 * Calls original function and then sets program states.
 * @param {string} response - HTML content to be loaded
 * @private
 */
var originalLoadDataForm = window.loadDataForm || function(){};
window.loadDataForm = function(response) {
    originalLoadDataForm.apply(this, arguments);
    setTimeout(setInitialProgramStates, 200);
};

    /**
 * @function setInitialProgramStates
 * @memberof Bim
 * @description Sets initial states for program checkboxes and machine input fields.
 * Disables machine input fields when their corresponding checkboxes are unchecked.
 * @private
 */
function setInitialProgramStates() {
    console.log("Ustawianie początkowych stanów programu");

    // First, disable all machine program fields by default
    $("[name^='machine_program_']").prop("disabled", true).css("background-color", "#f0f0f0");

    // Iterate through all checkboxes
    $("input[id^='print_program_'], input[name^='print_program_']").each(function() {
        var checkboxElement = $(this);
        var line = "";

        // Extract line identifier
        if (checkboxElement.attr("id")) {
            line = checkboxElement.attr("id").replace("print_program_", "").toLowerCase();
        } else if (checkboxElement.attr("name")) {
            line = checkboxElement.attr("name").replace("print_program_", "").toLowerCase();
        }

        if (!line) return;

        // Find machine input field
        var machineInput = $("#machine_program_" + line);
        if (!machineInput.length) {
            machineInput = $("[name='machine_program_" + line + "']");
        }

        if (machineInput.length) {
            var isChecked = checkboxElement.prop("checked");
            machineInput.prop("disabled", !isChecked);
            machineInput.css("background-color", isChecked ? "#ffffff" : "#f0f0f0");
        }
    });
}

/**
 * @event document.ready
 * @memberof Bim
 * @description Initializes program states when document is ready.
 * Uses setTimeout to ensure DOM is fully loaded.
 * @private
 */
$(document).ready(function() {
    // Set initial states with a slight delay to ensure DOM is fully loaded
    setTimeout(setInitialProgramStates, 200);
    
    // Apply initial states after tab changes
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') === "#othetInformation") {
            console.log("Pokazana zakładka Inne informacje - inicjalizacja stanów programu");
            setInitialProgramStates();
        }
    });
});

/**
 * @function loadDataForm
 * @memberof window
 * @description Global override for loadDataForm to ensure states are set after loading.
 * Calls original function and then sets program states.
 * @param {string} response - HTML content to be loaded
 * @private
 */
var originalLoadDataForm = window.loadDataForm || function(){};
window.loadDataForm = function(response) {
    originalLoadDataForm.apply(this, arguments);
    console.log("Dane formularza załadowane - ustawianie stanów programu");
    setTimeout(setInitialProgramStates, 200);
};

/**
 * @event document.ready
 * @memberof Bim
 * @description Additional document ready handler that initializes data history attributes
 * and program states when the document is ready.
 * @private
 */
$(document).ready(function() {
    setDataHistoryAttributes();
    setInitialProgramStates();
});

/**
 * @function loadDataForm
 * @memberof Bim
 * @description Enhanced version of loadDataForm that updates data history attributes
 * and program states after loading data via AJAX.
 * @param {string} response - HTML content to be loaded into the form area
 * @private
 */
var originalLoadDataForm = loadDataForm;
loadDataForm = function(response) {
    originalLoadDataForm.apply(this, arguments);
    // Initialize data history attributes after loading data
    setTimeout(function() {
        setDataHistoryAttributes();
        setInitialProgramStates();
    }, 100);
};

/**
 * @event newbarc4.keyup
 * @memberof Bim
 * @description Handles keyup events on the new barcode field.
 * Checks if the barcode exists in the database and updates the status display.
 * @private
 */
newbarc4.on("keyup", function() {
    var barc4Val = $(this).val();
    var sideVal = $("#side").val();
    
    if (barc4Val.length >= 4) {
        ajax(
            "index.php?controller=administration&action=findnewBarcode",
            "newbarc4=" + barc4Val,
            null,
            function(response) {
                var statusDiv = $("#instruction-status");
                if (!statusDiv.length) {
                    statusDiv = $('<div id="instruction-status" class="instruction-status"></div>');
                    $(".new-barcode").append(statusDiv);
                }

                var result = JSON.parse(response);
                if (result.SITUATION === 'EXISTS') {
                    statusDiv.removeClass('instruction-new').addClass('instruction-exists')
                           .text('Instrukcja dla tego produktu już istnieje w bazie danych, użyj zakładki do edycji');
                } else if (result.SITUATION === 'NEW') {
                    statusDiv.removeClass('instruction-exists').addClass('instruction-new')
                           .text('Dla tego kodu kreskowego nie ma jeszcze instrukcji');
                }
            },
            null,
            "html"
        );
    }
});

/**
 * @event newbarc4.keyup
 * @memberof Bim
 * @description Alternative handler for checking instruction status via direct AJAX call.
 * Shows status information about whether an instruction already exists for the barcode.
 * @private
 */
$("#newbarc4").on("keyup", function() {
    var barc4 = $(this).val();
    var side = $("#side").val();
    
    // Create or get status div
    var statusDiv = $("#instruction-status");
    if (!statusDiv.length) {
        statusDiv = $('<div id="instruction-status" class="instruction-status"></div>');
        $(this).parent().append(statusDiv);
    }
    
    if (barc4.length >= 4) {
        $.ajax({
            url: 'index.php?controller=administration&action=checkInstruction',
            method: 'POST',
            data: {
                barc4: barc4,
                side: side
            },
            success: function(response) {
                statusDiv
                    .removeClass('instruction-exists instruction-new')
                    .addClass(response.exists ? 'instruction-exists' : 'instruction-new')
                    .text(response.message)
                    .show();
            },
            error: function() {
                statusDiv.hide();
            }
        });
    } else {
        statusDiv.hide();
    }
});
    /**
 * @function setupFormHandlers
 * @memberof Bim
 * @description Sets up form submission handlers for the application.
 * Clears any existing handlers to prevent duplication and establishes proper handling logic.
 * @private
 */
function setupFormHandlers() {
    // Remove all existing handlers to avoid duplication
    $(document).off("submit", "form");
    $("#copyForm").off("submit");
    $("#searchedData form").off("submit");
    $("form").off("submit");
    
    /**
     * @event copyForm.submit
     * @memberof Bim
     * @description Handles the copy form submission with validation and AJAX processing.
     * Performs checks for document number, barcode status, and prevents unwanted actions.
     * @private
     */
    $("#copyForm").on("submit", function (e) {
        e.preventDefault();
        var form = $(this);
        var data = form.serialize() + "&saveData=1";
        
        // Check if document number field is filled
        var documentNrValue = $("#documentNr").val();
        if (!documentNrValue) {
            swal("Ostrzeżenie!", "Proszę wypełnić pole 'Numer dokumentu'.", "warning");
            return false;
        }
        
        // Check barcode status before copying
        var newBarc4Value = $("#newbarc4").val();
        var instruktionStatusMsg = "";
        var barcodeValidMsg = "";
        
        // Find barcode status messages
        $(".instruction-status").each(function() {
            instruktionStatusMsg = $(this).text();
        });
        
        // Find barcode validity messages
        $(".new-barcode").find("div:contains('Nie ma takiego barkodu')").each(function() {
            barcodeValidMsg = $(this).text();
        });
        
        // Check for errors with priority
        if (barcodeValidMsg.indexOf("Nie ma takiego barkodu w bazie danych") > -1) {
            swal("Błąd", "Nie ma takiego barkodu w bazie danych.", "error");
            return false;
        } else if (instruktionStatusMsg.indexOf("Instrukcja dla tego produktu już istnieje") > -1) {
            swal("Błąd", "Instrukcja dla tego produktu już istnieje w bazie danych, użyj zakładki do edycji", "error");
            return false;
        }
        
        // Check form action and block unwanted calls
        var actionUrl = form.attr("action");
        if (actionUrl.indexOf("action=copy") === -1) {
            console.log("Blokowanie wysyłania formularza z niechcianą akcją: " + actionUrl);
            return false;
        }
        
        console.log("Przesłanie kopii formularza na adres: " + actionUrl);
        $.ajax({
            type: 'POST',
            url: actionUrl,
            data: data,
            success: function (response) {
                try {
                    var result = JSON.parse(response);
                    if (result.success) {
                        // Call the standalone saveHistory function
                        saveHistory($("#barc4").val(), $("#side").val(), $("#newbarc4").val());
                        
                        swal("Sukces!", "Kopiowanie zakończone pomyślnie", "success")
                            .then(function () {
                                window.location.reload(true);
                            });
                    } else {
                        swal("Błąd", result.message || "Błąd podczas kopiowania", "error");
                    }
                } catch (e) {
                    // Call the standalone saveHistory function here too
                    saveHistory($("#barc4").val(), $("#side").val(), $("#newbarc4").val());
                    
                    swal("Sukces!", "Kopiowanie zakończone pomyślnie", "success")
                        .then(function () {
                            window.location.reload(true);
                        });
                }
            },
            error: function () {
                swal("Błąd", "Błąd podczas komunikacji z serwerem", "error");
            }
        });
    });

    /**
     * @event form.submit
     * @memberof Bim
     * @description Global handler for all form submissions.
     * Prevents unwanted form submissions, especially on the copy page.
     * @private
     */
    $(document).on("submit", "form", function(e) {
        var form = $(this);
        
        // If this is not the copy form, check the action
        if (form.attr('id') !== 'copyForm') {
            var actionUrl = form.attr("action") || '';
            
            // If we're on the copy page, block all other forms
            if (window.location.href.indexOf("action=copy") > -1) {
                console.log("Blokowanie przesyłania formularzy na stronie kopiowania: " + actionUrl);
                e.preventDefault();
                return false;
            }
            
            // Block unwanted actions on the copy page
            if (actionUrl.indexOf("action=saveHistory") > -1 || 
                actionUrl.indexOf("action=edit") > -1) {
                if (window.location.href.indexOf("action=copy") > -1) {
                    console.log("Blokowanie niechcianych działań: " + actionUrl);
                    e.preventDefault();
                    return false;
                }
            }
        }
    });
    
    /**
     * @event searchedData-form.submit
     * @memberof Bim
     * @description Handler for form submissions inside the searchedData container.
     * Specifically blocks forms on the copy page.
     * @private
     */
    $("#searchedData").on("submit", "form", function(e) {
        if (window.location.href.indexOf("action=copy") > -1) {
            console.log("Wewnętrzne blokowanie przesyłania formularzy #searchedData");
            e.preventDefault();
            return false;
        }
    });
}

/**
 * @function getNoteTemplate
 * @memberof Bim
 * @description Generates HTML template for a new note item with status indicator and save button.
 * @param {number|string} index - The index/identifier for the new note
 * @param {boolean} [isSaved=false] - Whether the note has been saved to the database
 * @returns {string} HTML template string for the note list item
 * @private
 */
function getNoteTemplate(index, isSaved = false) {
    // If template is empty, try to get it again
    if (!notesOptionsTemplate) {
        initializeNotesOptions();
    }
    
    return `
        <li class="margin-bottom-15" data-note-index="${index}" data-saved="${isSaved}">
            <div>
                <select class="form-control" name="notes[]" data-history="0">
                    ${notesOptionsTemplate}
                </select>
                <input id="id_row_${index}" type="hidden" name="id[]" value="${index}">
                <button type="button" class="btn btn-xs btn-danger btn-remove-note" data-note-index="${index}">-</button>
                <span class="note-status ${isSaved ? 'text-success' : 'text-danger'}">
                    ${isSaved ? 'Zapisano' : 'Niezapisano'}
                </span>
                ${!isSaved ? '<button type="button" class="btn btn-xs btn-primary btn-save-note" data-note-index="' + index + '">Zapisz</button>' : ''}
            </div>
            <div class="position-relative js-image-new">
                <label for="imageNotice_${index}">Załaduj plik:</label>
                <input type="file" id="imageNotice_${index}" name="imageNotice">
            </div>
        </li>
    `;
}

/**
 * @event btn-add-note.click
 * @memberof Bim
 * @description Handler for adding a new note to the list.
 * Creates a new note element with temporary ID and unsaved status.
 * @private
 */
$("#searchedData").on("click", ".btn-add-note", function(e) {
    e.preventDefault();
    var barc4 = $("#barc4").val();
    var side = $("#side").val();
    
    // Generate temporary ID for the new note
    var tempId = "temp_" + new Date().getTime();
    
    // Create new note element with temporary ID and unsaved status
    var noteTemplate = getNoteTemplate(tempId, false);
    $("#notes-list").append(noteTemplate);
    
    // Reindex elements
    reindexNotes();
    
    // Highlight animation for new note
    var newNote = $("#notes-list").last();
    newNote.css("background-color", "#ffeeba");
    setTimeout(function() {
        newNote.css("background-color", "");
    }, 1000);
});

/**
 * @event btn-save-note.click
 * @memberof Bim
 * @description Handler for saving a note to the database.
 * Validates selection, updates status indicators, and records history changes.
 * @private
 */
$("#searchedData").on("click", ".btn-save-note", function(e) {
    e.preventDefault();
    var noteElement = $(this).closest("li");
    var noteIndex = noteElement.data("note-index");
    var selectedParentNoteId = noteElement.find("select[name='notes[]']").val();
    var barc4 = $("#barc4").val();
    var side = $("#side").val();
    
    // Validate note selection
    if (!selectedParentNoteId || selectedParentNoteId === "0") {
        alert("Wybierz uwagę z listy");
        return;
    }
    
    // Button and status reference
    var saveButton = $(this);
    var statusElement = noteElement.find(".note-status");
    
    // Show saving in progress
    saveButton.prop('disabled', true).text("Zapisywanie...");
    
    // Send request to server for saving
    $.ajax({
        url: 'index.php?controller=administration&action=addNote',
        method: 'POST',
        data: {
            barc4: barc4,
            side: side,
            parent_note_id: selectedParentNoteId
        },
        dataType: 'json',
        success: function(response) {
            console.log("Server response:", response);
            
            if (response && response.success) {
                // Update note ID from server response
                noteElement.find("input[name='id[]']").val(response.id);
                
                // Update status indicator
                noteElement.attr("data-saved", "true");
                statusElement.removeClass("text-danger").addClass("text-success").text("Zapisano");
                
                // Remove save button
                saveButton.remove();
                
                // Confirm successful save with highlight
                noteElement.css("background-color", "#d4edda");
                setTimeout(function() {
                    noteElement.css("background-color", "");
                }, 1000);
                
                // Record history after adding note (only on edit page)
                if (window.location.href.indexOf("action=edit") > -1) {
                    saveEditHistory(barc4, side);
                }
            } else {
                // Handle error
                statusElement.text("Błąd zapisu");
                alert('Błąd zapisu: ' + (response && response.error ? response.error : 'Nieznany błąd'));
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
            console.log("Response text:", xhr.responseText);
            alert('Błąd komunikacji z serwerem');
            statusElement.text("Błąd serwera");
        },
        complete: function() {
            // Restore button state
            saveButton.prop('disabled', false).text("Zapisz");
        }
    });
});
   
    /**
 * @function setupEditFormHandlers
 * @memberof Bim
 * @description Sets up form submission handlers for the edit page.
 * Handles validation, form submission via AJAX, and updates the history when successful.
 * Prevents default form submission and shows appropriate success/error messages.
 * @private
 */
function setupEditFormHandlers() {
    // Find the edit form
    var editForm = $("form[action*='action=edit']");
    
    // If we're on the edit page and form is found
    if (window.location.href.indexOf("action=edit") > -1 && editForm.length) {
        // Remove any existing handlers to avoid duplication
        editForm.off("submit");
        
        // Add a single handler for the edit form
        editForm.on("submit", function (e) {
            e.preventDefault();
            var form = $(this);
            var data = form.serialize();
            
            // Check barcode status before submitting form
            var barc4Value = $("#barc4").val();
            var barcodeValidMsg = "";
            
            // Find barcode validity messages
            $(".barcode-information").find("div:contains('Nie ma takiego barkodu')").each(function() {
                barcodeValidMsg = $(this).text();
            });
            
            // Validate barcode before submitting form
            if (barcodeValidMsg.indexOf("Nie ma takiego barkodu w bazie danych") > -1) {
                swal("Błąd", "Nie ma takiego barkodu w bazie danych.", "error");
                return false;
            }
            
            console.log("Przesyłanie formularza edycji");
            $.ajax({
                type: 'POST',
                url: form.attr("action"),
                data: data,
                success: function (response) {
                    try {
                        var result = JSON.parse(response);
                        if (result.success) {
                            // Successful edit - record history
                            saveEditHistory($("#barc4").val(), $("#side").val());
                            
                            swal("Sukces!", "Instrukcja została zaktualizowana", "success")
                                .then(function () {
                                    // Optional - reload or other action
                                    window.location.reload(true);
                                });
                        } else {
                            swal("Błąd", result.message || "Błąd podczas aktualizacji", "error");
                        }
                    } catch (e) {
                        // If JSON parsing failed, consider operation successful
                        saveEditHistory($("#barc4").val(), $("#side").val());
                        
                        swal("Sukces!", "Instrukcja została zaktualizowana", "success")
                            .then(function () {
                                window.location.reload(true);
                            });
                    }
                },
                error: function () {
                    swal("Błąd", "Błąd podczas komunikacji z serwerem", "error");
                }
            });
        });
    }
}

/**
 * @function saveHistory
 * @memberof Bim
 * @description Records history when copying instructions from one barcode to another.
 * Collects changes between original and current values, formats them into a comment,
 * and sends the data to the server via AJAX.
 * @param {string} sourceBarc4 - Source barcode to copy from
 * @param {string} sourceSide - Source side value (1 or 2)
 * @param {string} targetBarc4 - Target barcode to copy to
 * @private
 */
function saveHistory(sourceBarc4, sourceSide, targetBarc4) {
    // Base comment
    var comment = "Skopiowano z " + sourceBarc4 + " (strona " + sourceSide + ") do " + targetBarc4;
    var changes = [];
    
    // Get user name
    var author = $("#userName").val() ? ($("#userName").val() + " " + $("#userLastName").val()) : "User";
    
    // Track already processed fields
    var processedFields = {};
    
    // Map field names to their readable labels
    var fieldMappings = {
        'name': 'Nazwa',
        'subgroup': 'Podgrupa',
        'tile': 'Nr płytka drukowana',
        'numTilesInPanel': 'Ilość w panelу',
        'tileWidth': 'Szerokość płytкi',
        'width_tiles': 'Szerokość płytкi',
        'assembly_order': 'Kolejność montażу',
        'print_prog': 'Program druku',
        'wpn': 'WPN'
    };
    
    // Collect field changes if original values exist
    $('#searchedData').find('input, select, textarea').each(function() {
        var $field = $(this);
        var name = $field.attr('name');
        
        // Skip fields without name, service fields, and printing program fields
        if (!name || 
            name === 'id[]' || 
            name === 'saveData' || 
            name === 'imageNotice' || 
            name === 'notes[]' || 
            name.startsWith('note_') ||
            name.startsWith('print_program_') ||
            name.startsWith('old_print_prog_') ||
            processedFields[name]) {
            return;
        }
        
        // Skip machine programs - handle them separately
        if (name && name.startsWith('machine_program_')) {
            return;
        }
        
        var currentValue = $field.val();
        var originalValue = $field.attr('data-original');
        
        // For checkboxes, get the actual state
        if ($field.attr('type') === 'checkbox') {
            currentValue = $field.is(':checked') ? '1' : '0';
        }
        
        // If original value exists and differs from current
        if (originalValue !== undefined && currentValue !== originalValue) {
            var fieldLabel = fieldMappings[name] || name;
            changes.push(fieldLabel + ': ' + originalValue + ' zmieniono na ' + currentValue);
            processedFields[name] = true;
        }
    });
    
    // Process machine programs - use consistent format
    $('[name^="machine_program_"]').each(function() {
        var $field = $(this);
        var name = $field.attr('name');
        var lineName = name.replace('machine_program_', '').toUpperCase();
        var currentValue = $field.val() || '';
        var originalValue = $field.attr('data-original') || '';
        
        if (currentValue !== originalValue) {
            changes.push('Program dla maszyn ' + lineName + ': ' + originalValue + ' zmieniono na ' + currentValue);
            processedFields[name] = true;
        }
    });
    
    // Collect data about copied notes
    var noteIds = [];
    if ($("#notes-list").length) {
        var notesInfo = [];
        
        $("#notes-list li").each(function() {
            var noteId = $(this).find('input[name="id[]"]').val();
            if (noteId && noteId.toString().indexOf('temp_') == -1) { // Only real note IDs
                noteIds.push(noteId);
                
                var noteText = $(this).find('select[name="notes[]"] option:selected').text().trim();
                var optgroup = $(this).find('select[name="notes[]"] option:selected').closest('optgroup');
                var category = optgroup.length ? optgroup.attr('label') || '' : '';
                
                if (noteText) {
                    notesInfo.push(category ? '[' + category + '] ' + noteText : noteText);
                }
            }
        });
        
        // Add notes to comment only if there are any
        if (notesInfo.length > 0) {
            // Add marker for copied notes
            changes.push('Skopiowane uwagi:');
            Array.prototype.push.apply(changes, notesInfo);
        }
    }
    
    // Filter changes, removing mentions of print programs
    changes = changes.filter(function(change) {
        return !change.includes('print_program_') && 
               !change.includes('old_print_prog_') && 
               !change.includes('zmieniono na on') &&
               !change.includes('zmieniono na off');
    });
    
    // Add changes to comment
    if (changes.length > 0) {
        comment += "\nZmienione pola:\n" + changes.join('\n');
        if (comment.length > 2000) {
            comment = comment.substring(0, 1997) + "...";
        }
    }
    
    // Get values of all form fields for submission
    var formData = {
        "BARC4": targetBarc4,
        "SIDE": sourceSide,
        "AUTHOR": author,
        "COMMENTS": comment,
        "LINE": $("#devices").val() || '',
        "NAME_I": $('#name').val() || '',
        "WPN": $('#wpn').val() || '',
        "SUBGROUP": $('#subgroup').val() || '',
        "TILE": $('#tile').val() || '',
        "NUM_TILES_IN_PANEL": $('#numTilesInPanel').val() || '',
        "WIDTH_TILES": $('#tileWidth').val() || $('#width_tiles').val() || '',
        "ASSEMBLY_ORDER": $('#assembly_order').val() || '',
        "PROGRAM_PRINT_PROG": $('#print_prog').val() || '',
        
        // Add machine programs
        "MACHINE_PROGRAM_1R": $("[name='machine_program_1r']").val() || '',
        "MACHINE_PROGRAM_2R": $("[name='machine_program_2r']").val() || '',
        "MACHINE_PROGRAM_3R": $("[name='machine_program_3r']").val() || '',
        "MACHINE_PROGRAM_4R": $("[name='machine_program_4r']").val() || '',
        "MACHINE_PROGRAM_1G": $("[name='machine_program_1g']").val() || '',
        "MACHINE_PROGRAM_2G": $("[name='machine_program_2g']").val() || '',
        "MACHINE_PROGRAM_3G": $("[name='machine_program_3g']").val() || ''
    };
    
    // Add note IDs
    if (noteIds.length > 0) formData.NOTES_ID_1 = noteIds[0];
    if (noteIds.length > 1) formData.NOTES_ID_2 = noteIds[1];
    if (noteIds.length > 2) formData.NOTES_ID_3 = noteIds[2];
    if (noteIds.length > 3) formData.NOTES_ID_4 = noteIds[3];
    
    // Duplicate fields for backward compatibility
    formData.barc4 = targetBarc4;
    formData.side = sourceSide;
    formData.author = author;
    formData.comments = comment;
    formData.name = formData.NAME_I;
    formData.wpn = formData.WPN;
    formData.devices = formData.LINE;
    
    console.log("Sending copy history data:", formData);
    
    // Send to server
    $.ajax({
        url: "index.php?controller=administration&action=saveHistory",
        type: "POST", 
        data: formData,
        success: function(response) {
            console.log("Copy history saved successfully:", response);
        },
        error: function(xhr, status, error) {
            console.error("Error saving copy history:", error);
        }
    });
}
/**
 * @function setOriginalFormValues
 * @memberof Bim
 * @description Stores initial form field values as data attributes for tracking changes.
 * Called when form loads initially and after AJAX updates.
 * @private
 */
function setOriginalFormValues() {
    // Save initial field values for change tracking
    $('#searchedData').find('input, select, textarea').each(function() {
        var $field = $(this);
        var name = $field.attr('name');
        
        // Skip fields without name or service fields
        if (!name || name === 'id[]' || name === 'saveData' || name === 'imageNotice' || 
            name === 'notes[]' || name.startsWith('note_')) {
            return;
        }
        
        var value = $field.val() || '';
        
        // For checkboxes, save state rather than value
        if ($field.attr('type') === 'checkbox') {
            value = $field.is(':checked') ? '1' : '0';
        }
        
        // Save initial value for change tracking
        $field.attr('data-original', value);
    });
}

/**
 * @event document.ready
 * @memberof Bim
 * @description Initialize form value tracking when document is ready.
 * @private
 */
$(document).ready(function() {
    // Other initialization code may exist here
    setOriginalFormValues();
});

/**
 * @event document.ajaxComplete
 * @memberof Bim
 * @description Reset form value tracking after data is loaded via AJAX.
 * Only executes for getDataInstructions requests which update form content.
 * @private
 */
$(document).ajaxComplete(function(event, xhr, settings) {
    if (settings.url.indexOf("getDataInstructions") > -1) {
        setTimeout(setOriginalFormValues, 100);
    }
});

/**
 * @function checkWpn
 * @memberof Bim
 * @description Processes barcode lookup results for WPN input fields.
 * Updates UI with validation states and error messages.
 * @param {string} response - Server response containing barcode or none
 * @private
 */
function checkWpn(response) {
    var barcode = response.trim(),
        inputObj = $("#barcodeNumber_" + driveNumber.data("idel")),
        nav = $(".nav"),
        errorInputNameClass = "errorValidation";

    if (barcode == "none") {
        inputObj.val("Brak barkodu dla wpisanego WPN. Poproś o dodanie barkodu do bazy.");
        inputObj.addClass(errorInputNameClass);
        nav.addClass("error-on-page");
    } else {
        inputObj.val(barcode);
        inputObj.removeClass(errorInputNameClass);
        driveNumberFields.each(function (index) {
            if ($(this).val() != "" && index + 1 == driveNumberFields.length) {
                nav.removeClass("error-on-page");
            }
        });
    }
}

/**
 * @event driveNumberFields.keyup
 * @memberof Bim
 * @description Handles keyup events on drive number input fields.
 * Triggers AJAX request to look up barcode by drive number.
 * @private
 */
driveNumberFields.off("keyup").on("keyup", function () {
    logMessage("driveNumber was changed.");
    var that = $(this);
    driveNumber = $(this);
    ajax(
        "index.php?controller=administration&action=findBarcViaWpn",
        { driveNumber: that.val() },
        function () {},
        checkWpn,
        function () {}
    );
});

/**
 * @function saveEditHistory
 * @memberof Bim
 * @description Records edit history for instruction changes.
 * Collects all modified fields, notes changes, and sends to server.
 * @param {string} barc4 - Barcode identifier being edited
 * @param {string} side - Side identifier (1 or 2)
 * @private
 */
function saveEditHistory(barc4, side) {
    // Collect information about all changed fields
    var changes = [];
    var fieldMappings = {
        'name': 'Nazwa',
        'subgroup': 'Podgrupa',
        'tile': 'Nr płytka drukowana',
        'numTilesInPanel': 'Ilość w panelу',
        'tileWidth': 'Szerokość płytкi',
        'width_tiles': 'Szerokość płytкi',
        'assembly_order': 'Kolejność montażу',
        'print_prog': 'Program druku',
        'wpn': 'WPN'
    };
    
    // Track already processed fields
    var processedFields = {};
    
    // Find all fields with changed values
    $('#searchedData').find('input, select, textarea').each(function() {
        var $field = $(this);
        var name = $field.attr('name');
        
        // Skip fields without name, service fields, and all print program-related fields
        if (!name || 
            name === 'id[]' || 
            name === 'saveData' || 
            name === 'imageNotice' || 
            name === 'notes[]' || 
            name.startsWith('note_') || 
            name.startsWith('print_program_') ||  // Skip all print program checkboxes
            name.startsWith('old_print_prog_')) { // Skip all old program fields
            return;
        }
        
        // Skip machine programs - handle them separately
        if (name && name.startsWith('machine_program_')) {
            return;
        }
        
        var currentValue = $field.val();
        var oldValue = $field.attr('data-history');
        
        // For checkboxes, process specially
        if ($field.attr('type') === 'checkbox') {
            currentValue = $field.is(':checked') ? '1' : '0';
        }
        
        // If value changed, add to changes list
        if (oldValue !== undefined && currentValue !== oldValue) {
            var fieldLabel = fieldMappings[name] || name;
            changes.push(fieldLabel + ': ' + oldValue + ' zmieniono na ' + currentValue);
            processedFields[name] = true;
        }
    });
    
    // Process machine programs - use consistent format
    $('[name^="machine_program_"]').each(function() {
        var $field = $(this);
        var name = $field.attr('name');
        var lineName = name.replace('machine_program_', '').toUpperCase();
        var currentValue = $field.val() || '';
        var oldValue = $field.attr('data-history') || '';
        
        if (currentValue !== oldValue) {
            // Use only "Program dla maszyn X" format for consistency
            changes.push('Program dla maszyn ' + lineName + ': ' + oldValue + ' zmieniono na ' + currentValue);
            processedFields[name] = true;
        }
    });
    
    // Enhanced handling of note changes
    if ($("#notes-list").length) {
        var currentNotes = [];
        var oldNotesData = [];
        
        // Get current notes with their ID and text
        $("#notes-list li").each(function() {
            var noteId = $(this).find('input[name="id[]"]').val();
            var noteElement = $(this).find('select[name="notes[]"]');
            var noteText = noteElement.find('option:selected').text() || '';
            
            if (noteId) {
                currentNotes.push({
                    id: noteId,
                    text: noteText.trim()
                });
            }
        });
        
        // Get previous note data
        if ($("#notes-list").attr('data-history-notes')) {
            try {
                oldNotesData = JSON.parse($("#notes-list").attr('data-history-notes')) || [];
            } catch (e) {
                oldNotesData = [];
                console.error("Error parsing notes history:", e);
            }
        }
        
        // Find added notes
        var addedNotes = currentNotes.filter(function(note) {
            return !oldNotesData.some(function(oldNote) {
                return oldNote.id === note.id;
            });
        });
        
        // Find removed notes
        var removedNotes = oldNotesData.filter(function(oldNote) {
            return !currentNotes.some(function(note) {
                return note.id === oldNote.id;
            });
        });
        
        // Add information about added notes
        if (addedNotes.length > 0) {
            var addedNotesDetails = [];
            addedNotes.forEach(function(note) {
                addedNotesDetails.push(note.text);
            });
            changes.push('Dodano uwagi (' + addedNotes.length + '): \n' + addedNotesDetails.join('\n'));
        }
        
        // Add information about removed notes
        if (removedNotes.length > 0) {
            var removedNotesDetails = [];
            removedNotes.forEach(function(note) {
                removedNotesDetails.push(note.text);
            });
            changes.push('Usunięto uwagi (' + removedNotes.length + '): \n' + removedNotesDetails.join('\n'));
        }
        
        // Update notes history after changes
        $("#notes-list").attr('data-history-notes', JSON.stringify(currentNotes));
    }
    
    // Create comment with all changes
    var comment = "Zaktualizowana instrukcja dla " + barc4 + " (strona " + side + ")";
    if (changes.length > 0) {
        comment += "\nZmienione pola:\n" + changes.join('\n');
        if (comment.length > 2000) {
            comment = comment.substring(0, 1997) + "...";
        }
    }
    
    // Get user name
    var author = $("#userName").val() ? ($("#userName").val() + " " + $("#userLastName").val()) : "User";
    
    // Get note IDs
    var noteIds = [];
    if ($("#notes-list").length) {
        $("#notes-list li").each(function() {
            var noteId = $(this).find('input[name="id[]"]').val();
            if (noteId) {
                noteIds.push(noteId);
            }
        });
    }
    
    // Get OLD values for history
    var oldValues = {
        "NAME_I": $('#name').attr('data-history') || '',
        "WPN": $('#wpn').attr('data-history') || '',
        "SUBGROUP": $('#subgroup').attr('data-history') || '',
        "TILE": $('#tile').attr('data-history') || '',
        "NUM_TILES_IN_PANEL": $('#numTilesInPanel').attr('data-history') || '',
        "WIDTH_TILES": $('#tileWidth').attr('data-history') || $('#width_tiles').attr('data-history') || '',
        "ASSEMBLY_ORDER": $('#assembly_order').attr('data-history') || '',
        "PROGRAM_PRINT_PROG": $('#print_prog').attr('data-history') || ''
    };
    
    // Get OLD machine program values
    var oldMachinePrograms = {
        "MACHINE_PROGRAM_1R": $("[name='machine_program_1r']").attr('data-history') || '',
        "MACHINE_PROGRAM_2R": $("[name='machine_program_2r']").attr('data-history') || '',
        "MACHINE_PROGRAM_3R": $("[name='machine_program_3r']").attr('data-history') || '',
        "MACHINE_PROGRAM_4R": $("[name='machine_program_4r']").attr('data-history') || '',
        "MACHINE_PROGRAM_1G": $("[name='machine_program_1g']").attr('data-history') || '',
        "MACHINE_PROGRAM_2G": $("[name='machine_program_2g']").attr('data-history') || '',
        "MACHINE_PROGRAM_3G": $("[name='machine_program_3g']").attr('data-history') || ''
    };
    
    // Create history data with OLD values
    var postData = {
        "BARC4": barc4,
        "SIDE": side,
        "AUTHOR": author,
        "COMMENTS": comment,
        "LINE": $("#devices").val() || '',
        "NAME_I": oldValues.NAME_I,
        "WPN": oldValues.WPN,
        "SUBGROUP": oldValues.SUBGROUP,
        "TILE": oldValues.TILE,
        "NUM_TILES_IN_PANEL": oldValues.NUM_TILES_IN_PANEL,
        "WIDTH_TILES": oldValues.WIDTH_TILES,
        "ASSEMBLY_ORDER": oldValues.ASSEMBLY_ORDER,
        "PROGRAM_PRINT_PROG": oldValues.PROGRAM_PRINT_PROG,
        
        // Add machine programs with OLD values
        "MACHINE_PROGRAM_1R": oldMachinePrograms.MACHINE_PROGRAM_1R,
        "MACHINE_PROGRAM_2R": oldMachinePrograms.MACHINE_PROGRAM_2R,
        "MACHINE_PROGRAM_3R": oldMachinePrograms.MACHINE_PROGRAM_3R,
        "MACHINE_PROGRAM_4R": oldMachinePrograms.MACHINE_PROGRAM_4R,
        "MACHINE_PROGRAM_1G": oldMachinePrograms.MACHINE_PROGRAM_1G,
        "MACHINE_PROGRAM_2G": oldMachinePrograms.MACHINE_PROGRAM_2G,
        "MACHINE_PROGRAM_3G": oldMachinePrograms.MACHINE_PROGRAM_3G
    };
    
    // Add note IDs
    if (noteIds.length > 0) postData.NOTES_ID_1 = noteIds[0];
    if (noteIds.length > 1) postData.NOTES_ID_2 = noteIds[1];
    if (noteIds.length > 2) postData.NOTES_ID_3 = noteIds[2];
    if (noteIds.length > 3) postData.NOTES_ID_4 = noteIds[3];
    
    // Duplicate fields for backward compatibility
    postData.barc4 = barc4;
    postData.side = side;
    postData.author = author;
    postData.comments = comment;
    postData.name = oldValues.NAME_I;
    postData.wpn = oldValues.WPN;
    postData.devices = $("#devices").val() || '';
    
    console.log("Sending history data with OLD values:", postData);
    
    // Send to server
    $.ajax({
        url: "index.php?controller=administration&action=saveHistory",
        type: "POST", 
        data: postData,
        success: function(response) {
            console.log("History saved successfully:", response);
        },
        error: function(xhr, status, error) {
            console.error("Error saving history:", error);
        }
    });
}

/**
 * @function handleCopyPageNotes
 * @memberof Bim
 * @description Manages notes on the copy page by disabling edit functionality.
 * Notes can only be modified on the edit page, not during copying process.
 * @private
 */
function handleCopyPageNotes() {
    // Check if we're on the copy page
    if (window.location.href.indexOf("action=copy") > -1 || $("#copyForm").length > 0) {
        console.log("Copy page detected - hiding note management controls");
        
        // Hide all note management buttons
        $(".btn-add-note").hide();
        $(".btn-remove-note").hide();
        $(".btn-save-note").hide();
        
        // Add warning message if it doesn't exist and notes are present
        if ($("#notes-warning").length === 0 && $("#notes-list").length > 0) {
            var warningMessage = $(
                '<div id="notes-warning" class="alert alert-danger" style="color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 10px; margin-bottom: 15px;">' +
                '<strong>Uwaga!</strong> Aby edytować uwagi, należy najpierw utworzyć instrukcję dla nowego barkodu, ' +
                'a następnie użyć zakładki edycji.' +
                '</div>'
            );
            
            // Add warning before notes list
            $("#notes-list").before(warningMessage);
            
            // Make all notes read-only
            $("#notes-list li").each(function() {
                $(this).find('select').prop('disabled', true);
                $(this).find('input[type="file"]').prop('disabled', true);
                $(this).css('opacity', '0.7');
            });
        }
    }
}

 
    // Initialize handlers when document is ready
    $(document).ready(function() {
        setupFormHandlers();
        setupEditFormHandlers();
        handleCopyPageNotes();
        setOriginalFormValues(); 
    });

    // Reinitialize handlers after any AJAX request completes
    $(document).ajaxComplete(function() {
        setupFormHandlers();
        setupEditFormHandlers();
        handleCopyPageNotes();
    });
    // Prevent form submission when Enter is pressed
    $(document).on('keydown', 'form', function(event) {
        if (event.key === "Enter") {
            return false;
        }
    });
}

var bim = new Bim();