(function ($, editor) {

    $.extend(editor, {

        init: function () {

            $("#meta-table").click(function (e) {
                if ($(e.target).closest(".deletemeta").length) {
                    $(e.target).closest("tr").remove();
                }
            });

            $(".addmeta").click(function () {

                var $newMeta = "<tr>" +
                    "<th>" +
                    "<input type='text' class='input' name='newmetakey[]' placeholder='" + $(".metakeyplaceholder").text() + "'>" +
                    "</th>" +
                    "<td>" +
                    "<input type='text' class='regular-text' name='newmetavalue[]' placeholder='" + $(".metavalueplaceholder").text() + "'>" +
                    " <span class=\"row-actions\"><span class=\"delete\"><a style=\"text-decoration: none\" href=\"javascript:void(0)\" class=\"deletemeta\"><span class=\"dashicons dashicons-trash\"></span></a></span></span>\n" +
                    "</td>" +
                    "</tr>";
                $("#meta-table").find("tbody").prepend($newMeta);

            });

            $(".create-user-account").click(function () {
                $("#create-user-form").submit();
            });

            $(".nav-tab").click(function (e) {

                var $tab = $(this);

                $(".nav-tab").removeClass("nav-tab-active");
                $tab.addClass("nav-tab-active");

                $(".tab-content-wrapper").addClass("hidden");
                $("#" + $tab.attr("id") + "_content").removeClass("hidden");

                $("#active-tab").val($tab.attr("id").replace("tab_", ""));
                document.cookie = "gh_contact_tab=" + $tab.attr("id") + ";path=/;";

            });

            $(document).on( 'click', ".edit-notes", function (e) {
                var $note = get_note(e.target);
                $note.find( '.gh-note-view' ).hide();
                $note.find( '.gh-note-edit' ).show();
            });

            $(document).on( 'click', ".cancel-note-edit", function (e) {
                var $note = get_note(e.target);
                $note.find( '.gh-note-edit' ).hide();
                $note.find( '.gh-note-view' ).show();
            });

            $(document).on( 'click', ".save-note", function (e) {
                var $note = get_note(e.target);
                var note_id = $note.attr("id");
                save_note(note_id);
            });

            $(document).on("click", ".delete-note", function (e) {
                var $note = get_note(e.target);
                var note_id = $note.attr("id");
                delete_note(note_id);
            });

            $("#add-note").click(function (event) {
                add_note();
            });

        }
    });

    /**
     * Add a new note
     */
    function add_note() {

        var $newNote = $("#add-new-note");
        var $notes = $("#gh-notes");

        adminAjaxRequest(
            {
                action: "groundhogg_add_notes",
                note: $newNote.val(),
                contact: editor.contact_id
            },
            function callback(response) {
                // Handler
                if (response.success) {
                    $newNote.val("");
                    $notes.prepend(response.data.note);
                } else {
                    alert(response.data);
                }
            }
        );
    }

    /**
     * Save the edited note...
     *
     * @param note_id
     */
    function save_note( note_id ) {

        var $note = $("#" + note_id);
        var new_note_text = $note.find(".edited-note-text").val();
        showSpinner();

        adminAjaxRequest(
            {
                action: "groundhogg_edit_notes",
                note: new_note_text,
                note_id: note_id
            },
            function callback(response) {
                // Handler
                hideSpinner();
                if (response.success) {
                    $note.replaceWith( response.data.note );
                } else {
                    alert(response.data);
                }
            }
        );
    }

    /**
     * Delete a note
     *
     * @param note_id
     */
    function delete_note(note_id) {

        if (!confirm(editor.delete_note_text)) {
            return;
        }

        var $note = $("#" + note_id);

        adminAjaxRequest(
            {
                action: "groundhogg_delete_notes",
                note_id: note_id
            },
            function callback(response) {
                // Handler
                if (response.success) {
                    $note.remove();
                } else {
                    alert(response.data);
                }
            }
        );
    }

    /**
     *
     * Get the note
     *
     * @param e
     * @returns {any | Element | jQuery}
     */
    function get_note(e) {
        return $(e).closest(".gh-note");
    }

    $(function () {
        editor.init();
    });

})(jQuery, ContactEditor);