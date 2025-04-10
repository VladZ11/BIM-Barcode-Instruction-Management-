function Bim() {
    var _self = this;
    var barc4Current = null,
        autougesterNameId = "autougester",
        autougester2NameId = "autougester2",
        barc4 = $("#barc4"),
        newbarc4 = $("#newbarc4"),
        side = $("#side"),
        devices = $("#devices"),
        driveNumber = null,
        idRowNotice = null,
        chosenComponent = null,
        driveNumberFields = $("input[name='driveNumber[]']");

    var userName = $("#userName").val();
    var userLastName = $("#userLastName").val();

    // Initialize notes options when document is ready
    var notesOptionsTemplate = '';
    function initializeNotesOptions() {
        var firstSelect = $("#notes-list select[name='notes[]']").first();
        if (firstSelect.length) {
            notesOptionsTemplate = firstSelect.html();
        }
    }
    
    // Call initialization on document ready and after form updates
    $(document).ready(initializeNotesOptions);
    
    // Update initialization after form data loads
    var originalLoadDataForm = loadDataForm;
    function loadDataForm(response) {
        originalLoadDataForm(response);
        initializeNotesOptions();
    }

    // Template for new note using stored options
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

   
// Обновленный обработчик удаления заметки с записью в историю
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
                            
                            // Запись в историю после удаления заметки (только на странице редактирования)
                            if (window.location.href.indexOf("action=edit") > -1) {
                                saveEditHistory(barc4, side);
                            }
                        });
                    } else {
                        // Server returned error
                        noteElement.removeClass("deleting");
                        console.error("Server returned error:", response);
                        alert('Error deleting note: Server returned an error');
                    }
                },
                error: function(xhr, status, error) {
                    // AJAX request failed
                    noteElement.removeClass("deleting");
                    console.error("AJAX Error:", status, error);
                    console.log("Response:", xhr.responseText);
                    alert('Server communication error');
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

    // Reindex remaining notes after deletion
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

    // Function for logging messages (only to console)
    function logMessage(message) {
        console.log(message);
    }

    // Function for sending AJAX requests
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

    // Function to reset form fields
    function resetFields() {
        $("#searchedData input").each(function () {
            $(this).val("");
        });
    }

    // Handling successful barcode search
    function findBarcodeSuccess(response) {
        logMessage("Kod kreskowy znaleziony pomyślnie: " + response);
        var autougester = $("#" + autougesterNameId);
        autougester.removeClass("hide");
        autougester.html(response);
    }

    // Completion of barcode search
    function findBarcodeComplete() {
        var autougester = $("#" + autougesterNameId);
        if (autougester.find("li").length == 1) {
            if (barc4Current.val() == autougester.find("li:first").data("barcode")) {
                autougester.addClass("hide");
                getDataToInputs();
            }
        }
    }

    // Set the controller dynamically
    function setController() {
        return controller !== "administration" ? "index" : "administration";
    }

    // Handle barcode input for both fields
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

    // Handle clicks on both autougester lists
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

    // Restoring old image upload and delete functionality
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
                    alert('file not uploaded');
                }
            },
        });
    });

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
                        alert("file deleted");
                    }
                },
                function () {}
            );
        }
    });

    // Loading data into the form
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

    // Set data-history attributes for form fields
   // Полная версия функции setDataHistoryAttributes с улучшенной обработкой заметок
