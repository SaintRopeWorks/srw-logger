<script>
document.addEventListener('DOMContentLoaded', function() {
    var editor = document.getElementById('srw-rich-editor');
    var hiddenInput = document.getElementById('srw_log_format_string');
    var selector = document.getElementById('srw-token-selector');
    var preview = document.getElementById('srw-live-preview');
    var modal = document.getElementById('srw-token-modal');
    var subSelector = document.getElementById('srw-pattern-sub-selector');
    var form = document.getElementById('srw-logger-form');
    var activeEditingTokenElement = null;
    var lastSavedRange = null;

    var formatOptions = {
        'DATE': [
            { value: 'Y-m-d', label: 'YYYY-MM-DD (e.g. 2026-07-18)' },
            { value: 'Y-M-d', label: 'YYYY-MMM-DD (e.g. 2026-Jul-18)' },
            { value: 'm/d/Y', label: 'MM/DD/YYYY (e.g. 07/18/2026)' },
            { value: 'd-m-Y', label: 'DD-MM-YYYY (e.g. 18-07-2026)' }
        ],
        'TIME': [
            { value: 'H:i:s.v', label: 'HH:MM:SS.mmm (24hr + Milliseconds)' },
            { value: 'H:i:s', label: 'HH:MM:SS (24hr Default)' },
            { value: 'h:i:s a', label: 'HH:MM:SS am/pm (12hr format)' }
        ]
    };

    function saveSelectionRange() {
        var selection = window.getSelection();
        if (selection.rangeCount > 0) {
            var range = selection.getRangeAt(0);
            if (editor.contains(range.commonAncestorContainer)) {
                lastSavedRange = range.cloneRange();
            }
        }
    }

    editor.addEventListener('keyup', saveSelectionRange);
    editor.addEventListener('click', saveSelectionRange);
    editor.addEventListener('mouseup', saveSelectionRange);

    function parseStringToEditor(str) {
        var regex = /\{([A-Za-z_]+)(?::([^}]+))?\}/g;
        var html = str.replace(regex, function(match, token, param) {
            var displayParam = param ? '&lt;' + param + '&gt;' : '';
            return '<span class="srw-token" contenteditable="false" data-token="' + token + '" data-param="' + (param || '') + '">' + token + displayParam + '</span>';
        });
        editor.innerHTML = html;
    }

    function syncEditorToString() {
        var tempDiv = document.createElement('div');
        tempDiv.innerHTML = editor.innerHTML;
        
        tempDiv.querySelectorAll('.srw-token').forEach(function(el) {
            var token = el.getAttribute('data-token');
            var param = el.getAttribute('data-param');
            var replacement = param ? '{' + token + ':' + param + '}' : '{' + token + '}';
            el.parentNode.replaceChild(document.createTextNode(replacement), el);
        });
        
        var cleanString = tempDiv.textContent || tempDiv.innerText || '';
        cleanString = cleanString.replace(/\u00A0/g, ' ');
        hiddenInput.value = cleanString;
        updateLivePreview(cleanString);
    }

    function updateLivePreview(str) {
        var siteName = typeof srw_site_name !== 'undefined' ? srw_site_name : 'WordPress';
        var sample = str;
        
        sample = sample.replace(/\{SITENAME\}/g, siteName);
        sample = sample.replace(/\{LEVEL\}/g, "INFO");
        sample = sample.replace(/\{CONTEXT\}/g, "functions.php:42 -> id_exists_in_siblings()");
        sample = sample.replace(/\{MESSAGE\}/g, "checking if siblings have the target id Id: 7");
        
        sample = sample.replace(/\{DATE:([^}]+)\}/g, function(m, p) {
            if (p.includes('M')) return "2026-Jul-18";
            if (p.includes('/')) return "07/18/2026";
            return "2026-07-18";
        });
        
        sample = sample.replace(/\{TIME:([^}]+)\}/g, function(m, p) {
            if (p.includes('.v')) return "17:17:00.123";
            if (p.includes('a')) return "05:17:00 pm";
            return "17:17:00";
        });

        preview.textContent = sample;
    }

    selector.addEventListener('change', function() {
        var token = this.value;
        if (!token) return;

        var defaultParam = '';
        var displayParam = '';
        if (token === 'DATE') { defaultParam = 'Y-m-d'; displayParam = '&lt;Y-m-d&gt;'; }
        if (token === 'TIME') { defaultParam = 'H:i:s.v'; displayParam = '&lt;H:i:s.v&gt;'; }

        var tokenHtml = '<span class="srw-token" contenteditable="false" data-token="' + token + '" data-param="' + defaultParam + '">' + token + displayParam + '</span>';
        
        editor.focus();

        if (lastSavedRange) {
            var selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(lastSavedRange);
            
            lastSavedRange.deleteContents();
            var el = document.createElement("div");
            el.innerHTML = tokenHtml;
            var frag = document.createDocumentFragment(), node, lastNode;
            while ((node = el.firstChild)) { lastNode = frag.appendChild(node); }
            lastSavedRange.insertNode(frag);
            
            if (lastNode) {
                var newRange = document.createRange();
                newRange.setStartAfter(lastNode);
                newRange.collapse(true);
                selection.removeAllRanges();
                selection.addRange(newRange);
                lastSavedRange = newRange;
            }
        } else {
            editor.innerHTML += tokenHtml;
        }
        
        this.value = '';
        syncEditorToString();
    });

    editor.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('srw-token')) {
            var token = e.target.getAttribute('data-token');
            if (token !== 'DATE' && token !== 'TIME') return;

            activeEditingTokenElement = e.target;
            var currentParam = e.target.getAttribute('data-param');

            subSelector.innerHTML = '';
            formatOptions[token].forEach(function(opt) {
                var selected = (opt.value === currentParam) ? ' selected' : '';
                subSelector.innerHTML += '<option value="' + opt.value + '"' + selected + '>' + opt.label + '</option>';
            });

            var rect = e.target.getBoundingClientRect();
            modal.style.top = (rect.bottom + window.scrollY + 5) + 'px';
            modal.style.left = (rect.left + window.scrollX) + 'px';
            modal.style.display = 'block';
        }
    });

    document.getElementById('srw-modal-save').addEventListener('click', function() {
        if (activeEditingTokenElement) {
            var chosenFormat = subSelector.value;
            activeEditingTokenElement.setAttribute('data-param', chosenFormat);
            activeEditingTokenElement.innerHTML = activeEditingTokenElement.getAttribute('data-token') + '&lt;' + chosenFormat + '&gt;';
            syncEditorToString();
        }
        modal.style.display = 'none';
    });

    document.getElementById('srw-modal-close').addEventListener('click', function() { modal.style.display = 'none'; });
    
    editor.addEventListener('input', syncEditorToString);
    form.addEventListener('submit', syncEditorToString);

    parseStringToEditor(hiddenInput.value);
    updateLivePreview(hiddenInput.value);
});
</script>
