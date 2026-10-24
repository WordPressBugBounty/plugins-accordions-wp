jQuery(document).ready(function($) {

    var previewTimer;

    function fetchExactFrontendPreview() {
        var $canvas = $('#tc-exact-preview-canvas');
        var $loader = $('#tc-preview-loading');
        var postId  = $('#post_ID').val();

        // 1. Serialize all form fields inside the metabox dynamically
        var formData = $('#tc-tabs-container input, #tc-tabs-container select, #tc-tabs-container textarea').serialize();

        $loader.show();
        $canvas.css('opacity', '0.5');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tcaccordion_get_live_preview',
                security: $('#tcaccordion_nonce_field').val(),
                post_id: postId,
                form_data: formData // Pass live unsaved input data
            },
            success: function(response) {
                $loader.hide();
                $canvas.css('opacity', '1');

                if (response.success) {
                    $canvas.html(response.data.html);

                    // Re-trigger color/theme JS initialization scripts if needed
                    if (typeof initTCAccordionFrontend === 'function') {
                        initTCAccordionFrontend();
                    }
                } else {
                    $canvas.html('<p>' + response.data.message + '</p>');
                }
            },
            error: function() {
                $loader.hide();
                $canvas.css('opacity', '1');
                $canvas.html('<p>Error rendering live preview.</p>');
            }
        });
    }

    // Trigger preview fetch when switching to the Live Preview tab
    $(document).on('click', '.tc-tab-link[href="#tab-accordion-preview"]', function() {
        fetchExactFrontendPreview();
    });

    // Manual Refresh Button Click
    $(document).on('click', '#tc-refresh-preview-btn', function(e) {
        e.preventDefault();
        fetchExactFrontendPreview();
    });

    // Debounced automatic reload when metabox inputs change
    $(document).on('input change', '#tc-tabs-container input, #tc-tabs-container select, #tc-tabs-container textarea', function() {
        if ($('#tab-accordion-preview').hasClass('active')) {
            clearTimeout(previewTimer);
            previewTimer = setTimeout(fetchExactFrontendPreview, 600);
        }
    });


    /**
     * Unique, always-increasing id for new item rows (safe for many items created in one millisecond)
     */
    var lastItemId = 0;
    function nextItemId() {
        var now = new Date().getTime();
        lastItemId = now > lastItemId ? now : lastItemId + 1;
        return lastItemId;
    }

    /**
     * Helper to check and enforce maximum item limits (Free vs Pro)
     */
    function checkItemLimit() {
        var count = $('#tcaccordion-sortable .tcaccordion-item').length;
        if (typeof tcAccordion !== 'undefined' && tcAccordion.maxItems > 0 && count >= tcAccordion.maxItems) {
            $('#tcaccordion-add').prop('disabled', true);
        } else {
            $('#tcaccordion-add').prop('disabled', false);
        }
    }

    /**
     * Helper to re-initialize TinyMCE on a specific element or container
     */
    function initEditor($container) {
        $container.find('.wp-editor-area').each(function() {
            var $textarea = $(this);
            var id = $textarea.attr('id');
            if (!id) return;

            // 1. Remove existing TinyMCE instance if present
            if (typeof tinyMCE !== 'undefined' && tinyMCE.get(id)) {
                tinyMCE.execCommand('mceRemoveEditor', false, id);
            }

            // 2. Clean up duplicate wrapper markup injected by templates or old instances
            var $wrap = $textarea.closest('.wp-editor-wrap');
            if ($wrap.length) {
                var content = $textarea.val();
                // Replace the bloated wrapper with just the raw textarea to start fresh
                $wrap.replaceWith($textarea);
                $textarea.val(content);
                // Assign a fresh unique ID if it conflicts
                id = 'tc_desc_' + new Date().getTime() + Math.floor(Math.random() * 1000);
                $textarea.attr('id', id);
            }

            // 3. Re-initialize Quicktags (HTML Tab)
            if (typeof quicktags !== 'undefined') {
                try {
                    quicktags({ id: id });
                    QTags._init();
                } catch(e) {}
            }

            // 4. Re-initialize TinyMCE (Visual Tab) cleanly
            if (typeof tinyMCE !== 'undefined' && typeof wp !== 'undefined' && wp.editor) {
                wp.editor.initialize(id, {
                    tinymce: {
                        wpautop: true,
                        plugins: 'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern',
                        toolbar1: 'bold,italic,underline,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv',
                        textarea_rows: 6
                    },
                    quicktags: true
                });
            }
        });
    }

    /**
     * 1. Initialize Sortable with TinyMCE Destruction Hooks
     */
    if ($('#tcaccordion-sortable').length) {
        $('#tcaccordion-sortable').sortable({
            handle: '.tcaccordion-header',
            items: '.tcaccordion-item',
            placeholder: 'tcaccordion-placeholder',
            start: function(event, ui) {
                // Before drag starts: Save editor content to raw textarea & remove TinyMCE instances
                ui.item.find('.wp-editor-area').each(function() {
                    var id = $(this).attr('id');
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get(id)) {
                        tinyMCE.get(id).save(); // Sync visual editor content to hidden textarea
                        tinyMCE.execCommand('mceRemoveEditor', false, id);
                    }
                });
            },
            stop: function(event, ui) {
                // After drag completes: Re-initialize TinyMCE on the dragged item
                initEditor(ui.item);
                reindexItems();
            }
        });
    }

    /**
     * 2. Re-index Input Names and IDs After Sorting or Removing
     */
    function reindexItems() {
        $('#tcaccordion-sortable .tcaccordion-item').each(function(index) {
            var $item = $(this);

            // Re-index every field of the item (title, description, nested accordion...)
            $item.find('[name^="custom_accordion_wordpresspro_columns["]').each(function() {
                var name = $(this).attr('name');
                $(this).attr('name', name.replace(/^custom_accordion_wordpresspro_columns\[[^\]]*\]/, 'custom_accordion_wordpresspro_columns[' + index + ']'));
            });
        });
        checkItemLimit();
    }

    /**
     * 3. Add New Accordion Item
     */
    $(document).on('click', '#tcaccordion-add', function(e) {
        e.preventDefault();

        var count = $('#tcaccordion-sortable .tcaccordion-item').length;
        if (typeof tcAccordion !== 'undefined' && tcAccordion.maxItems > 0 && count >= tcAccordion.maxItems) {
            alert(tcAccordion.limitText);
            return;
        }

        var template = $('#tcaccordion-template').html();
        var newIndex = nextItemId(); // Unique timestamp key
        var itemHtml = template.replace(/__INDEX__/g, newIndex);

        var $newItem = $(itemHtml);
        $('#tcaccordion-sortable').append($newItem);

        // Initialize editor for newly created row
        initEditor($newItem);
        checkItemLimit();
    });

    /**
     * 3b. Duplicate Item
     */
    $(document).on('click', '.tcaccordion-duplicate', function(e) {
        e.preventDefault();

        var $item = $(this).closest('.tcaccordion-item');

        var count = $('#tcaccordion-sortable .tcaccordion-item').length;
        if (typeof tcAccordion !== 'undefined' && tcAccordion.maxItems > 0 && count >= tcAccordion.maxItems) {
            alert(tcAccordion.limitText);
            return;
        }

        // Read the current values (sync the visual editor into its textarea first)
        var $textarea = $item.find('.wp-editor-area').first();
        var editorId  = $textarea.attr('id');
        if (typeof tinyMCE !== 'undefined' && editorId) {
            var editor = tinyMCE.get(editorId);
            if (editor && !editor.isHidden()) {
                editor.save();
            }
        }

        var title       = $item.find('.tcaccordion-title').val() || '';
        var description = $textarea.val() || '';
        var copyText    = (typeof tcAccordion !== 'undefined' && tcAccordion.copyText) ? tcAccordion.copyText : 'Copy';
        var newTitle    = title ? title + ' (' + copyText + ')' : '';

        // Build the new row from the template
        var template = $('#tcaccordion-template').html();
        var newIndex = nextItemId();
        var $newItem = $(template.replace(/__INDEX__/g, newIndex));

        $newItem.find('.tcaccordion-title').val(newTitle);
        $newItem.find('.tcaccordion-label').text(newTitle ? newTitle : 'New Accordion');
        $newItem.find('.wp-editor-area').val(description);

        // Copy every extra per-item field (for example nested accordion, group, icon, badge).
        $item.find('[name^="custom_accordion_wordpresspro_columns["]').each(function() {
            var match = /\]\[([^\]]+)\]$/.exec($(this).attr('name') || '');
            if (!match || match[1] === 'title' || match[1] === 'description') {
                return;
            }
            $newItem.find('[name$="][' + match[1] + ']"]').val($(this).val());
        });

        // Insert right after the original, then start its editor
        $item.after($newItem);
        initEditor($newItem);
        reindexItems();

        // The original may be collapsed, so open the copy and bring it into view.
        $newItem.find('.tcaccordion-body').show();
        if ($newItem[0] && $newItem[0].scrollIntoView) {
            $newItem[0].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }

        $newItem.find('.tcaccordion-title').trigger('focus');
    });

    /**
     * 3c. CSV Import / Export (runs in the browser, so unsaved edits are included)
     */
    var CSV_TITLE_KEYS = ['title', 'question', 'heading', 'name', 'post_title', 'q', 'accordion title'];
    var CSV_DESC_KEYS  = ['description', 'answer', 'content', 'post_content', 'body', 'details', 'a', 'text'];

    function i18nText(key, fallback) {
        return (typeof tcAccordion !== 'undefined' && tcAccordion.i18n && tcAccordion.i18n[key]) ? tcAccordion.i18n[key] : fallback;
    }

    function csvNotice(message) {
        $('#tcaccordion-csv-notice').text(message).show();
    }

    function escapeHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // Plain text descriptions become paragraphs; descriptions that already contain HTML are kept as they are.
    function formatDescription(text) {
        text = String(text || '').replace(/\r\n?/g, '\n').trim();
        if (text === '' || /<\/?[a-z][\s\S]*>/i.test(text)) {
            return text;
        }
        return text.split(/\n{2,}/).map(function(part) {
            return '<p>' + escapeHtml(part).replace(/\n/g, '<br>') + '</p>';
        }).join('\n');
    }

    function csvDetectDelimiter(text) {
        var firstLine = text.split(/\r?\n/)[0] || '';
        var best = ',';
        var bestCount = 0;
        [',', ';', '\t'].forEach(function(delimiter) {
            var count = firstLine.split(delimiter).length - 1;
            if (count > bestCount) {
                best = delimiter;
                bestCount = count;
            }
        });
        return best;
    }

    // Small RFC 4180 parser: quoted fields, doubled quotes and line breaks inside quotes.
    function parseCSV(text) {
        if (text.charCodeAt(0) === 0xFEFF) {
            text = text.slice(1);
        }

        var delimiter = csvDetectDelimiter(text);
        var rows = [];
        var row = [];
        var field = '';
        var inQuotes = false;

        for (var i = 0; i < text.length; i++) {
            var ch = text.charAt(i);

            if (inQuotes) {
                if (ch === '"') {
                    if (text.charAt(i + 1) === '"') {
                        field += '"';
                        i++;
                    } else {
                        inQuotes = false;
                    }
                } else {
                    field += ch;
                }
            } else if (ch === '"' && field === '') {
                inQuotes = true;
            } else if (ch === delimiter) {
                row.push(field);
                field = '';
            } else if (ch === '\n' || ch === '\r') {
                if (ch === '\r' && text.charAt(i + 1) === '\n') {
                    i++;
                }
                row.push(field);
                rows.push(row);
                row = [];
                field = '';
            } else {
                field += ch;
            }
        }

        if (field !== '' || row.length) {
            row.push(field);
            rows.push(row);
        }

        // Drop completely empty rows
        return rows.filter(function(r) {
            return r.some(function(cell) { return String(cell).trim() !== ''; });
        });
    }

    function csvQuote(value) {
        return '"' + String(value).replace(/"/g, '""') + '"';
    }

    function downloadCSV(filename, rows) {
        // The BOM makes Excel open UTF-8 text (Bengali, Arabic, accents...) correctly.
        var csv  = '\uFEFF' + rows.map(function(r) { return r.map(csvQuote).join(','); }).join('\r\n');
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var url  = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(function() { URL.revokeObjectURL(url); }, 1000);
    }

    function csvFilename() {
        var name = ($('#title').val() || 'accordion').toLowerCase().replace(/[^\w\u0080-\uFFFF]+/g, '-').replace(/^-+|-+$/g, '');
        return (name || 'accordion') + '-items.csv';
    }

    function collectItemRows() {
        var rows = [['title', 'description']];

        $('#tcaccordion-sortable .tcaccordion-item').each(function() {
            var $item     = $(this);
            var $textarea = $item.find('.wp-editor-area').first();
            var editorId  = $textarea.attr('id');

            if (typeof tinyMCE !== 'undefined' && editorId) {
                var editor = tinyMCE.get(editorId);
                if (editor && !editor.isHidden()) {
                    editor.save();
                }
            }

            rows.push([$item.find('.tcaccordion-title').val() || '', $textarea.val() || '']);
        });

        return rows;
    }

    function clearAllItems() {
        $('#tcaccordion-sortable .tcaccordion-item').each(function() {
            $(this).find('.wp-editor-area').each(function() {
                var id = $(this).attr('id');
                if (typeof tinyMCE !== 'undefined' && id && tinyMCE.get(id)) {
                    tinyMCE.execCommand('mceRemoveEditor', false, id);
                }
            });
            $(this).remove();
        });
    }

    function appendImportedItem(item) {
        var template = $('#tcaccordion-template').html();
        var $newItem = $(template.replace(/__INDEX__/g, nextItemId()));

        $newItem.find('.tcaccordion-title').val(item.title);
        $newItem.find('.tcaccordion-label').text(item.title ? item.title : 'New Accordion');
        $newItem.find('.wp-editor-area').val(item.description);

        $('#tcaccordion-sortable').append($newItem);

        if ($newItem.is(':first-child')) {
            // The first item is shown open, so it gets its editor right away.
            initEditor($newItem);
        } else {
            // Large imports would freeze the page with dozens of editors, so the others
            // start as plain textareas and get the full editor when the item is opened.
            var $textarea = $newItem.find('.wp-editor-area');
            var $wrap     = $textarea.closest('.wp-editor-wrap');
            if ($wrap.length) {
                $wrap.replaceWith($textarea);
            }
            $textarea.val(item.description)
                .attr('id', 'tc_desc_' + nextItemId())
                .css({ display: 'block', width: '100%', minHeight: '140px' });
            $newItem.attr('data-lazy-editor', '1');
        }
    }

    function importRows(rows) {
        if (!rows.length) {
            alert(i18nText('noRows', 'No items were found in this file.'));
            return;
        }

        var header   = rows[0].map(function(cell) { return String(cell).trim().toLowerCase(); });
        var titleIdx = -1;
        var descIdx  = -1;

        header.forEach(function(name, i) {
            if (titleIdx === -1 && CSV_TITLE_KEYS.indexOf(name) !== -1) {
                titleIdx = i;
            } else if (descIdx === -1 && CSV_DESC_KEYS.indexOf(name) !== -1) {
                descIdx = i;
            }
        });

        var dataRows;
        if (titleIdx === -1 && descIdx === -1) {
            // No recognizable header: use the first two columns, every row is an item.
            titleIdx = 0;
            descIdx  = 1;
            dataRows = rows;
        } else {
            dataRows = rows.slice(1);
        }

        var items = [];
        dataRows.forEach(function(r) {
            var title = (titleIdx > -1 && r[titleIdx] !== undefined) ? String(r[titleIdx]).trim() : '';
            var desc  = (descIdx > -1 && r[descIdx] !== undefined) ? String(r[descIdx]) : '';

            if (title === '' && desc.trim() === '') {
                return;
            }
            items.push({ title: title, description: formatDescription(desc) });
        });

        if (!items.length) {
            alert(i18nText('noRows', 'No items were found in this file.'));
            return;
        }

        var replace = $('#tcaccordion-import-mode').val() === 'replace';
        if (replace && $('#tcaccordion-sortable .tcaccordion-item').length && !window.confirm(i18nText('confirmReplace', 'Replace all existing items?'))) {
            return;
        }

        if (replace) {
            clearAllItems();
        }

        var existing = $('#tcaccordion-sortable .tcaccordion-item').length;
        var maxItems = (typeof tcAccordion !== 'undefined') ? tcAccordion.maxItems : 0;
        var limited  = false;

        if (maxItems > 0) {
            var room = Math.max(0, maxItems - existing);
            if (items.length > room) {
                items = items.slice(0, room);
                limited = true;
            }
        }

        items.forEach(appendImportedItem);
        reindexItems();

        var total   = $('#tcaccordion-sortable .tcaccordion-item').length;
        var message = i18nText('imported', '%d items imported.').replace('%d', items.length);

        if (limited) {
            message += ' ' + i18nText('limited', 'Only %d items could be imported because of the item limit.').replace('%d', items.length);
        }
        if (total > 200) {
            message += ' ' + i18nText('largeWarning', '');
        }

        csvNotice(message);
    }

    $(document).on('click', '#tcaccordion-import-btn', function(e) {
        e.preventDefault();
        $('#tcaccordion-import-file').val('').trigger('click');
    });

    $(document).on('change', '#tcaccordion-import-file', function() {
        var file = this.files && this.files[0];
        if (!file) {
            return;
        }

        var reader = new FileReader();
        reader.onload = function(event) {
            importRows(parseCSV(String(event.target.result || '')));
        };
        reader.onerror = function() {
            alert(i18nText('readError', 'The file could not be read.'));
        };
        reader.readAsText(file, 'UTF-8');
    });

    $(document).on('click', '#tcaccordion-export-btn', function(e) {
        e.preventDefault();
        downloadCSV(csvFilename(), collectItemRows());
    });

    $(document).on('click', '#tcaccordion-sample-btn', function(e) {
        e.preventDefault();
        downloadCSV('accordion-sample.csv', [
            ['title', 'description'],
            ['What is your return policy?', '<p>You can return any item within 30 days of delivery.</p>'],
            ['Do you ship internationally?', '<p>Yes, we ship to most countries. Delivery takes 5-10 business days.</p>']
        ]);
    });

    /**
     * 4. Remove Item
     */
    $(document).on('click', '.tcaccordion-remove', function(e) {
        e.preventDefault();

        if (!window.confirm(i18nText('confirmRemove', 'Remove this item?'))) {
            return;
        }

        var $item = $(this).closest('.tcaccordion-item');

        $item.find('.wp-editor-area').each(function() {
            var id = $(this).attr('id');
            if (typeof tinyMCE !== 'undefined' && tinyMCE.get(id)) {
                tinyMCE.execCommand('mceRemoveEditor', false, id);
            }
        });

        $item.remove();
        reindexItems();
    });

    /**
     * 5. Accordion Toggle UI
     */
    $(document).on('click', '.tcaccordion-header', function(e) {
        if ($(e.target).hasClass('tcaccordion-title') || $(e.target).is('input')) {
            return;
        }
        // Duplicate / Remove buttons in the header must not open or close the item.
        if ($(e.target).closest('.tcaccordion-header-actions').length) {
            return;
        }
        $(this).next('.tcaccordion-body').slideToggle(200);

        // Imported items start as plain textareas; give them the full editor on first open.
        var $lazyItem = $(this).closest('.tcaccordion-item');
        if ($lazyItem.attr('data-lazy-editor')) {
            $lazyItem.removeAttr('data-lazy-editor');
            initEditor($lazyItem);
        }
    });

    /**
     * 6. Title Syncing to Header Label
     */
    $(document).on('keyup change', '.tcaccordion-title', function() {
        var val = $(this).val();
        var $label = $(this).closest('.tcaccordion-item').find('.tcaccordion-label');
        $label.text(val ? val : 'New Accordion');
    });

    // Save TinyMCE content directly to textareas before parent form submit
    $('form#post').on('submit', function() {
        if (typeof tinyMCE !== 'undefined') {
            tinyMCE.triggerSave();
        }
    });

    // Tab switching event
    $('.tc-tabs-menu .tc-tab-link').on('click', function(e) {
        e.preventDefault();
        var targetTab = $(this).attr('href');

        // Set value in hidden input field for form post
        $('#tc_active_tab_input').val(targetTab);

        // Active class update
        $(this).parent().addClass('current').siblings().removeClass('current');
        $('.tc-tab-content').removeClass('active');
        $(targetTab).addClass('active');
    });

    // Initialize WP Color Picker
    if ($.fn.wpColorPicker) {
        $('.tc-color-field').wpColorPicker();
    }


    $(document).on('change', '.tcacc-icon-selector input[type="radio"]', function () {
        const $parentGroup = $(this).closest('.tcacc-icon-selector');
        
        // Remove active class from siblings and add to checked parent
        $parentGroup.find('.tcacc-icon-btn').removeClass('active');
        $(this).closest('.tcacc-icon-btn').addClass('active');

        // Trigger live preview update if active
        if (typeof triggerLivePreview === 'function') {
            triggerLivePreview();
        }
    });

    checkItemLimit();
});