function setDataHistoryAttributes() {
    // Сохраняем начальные значения полей для отслеживания изменений
    $('#searchedData').find('input, select, textarea').each(function() {
        var $field = $(this);
        var name = $field.attr('name');
        
        // Пропускаем поля без имени или служебные поля
        if (!name || name === 'id[]' || name === 'saveData' || name === 'imageNotice' || 
            name === 'notes[]' || name.startsWith('note_')) {
            return;
        }
        
        var value = $field.val() || '';
        
        // Для чекбоксов сохраняем состояние, а не значение
        if ($field.attr('type') === 'checkbox') {
            value = $field.is(':checked') ? '1' : '0';
        }
        
        // Сохраняем начальное значение для отслеживания изменений
        $field.attr('data-history', value);
    });
    
    // Создаем и сохраняем подробную информацию о заметках
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
        
        // Сохраняем данные о заметках для отслеживания
        $("#notes-list").attr('data-history-notes', JSON.stringify(notesData));
    }
}

    // Handling click on autougester list item
    $(".barcode-information").on("click", "li", function () {
        var autougester = $("#" + autougesterNameId);
        autougester.addClass("hide");
        barc4.val($(this).data("barcode"));
        getDataToInputs(1);
    });

    // Get data and populate form fields
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

    side.add(devices).on("change", function () {
        getDataToInputs();
    });

    // Add input validation
    $("#numTilesInPanel").on("input", function() {
        var value = $(this).val();
        // Remove any non-numeric characters
        value = value.replace(/[^0-9]/g, '');
        $(this).val(value);
    });

    // Unified checkbox handler
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

    // Unified initialization function
    function setInitialProgramStates() {
        console.log("Setting initial program states");

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

    // Initialize on document ready
    $(document).ready(function() {
        setTimeout(setInitialProgramStates, 200);
    });

    // Initialize after AJAX content loads
    $(document).ajaxComplete(function(event, xhr, settings) {
        setTimeout(setInitialProgramStates, 200);
    });

    // Initialize after tab changes on any page
    $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function(e) {
        setTimeout(setInitialProgramStates, 200);
    });

    // Override the loadDataForm function to ensure states are set after loading
    var originalLoadDataForm = window.loadDataForm || function(){};
    window.loadDataForm = function(response) {
        originalLoadDataForm.apply(this, arguments);
        setTimeout(setInitialProgramStates, 200);
    };

    // Установка начального состояния полей при загрузке данных
    // Unified initialization function
function setInitialProgramStates() {
    console.log("Setting initial program states");

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
// Document ready handler to initialize everything
$(document).ready(function() {
    // Set initial states with a slight delay to ensure DOM is fully loaded
    setTimeout(setInitialProgramStates, 200);
    
    // Apply initial states after tab changes
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') === "#othetInformation") {
            console.log("Other Information tab shown - initializing program states");
            setInitialProgramStates();
        }
    });
});
// Override the loadDataForm function to ensure states are set after loading
var originalLoadDataForm = window.loadDataForm || function(){};
window.loadDataForm = function(response) {
    originalLoadDataForm.apply(this, arguments);
    console.log("Form data loaded - setting program states");
    setTimeout(setInitialProgramStates, 200);
};
    // Вызываем функцию при начальной загрузке страницы
    $(document).ready(function() {
        setDataHistoryAttributes();
        setInitialProgramStates();
    });

    // Обновляем вызов после загрузки данных через AJAX
    var originalLoadDataForm = loadDataForm;
    loadDataForm = function(response) {
        originalLoadDataForm.apply(this, arguments);
        // Устанавливаем атрибуты data-history после загрузки данных
        setTimeout(function() {
            setDataHistoryAttributes();
            setInitialProgramStates();
        }, 100);
    };

    // В функции Bim() добавим новый обработчик
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
                               .text('Instrukcja dla tego produktu już istnieje w bazie danych, użyj zakładki до edycji');
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

    // Inside Bim() function, add this handler
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

    // Очистим и установим правильные обработчики форм
    function setupFormHandlers() {
        // Удаляем все существующие обработчики для избежания дублирования
        $(document).off("submit", "form");
        $("#copyForm").off("submit");
        $("#searchedData form").off("submit");
        $("form").off("submit");
        
        // Единственный обработчик для формы копирования
$("#copyForm").on("submit", function (e) {
    e.preventDefault();
    var form = $(this);
    var data = form.serialize() + "&saveData=1";
    
    // Проверяем, заполнено ли поле "Numer dokumentu"
    var documentNrValue = $("#documentNr").val();
    if (!documentNrValue) {
        swal("Ostrzeżenie!", "Proszę wypełnić pole 'Numer dokumentu'.", "warning");
        return false;
    }
    
    // Проверяем статус баркода перед копированием
    var newBarc4Value = $("#newbarc4").val();
    var instruktionStatusMsg = "";
    var barcodeValidMsg = "";
    
    // Находим сообщения о статусе баркода
    $(".instruction-status").each(function() {
        instruktionStatusMsg = $(this).text();
    });
    
    // Находим сообщения о валидности баркода
    $(".new-barcode").find("div:contains('Nie ma takiego barkodu')").each(function() {
        barcodeValidMsg = $(this).text();
    });
    
    // Проверяем наличие ошибок с приоритетом
    if (barcodeValidMsg.indexOf("Nie ma takiego barkodu w bazie danych") > -1) {
        swal("Błąd", "Nie ma takiego barkodu w bazie danych.", "error");
        return false;
    } else if (instruktionStatusMsg.indexOf("Instrukcja dla tego produktu już istnieje") > -1) {
        swal("Błąd", "Instrukcja для tego produktu już istnieje w bazie danych, użyj zakładki do edycji", "error");
        return false;
    }
    
    // Проверяем действие формы и блокируем нежелательные вызовы
    var actionUrl = form.attr("action");
    if (actionUrl.indexOf("action=copy") === -1) {
        console.log("Blokowanie wysyłania formularza z niechcianą akcją: " + actionUrl);
        return false;
    }
    
    console.log("Przesłanie kopii formularza na адрес: " + actionUrl);
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

      

        // Глобальный перехватчик всех отправок форм остается без изменений
        $(document).on("submit", "form", function(e) {
            var form = $(this);
            
            // Если это не форма копирования, проверяем действие
            if (form.attr('id') !== 'copyForm') {
                var actionUrl = form.attr("action") || '';
                
                // Если мы на странице копирования, блокируем все другие формы
                if (window.location.href.indexOf("action=copy") > -1) {
                    console.log("Blokowanie przesyłania formularzy na stronie kopiowania: " + actionUrl);
                    e.preventDefault();
                    return false;
                }
                
                // Блокируем нежелательные действия на странице копирования
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
        
        // Блокировка делегированных событий внутри #searchedData остается без изменений
        $("#searchedData").on("submit", "form", function(e) {
            if (window.location.href.indexOf("action=copy") > -1) {
                console.log("Wewnętrzne blokowanie przesyłania formularzy #searchedData");
                e.preventDefault();
                return false;
            }
        });
    }
    // Обновленная функция для создания шаблона заметки с индикатором статуса и кнопкой сохранения
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
// Обновленный обработчик добавления заметки
$("#searchedData").on("click", ".btn-add-note", function(e) {
    e.preventDefault();
    var barc4 = $("#barc4").val();
    var side = $("#side").val();
    
    // Генерируем временный ID для новой заметки
    var tempId = "temp_" + new Date().getTime();
    
    // Создаем новый элемент заметки с временным ID и статусом "не записано"
    var noteTemplate = getNoteTemplate(tempId, false);
    $("#notes-list").append(noteTemplate);
    
    // Переиндексация элементов
    reindexNotes();
    
    // Анимация добавления
    var newNote = $("#notes-list").last();
    newNote.css("background-color", "#ffeeba"); // Желтый цвет для новой заметки
    setTimeout(function() {
        newNote.css("background-color", "");
    }, 1000);
});
// Обработчик сохранения заметки с записью в историю
$("#searchedData").on("click", ".btn-save-note", function(e) {
    e.preventDefault();
    var noteElement = $(this).closest("li");
    var noteIndex = noteElement.data("note-index");
    var selectedParentNoteId = noteElement.find("select[name='notes[]']").val();
    var barc4 = $("#barc4").val();
    var side = $("#side").val();
    
    // Проверка выбора заметки
    if (!selectedParentNoteId || selectedParentNoteId === "0") {
        alert("Wybierz uwagę z listy");
        return;
    }
    
    // Кнопка и статус
    var saveButton = $(this);
    var statusElement = noteElement.find(".note-status");
    
    // Показываем процесс сохранения
    saveButton.prop('disabled', true).text("Zapisywanie...");
    
    // Отправляем запрос на сервер для сохранения
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
                // Обновляем ID заметки из ответа сервера
                noteElement.find("input[name='id[]']").val(response.id);
                
                // Обновляем статус
                noteElement.attr("data-saved", "true");
                statusElement.removeClass("text-danger").addClass("text-success").text("Zapisano");
                
                // Удаляем кнопку сохранения
                saveButton.remove();
                
                // Подтверждаем успешное сохранение
                noteElement.css("background-color", "#d4edda");
                setTimeout(function() {
                    noteElement.css("background-color", "");
                }, 1000);
                
                // Запись в историю после добавления заметки (только на странице редактирования)
                if (window.location.href.indexOf("action=edit") > -1) {
                    saveEditHistory(barc4, side);
                }
            } else {
                // Обработка ошибки
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
            // Восстанавливаем кнопку
            saveButton.prop('disabled', false).text("Zapisz");
        }
    });
});
    
    // Добавляем новую функцию setupEditFormHandlers внутрь функции Bim()
function setupEditFormHandlers() {
    // Находим форму редактирования
    var editForm = $("form[action*='action=edit']");
    
    // Если мы на странице редактирования и форма найдена
    if (window.location.href.indexOf("action=edit") > -1 && editForm.length) {
        // Удаляем все существующие обработчики для этой формы
        editForm.off("submit");
        
        // Добавляем единственный обработчик для формы редактирования
        editForm.on("submit", function (e) {
            e.preventDefault();
            var form = $(this);
            var data = form.serialize();
            
            // Проверяем статус баркода перед отправкой формы
            var barc4Value = $("#barc4").val();
            var barcodeValidMsg = "";
            
            // Находим сообщения о валидности баркода
            $(".barcode-information").find("div:contains('Nie ma takiego barkodu')").each(function() {
                barcodeValidMsg = $(this).text();
            });
            
            // Проверяем баркод перед отправкой формы
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
                            // Успешное редактирование - записываем историю
                            saveEditHistory($("#barc4").val(), $("#side").val());
                            
                            swal("Sukces!", "Instrukcja została zaktualizowana", "success")
                                .then(function () {
                                    // Опционально - перезагрузка или другое действие
                                    window.location.reload(true);
                                });
                        } else {
                            swal("Błąd", result.message || "Błąd podczas aktualizacji", "error");
                        }
                    } catch (e) {
                        // Если не удалось разобрать JSON, считаем операцию успешной
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
  // Функция для записи истории после копирования
        // Create a standalone saveHistory function outside setupFormHandlers at the same level as saveEditHistory
        // Полная функция saveHistory с учетом заметок для копирования
// Улучшенная функция saveHistory с записью только изменений
// Полная исправленная функция saveHistory
function saveHistory(sourceBarc4, sourceSide, targetBarc4) {
    // Базовый комментарий
    var comment = "Skopiowano z " + sourceBarc4 + " (strona " + sourceSide + ") do " + targetBarc4;
    var changes = [];
    
    // Получаем имя пользователя
    var author = $("#userName").val() ? ($("#userName").val() + " " + $("#userLastName").val()) : "User";
    
    // Отслеживаем уже обработанные поля
    var processedFields = {};
    
    // Карта соответствия имен полей и их удобочитаемых названий
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
    
    // Собираем изменения полей, если есть оригинальные значения
    $('#searchedData').find('input, select, textarea').each(function() {
        var $field = $(this);
        var name = $field.attr('name');
        
        // Пропускаем поля без имени, служебные поля и поля программ печати
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
        
        // Пропускаем машинные программы - обрабатываем их отдельно
        if (name && name.startsWith('machine_program_')) {
            return;
        }
        
        var currentValue = $field.val();
        var originalValue = $field.attr('data-original');
        
        // Для чекбоксов получаем фактическое состояние
        if ($field.attr('type') === 'checkbox') {
            currentValue = $field.is(':checked') ? '1' : '0';
        }
        
        // Если есть оригинальное значение и оно отличается от текущего
        if (originalValue !== undefined && currentValue !== originalValue) {
            var fieldLabel = fieldMappings[name] || name;
            changes.push(fieldLabel + ': ' + originalValue + ' zmieniono na ' + currentValue);
            processedFields[name] = true;
        }
    });
    
    // Обработка машинных программ - используем один формат
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
    
    // Сбор данных о скопированных заметках
    var noteIds = [];
    if ($("#notes-list").length) {
        var notesInfo = [];
        
        $("#notes-list li").each(function() {
            var noteId = $(this).find('input[name="id[]"]').val();
            if (noteId && noteId.toString().indexOf('temp_') == -1) { // Только реальные ID заметок
                noteIds.push(noteId);
                
                var noteText = $(this).find('select[name="notes[]"] option:selected').text().trim();
                var optgroup = $(this).find('select[name="notes[]"] option:selected').closest('optgroup');
                var category = optgroup.length ? optgroup.attr('label') || '' : '';
                
                if (noteText) {
                    notesInfo.push(category ? '[' + category + '] ' + noteText : noteText);
                }
            }
        });
        
        // Добавляем заметки в комментарий, только если они есть
        if (notesInfo.length > 0) {
            // Добавим отметку о скопированных заметках
            changes.push('Skopiowane uwagi:');
            Array.prototype.push.apply(changes, notesInfo);
        }
    }
    
    // Фильтруем изменения, удаляя упоминания программ печати
    changes = changes.filter(function(change) {
        return !change.includes('print_program_') && 
               !change.includes('old_print_prog_') && 
               !change.includes('zmieniono na on') &&
               !change.includes('zmieniono na off');
    });
    
    // Добавляем изменения в комментарий
    if (changes.length > 0) {
        comment += "\nZmienione pola:\n" + changes.join('\n');
        if (comment.length > 2000) {
            comment = comment.substring(0, 1997) + "...";
        }
    }
    
    // Получаем значения всех полей формы для отправки
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
        
        // Добавляем машинные программы
        "MACHINE_PROGRAM_1R": $("[name='machine_program_1r']").val() || '',
        "MACHINE_PROGRAM_2R": $("[name='machine_program_2r']").val() || '',
        "MACHINE_PROGRAM_3R": $("[name='machine_program_3r']").val() || '',
        "MACHINE_PROGRAM_4R": $("[name='machine_program_4r']").val() || '',
        "MACHINE_PROGRAM_1G": $("[name='machine_program_1g']").val() || '',
        "MACHINE_PROGRAM_2G": $("[name='machine_program_2g']").val() || '',
        "MACHINE_PROGRAM_3G": $("[name='machine_program_3g']").val() || ''
    };
    
    // Добавляем ID заметок
    if (noteIds.length > 0) formData.NOTES_ID_1 = noteIds[0];
    if (noteIds.length > 1) formData.NOTES_ID_2 = noteIds[1];
    if (noteIds.length > 2) formData.NOTES_ID_3 = noteIds[2];
    if (noteIds.length > 3) formData.NOTES_ID_4 = noteIds[3];
    
    // Дублируем поля для обратной совместимости
    formData.barc4 = targetBarc4;
    formData.side = sourceSide;
    formData.author = author;
    formData.comments = comment;
    formData.name = formData.NAME_I;
    formData.wpn = formData.WPN;
    formData.devices = formData.LINE;
    
    console.log("Sending copy history data:", formData);
    
    // Отправляем на сервер
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
// Добавьте эту функцию или дополните существующую
function setOriginalFormValues() {
    // Сохраняем начальные значения полей для отслеживания изменений
    $('#searchedData').find('input, select, textarea').each(function() {
        var $field = $(this);
        var name = $field.attr('name');
        
        // Пропускаем поля без имени или служебные поля
        if (!name || name === 'id[]' || name === 'saveData' || name === 'imageNotice' || 
            name === 'notes[]' || name.startsWith('note_')) {
            return;
        }
        
        var value = $field.val() || '';
        
        // Для чекбоксов сохраняем состояние, а не значение
        if ($field.attr('type') === 'checkbox') {
            value = $field.is(':checked') ? '1' : '0';
        }
        
        // Сохраняем начальное значение для отслеживания изменений
        $field.attr('data-original', value);
    });
}

// Вызывайте эту функцию после загрузки данных
$(document).ready(function() {
    // ...существующий код...
    setOriginalFormValues();
});

// И после загрузки формы через AJAX
$(document).ajaxComplete(function(event, xhr, settings) {
    if (settings.url.indexOf("getDataInstructions") > -1) {
        setTimeout(setOriginalFormValues, 100);
    }
});
    // Исправленная функция checkWpn
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
      // Исправленный обработчик событий для driveNumberFields
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

// Fixed saveEditHistory function to prevent duplicated machine programs in comments
// Полная версия функции saveEditHistory с улучшенной обработкой заметок
function saveEditHistory(barc4, side) {
    // Собираем информацию о всех измененных полях
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
    
    // Отслеживаем уже обработанные поля
    var processedFields = {};
    
    // Найдем все поля с измененными значениями
    $('#searchedData').find('input, select, textarea').each(function() {
        var $field = $(this);
        var name = $field.attr('name');
        
        // Пропускаем поля без имени, служебные поля и все поля связанные с программой печати
        if (!name || 
            name === 'id[]' || 
            name === 'saveData' || 
            name === 'imageNotice' || 
            name === 'notes[]' || 
            name.startsWith('note_') || 
            name.startsWith('print_program_') ||  // Пропускаем все чекбоксы программ печати
            name.startsWith('old_print_prog_')) { // Пропускаем все старые поля программ
            return;
        }
        
        // Пропускаем машинные программы - обрабатываем их отдельно
        if (name && name.startsWith('machine_program_')) {
            return;
        }
        
        var currentValue = $field.val();
        var oldValue = $field.attr('data-history');
        
        // Для чекбоксов обрабатываем особым образом
        if ($field.attr('type') === 'checkbox') {
            currentValue = $field.is(':checked') ? '1' : '0';
        }
        
        // Если значение изменилось, добавляем в список изменений
        if (oldValue !== undefined && currentValue !== oldValue) {
            var fieldLabel = fieldMappings[name] || name;
            changes.push(fieldLabel + ': ' + oldValue + ' zmieniono na ' + currentValue);
            processedFields[name] = true;
        }
    });
    
    // Обработка машинных программ - используем только один формат
    $('[name^="machine_program_"]').each(function() {
        var $field = $(this);
        var name = $field.attr('name');
        var lineName = name.replace('machine_program_', '').toUpperCase();
        var currentValue = $field.val() || '';
        var oldValue = $field.attr('data-history') || '';
        
        if (currentValue !== oldValue) {
            // Используем только формат "Program dla maszyn X" для единообразия
            changes.push('Program dla maszyn ' + lineName + ': ' + oldValue + ' zmieniono na ' + currentValue);
            processedFields[name] = true;
        }
    });
    
    // ПОЛНОСТЬЮ УБИРАЕМ обработку чекбоксов программ печати - не включаем их в комментарий
    
    // Улучшенная обработка изменений заметок
    if ($("#notes-list").length) {
        var currentNotes = [];
        var oldNotesData = [];
        
        // Получаем текущие заметки с их ID и текстом
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
        
        // Получаем предыдущие данные о заметках
        if ($("#notes-list").attr('data-history-notes')) {
            try {
                oldNotesData = JSON.parse($("#notes-list").attr('data-history-notes')) || [];
            } catch (e) {
                oldNotesData = [];
                console.error("Error parsing notes history:", e);
            }
        }
        
        // Находим добавленные заметки
        var addedNotes = currentNotes.filter(function(note) {
            return !oldNotesData.some(function(oldNote) {
                return oldNote.id === note.id;
            });
        });
        
        // Находим удаленные заметки
        var removedNotes = oldNotesData.filter(function(oldNote) {
            return !currentNotes.some(function(note) {
                return note.id === oldNote.id;
            });
        });
        
        // Добавляем информацию о добавленных заметках в историю
        if (addedNotes.length > 0) {
            var addedNotesDetails = [];
            addedNotes.forEach(function(note) {
                addedNotesDetails.push(note.text);
            });
            changes.push('Dodano uwagi (' + addedNotes.length + '): \n' + addedNotesDetails.join('\n'));
        }
        
        // Добавляем информацию об удаленных заметках
        if (removedNotes.length > 0) {
            var removedNotesDetails = [];
            removedNotes.forEach(function(note) {
                removedNotesDetails.push(note.text);
            });
            changes.push('Usunięto uwagi (' + removedNotes.length + '): \n' + removedNotesDetails.join('\n'));
        }
        
        // Обновляем историю заметок после изменений
        $("#notes-list").attr('data-history-notes', JSON.stringify(currentNotes));
    }
    
    // Остальной код функции без изменений...
    var comment = "Zaktualizowana instrukcja dla " + barc4 + " (strona " + side + ")";
    if (changes.length > 0) {
        comment += "\nZmienione pola:\n" + changes.join('\n');
        if (comment.length > 2000) {
            comment = comment.substring(0, 1997) + "...";
        }
    }
    
    // Получаем имя пользователя
    var author = $("#userName").val() ? ($("#userName").val() + " " + $("#userLastName").val()) : "User";
    
    // Получаем ID заметок
    var noteIds = [];
    if ($("#notes-list").length) {
        $("#notes-list li").each(function() {
            var noteId = $(this).find('input[name="id[]"]').val();
            if (noteId) {
                noteIds.push(noteId);
            }
        });
    }
    
    // Получаем СТАРЫЕ значения для сохранения в истории
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
    
    // Получаем СТАРЫЕ значения машинных программ
    var oldMachinePrograms = {
        "MACHINE_PROGRAM_1R": $("[name='machine_program_1r']").attr('data-history') || '',
        "MACHINE_PROGRAM_2R": $("[name='machine_program_2r']").attr('data-history') || '',
        "MACHINE_PROGRAM_3R": $("[name='machine_program_3r']").attr('data-history') || '',
        "MACHINE_PROGRAM_4R": $("[name='machine_program_4r']").attr('data-history') || '',
        "MACHINE_PROGRAM_1G": $("[name='machine_program_1g']").attr('data-history') || '',
        "MACHINE_PROGRAM_2G": $("[name='machine_program_2g']").attr('data-history') || '',
        "MACHINE_PROGRAM_3G": $("[name='machine_program_3g']").attr('data-history') || ''
    };
    
    // Создаем данные истории со СТАРЫМИ значениями
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
        
        // Добавляем машинные программы со СТАРЫМИ значениями
        "MACHINE_PROGRAM_1R": oldMachinePrograms.MACHINE_PROGRAM_1R,
        "MACHINE_PROGRAM_2R": oldMachinePrograms.MACHINE_PROGRAM_2R,
        "MACHINE_PROGRAM_3R": oldMachinePrograms.MACHINE_PROGRAM_3R,
        "MACHINE_PROGRAM_4R": oldMachinePrograms.MACHINE_PROGRAM_4R,
        "MACHINE_PROGRAM_1G": oldMachinePrograms.MACHINE_PROGRAM_1G,
        "MACHINE_PROGRAM_2G": oldMachinePrograms.MACHINE_PROGRAM_2G,
        "MACHINE_PROGRAM_3G": oldMachinePrograms.MACHINE_PROGRAM_3G
    };
    
    // Добавляем ID заметок
    if (noteIds.length > 0) postData.NOTES_ID_1 = noteIds[0];
    if (noteIds.length > 1) postData.NOTES_ID_2 = noteIds[1];
    if (noteIds.length > 2) postData.NOTES_ID_3 = noteIds[2];
    if (noteIds.length > 3) postData.NOTES_ID_4 = noteIds[3];
    
    // Дублируем поля для обратной совместимости
    postData.barc4 = barc4;
    postData.side = side;
    postData.author = author;
    postData.comments = comment;
    postData.name = oldValues.NAME_I;
    postData.wpn = oldValues.WPN;
    postData.devices = $("#devices").val() || '';
    
    console.log("Sending history data with OLD values:", postData);
    
    // Отправляем на сервер
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
function handleCopyPageNotes() {
    // Проверяем, находимся ли мы на странице копирования
    if (window.location.href.indexOf("action=copy") > -1 || $("#copyForm").length > 0) {
        console.log("Обнаружена страница копирования - скрываем кнопки управления заметками");
        
        // Скрываем кнопки добавления и удаления заметок
        $(".btn-add-note").hide();
        $(".btn-remove-note").hide();
        $(".btn-save-note").hide();
        
        // Добавляем предупреждение, если его еще нет
        if ($("#notes-warning").length === 0 && $("#notes-list").length > 0) {
            var warningMessage = $('<div id="notes-warning" class="alert alert-danger" style="color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 10px; margin-bottom: 15px;">' +
                '<strong>Uwaga!</strong> Aby edytować uwagi, należy najpierw utworzyć instrukcję dla nowego barkodu, ' +
                'a następnie użyć zakładки edycji.' +
                '</div>');
            
            // Добавляем предупреждение перед списком заметок
            $("#notes-list").before(warningMessage);
            
            // Добавляем подсказку "только для чтения" к каждой заметке
            $("#notes-list li").each(function() {
                $(this).find('select').prop('disabled', true);
                $(this).find('input[type="file"]').prop('disabled', true);
                $(this).css('opacity', '0.7');
            });
        }
    }
}
 
    // Важно: вызываем setupFormHandlers и setupEditFormHandlers при загрузке документа
    $(document).ready(function() {
        setupFormHandlers();
        setupEditFormHandlers();
        handleCopyPageNotes();
        setOriginalFormValues(); // Вызываем setOriginalFormValues при загрузке страницы
    });

    // Добавим перехватчик всех AJAX запросов для повторной настройки обработчиков после их завершения
    $(document).ajaxComplete(function() {
        setupFormHandlers();
        setupEditFormHandlers();
        handleCopyPageNotes();
    });
    $(document).on('keydown', 'form', function(event) {
        if (event.key === "Enter") {
            return false;
        }
    });
}

var bim = new Bim();