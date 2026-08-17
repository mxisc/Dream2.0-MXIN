(function ($) {
    'use strict';

    window.DreamConfig = window.DreamConfig || {};
    window.DreamConfig.theme_base = Dream2WP.themeBase + 'assets/';
    window.DreamConfig.theme_version = Dream2WP.themeVersion;
    window.DreamConfig.default_theme = Dream2WP.defaultTheme;
    window.DreamConfig.document_hidden_title = Dream2WP.hiddenTitle || '';
    window.DreamConfig.document_visible_title = Dream2WP.visibleTitle || '';
    window.DreamConfig.website_time = Dream2WP.websiteTime || '';
    window.DreamConfig.notice_show_mode = Dream2WP.noticeMode || 'default';
    window.DreamConfig.cursor_move = Dream2WP.cursorMove || 'none';
    window.DreamConfig.cursor_click = Dream2WP.cursorClick || 'none';
    window.DreamConfig.effects_circle_magic_mode = Dream2WP.circleMode || 'none';
    window.DreamConfig.effects_lantern_mode = Dream2WP.lanternMode || 'none';
    window.DreamConfig.effects_sakura_mode = Dream2WP.sakuraMode || 'none';
    window.DreamConfig.effects_snowflake_mode = Dream2WP.snowflakeMode || 'none';
    window.DreamConfig.effects_universe_mode = Dream2WP.universeMode || 'none';
    window.DreamConfig.enable_baidu_push = !!Dream2WP.enableBaiduPush;
    window.DreamConfig.enable_toutiao_push = !!Dream2WP.enableToutiaoPush;
    window.DreamConfig.enable_live2d = false;
    window.DreamConfig.show_img_name = !!Dream2WP.showImageName;
    window.DreamConfig.load_progress = Dream2WP.loadProgress || 'none';
    document.documentElement.classList.toggle('dream-load-progress-enabled', window.DreamConfig.load_progress !== 'none');
    window.DreamConfig.code_fold_line = parseInt(Dream2WP.codeFoldLine || 0, 10);
    window.DreamConfig.img_fold_height = parseInt(Dream2WP.imageFoldHeight || 0, 10);
    if (Dream2WP.metingApi) {
        window.meting_api = Dream2WP.metingApi;
    }

    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|Windows Phone/i.test(navigator.userAgent);
    }

    function initializeBannerTyping() {
        var element = document.querySelector('.banner-info-desc');
        if (!element || element.dataset.typingReady === '1') return;
        var characters = Array.from(element.textContent.trim());
        if (!characters.length) return;

        element.dataset.typingReady = '1';
        element.textContent = '';
        var length = 0;
        var deleting = false;
        var timer = 0;

        function schedule(delay) {
            timer = window.setTimeout(update, delay);
            element._dreamBannerTypingTimer = timer;
        }

        function update() {
            if (!element.isConnected) {
                window.clearTimeout(timer);
                return;
            }
            if (deleting) {
                length--;
            } else {
                length++;
            }
            element.textContent = characters.slice(0, length).join('');

            if (!deleting && length === characters.length) {
                deleting = true;
                schedule(500);
                return;
            }
            if (deleting && length === 0) {
                deleting = false;
                schedule(500);
                return;
            }
            schedule(deleting ? 80 : 500);
        }

        schedule(500);
    }

    initializeBannerTyping();
    document.addEventListener('dream2:page-loaded', initializeBannerTyping);
    document.addEventListener('dream2:page-leaving', function () {
        document.querySelectorAll('.banner-info-desc').forEach(function (element) {
            if (element._dreamBannerTypingTimer) window.clearTimeout(element._dreamBannerTypingTimer);
        });
    });

    if (!isMobileDevice()) {
        (Dream2WP.desktopEffectScripts || []).forEach(function (path) {
            var script = document.createElement('script');
            if (path.indexOf('/granule.min.js') !== -1) {
                window.$ = window.jQuery;
            }
            script.src = Dream2WP.themeBase + path + '?ver=' + encodeURIComponent(Dream2WP.themeVersion);
            script.async = true;
            document.body.appendChild(script);
        });
    }

    function replaceBrokenAvatar(image) {
        if (!image || (!image.dataset.dreamAvatarFallbacks && !image.dataset.dreamAvatarFallback && !image.matches('.dream-link-card img, .friends .meta img, .dream-comment img.avatar, .dream-recent-comment img'))) return;
        image.removeAttribute('srcset');
        image.removeAttribute('sizes');
        var fallbacks = (image.dataset.dreamAvatarFallbacks || '').split('|').filter(Boolean);
        var fallbackIndex = parseInt(image.dataset.avatarFallbackIndex || '0', 10);
        if (fallbacks[fallbackIndex]) {
            image.dataset.avatarFallbackIndex = String(fallbackIndex + 1);
            image.src = fallbacks[fallbackIndex];
            scheduleAvatarFallbackTimeout(image);
            return;
        }
        if (image.dataset.dreamAvatarFallback && image.dataset.avatarFallback !== 'existing') {
            image.dataset.avatarFallback = 'existing';
            image.src = image.dataset.dreamAvatarFallback;
            scheduleAvatarFallbackTimeout(image);
            return;
        }
        if (image.dataset.avatarFallback === 'default') return;
        image.dataset.avatarFallback = 'default';
        image.src = Dream2WP.defaultAvatar;
    }
    function scheduleAvatarFallbackTimeout(image) {
        if (!image || (!image.dataset.dreamAvatarFallbacks && !image.dataset.dreamAvatarFallback)) return;
        if (image.dataset.avatarFallbackTimer) window.clearTimeout(Number(image.dataset.avatarFallbackTimer));
        image.dataset.avatarFallbackTimer = String(window.setTimeout(function () {
            if (!image.complete || image.naturalWidth === 0) replaceBrokenAvatar(image);
        }, 5000));
    }
    document.addEventListener('error', function (event) { replaceBrokenAvatar(event.target); }, true);
    window.addEventListener('load', function () {
        document.querySelectorAll('.dream-link-card img, .friends .meta img, .dream-comment img.avatar, .dream-recent-comment img, img[data-dream-avatar-fallbacks], img[data-dream-avatar-fallback]').forEach(function (image) {
            if (image.complete && image.naturalWidth === 0) replaceBrokenAvatar(image);
        });
    });
    function initializeAvatarFallbackTimeouts(root) {
        (root || document).querySelectorAll('img[data-dream-avatar-fallbacks], img[data-dream-avatar-fallback]').forEach(scheduleAvatarFallbackTimeout);
    }
    initializeAvatarFallbackTimeouts(document);
    document.addEventListener('dream2:page-loaded', function () { initializeAvatarFallbackTimeouts(document); });

    function initializeSafeAvatars(root) {
        (root || document).querySelectorAll('img[data-dream-avatar]:not([data-avatar-loading])').forEach(function (image) {
            image.dataset.avatarLoading = '1';
            var remote = new window.Image();
            remote.onload = function () { image.src = image.dataset.dreamAvatar; };
            remote.src = image.dataset.dreamAvatar;
        });
    }
    initializeSafeAvatars(document);
    window.addEventListener('load', function () { initializeSafeAvatars(document); });
    document.addEventListener('dream2:page-loaded', function () { initializeSafeAvatars(document); });

    function syncCommentEditor(editor) {
        var textarea = editor.closest('.dream-comment-editor').querySelector('.dream-comment-source');
        if (textarea) {
            var clone = editor.cloneNode(true);
            clone.querySelectorAll('img.dream-comment-emoji-image').forEach(function (image) {
                image.replaceWith(document.createTextNode(image.dataset.code || ''));
            });
            textarea.value = clone.innerHTML.trim();
        }
    }

    function syncPrivateCommentControl(input) {
        var editor = input && input.closest('.dream-comment-editor');
        var control = editor && editor.querySelector('.dream-private-comment-field');
        if (!control) return;
        control.classList.toggle('is-active', input.checked);
        control.setAttribute('aria-pressed', input.checked ? 'true' : 'false');
    }

    function initializeCommentEditors(root) {
        (root || document).querySelectorAll('.dream-comment-rich-editor:not([data-rich-ready])').forEach(function (editor) {
            var textarea = editor.closest('.dream-comment-editor').querySelector('.dream-comment-source');
            editor.dataset.richReady = '1';
            editor._dreamPendingFormats = [];
            var emojiButton = editor.closest('.dream-comment-editor').querySelector('[data-comment-action="emoji"]');
            if (emojiButton) {
                emojiButton.title = '选择表情';
                emojiButton.setAttribute('aria-label', '选择表情');
                emojiButton.hidden = !Dream2WP.emojiEndpoint && !(Dream2WP.emojiGroups || []).length;
            }
            var privateInput = editor.closest('.dream-comment-editor').querySelector('.dream-private-comment-input');
            if (privateInput) syncPrivateCommentControl(privateInput);
            if (textarea && textarea.value) editor.innerHTML = textarea.value;
            editor.addEventListener('input', function () { syncCommentEditor(editor); });
            editor.addEventListener('beforeinput', function (event) {
                if (event.inputType !== 'insertText' || !event.data || !editor._dreamPendingFormats.length) return;
                var selection = window.getSelection();
                if (!selection || !selection.rangeCount || !editor.contains(selection.anchorNode)) return;
                event.preventDefault();
                var range = selection.getRangeAt(0);
                range.deleteContents();
                var fragment = document.createDocumentFragment();
                var parent = fragment;
                var textNode = document.createTextNode(event.data);
                var formatTags = { bold: 'strong', italic: 'em', underline: 'u', strike: 'del', 'inline-code': 'code' };
                editor._dreamPendingFormats.forEach(function (format) {
                    var wrapper = document.createElement(formatTags[format]);
                    parent.appendChild(wrapper);
                    parent = wrapper;
                });
                parent.appendChild(textNode);
                range.insertNode(fragment);
                range.setStart(textNode, textNode.length);
                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);
                syncCommentEditor(editor);
            });
        });
    }

    initializeCommentEditors(document);
    document.addEventListener('dream2:page-loaded', function () { initializeCommentEditors(document); });

    var commentEmojiGroupsPromise = null;
    function updateCommentEmojiButtons(groups) {
        document.querySelectorAll('[data-comment-action="emoji"]').forEach(function (button) {
            button.hidden = !groups.length;
            button.removeAttribute('aria-busy');
            button.disabled = false;
        });
    }
    function loadCommentEmojiGroups() {
        var groups = Dream2WP.emojiGroups || window.emojiLists || [];
        if (groups.length || !Dream2WP.emojiEndpoint || !window.fetch) {
            return Promise.resolve(groups);
        }
        if (commentEmojiGroupsPromise) return commentEmojiGroupsPromise;
        document.querySelectorAll('[data-comment-action="emoji"]').forEach(function (button) {
            button.setAttribute('aria-busy', 'true');
            button.disabled = true;
        });
        commentEmojiGroupsPromise = window.fetch(Dream2WP.emojiEndpoint, {
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function (response) {
            if (!response.ok) throw new Error('Emoji request failed');
            return response.json();
        }).then(function (response) {
            var loadedGroups = response && response.success && response.data && Array.isArray(response.data.groups)
                ? response.data.groups
                : [];
            Dream2WP.emojiGroups = loadedGroups;
            updateCommentEmojiButtons(loadedGroups);
            renderCommentEmojis(document);
            return loadedGroups;
        }).catch(function () {
            commentEmojiGroupsPromise = null;
            document.querySelectorAll('[data-comment-action="emoji"]').forEach(function (button) {
                button.hidden = false;
                button.removeAttribute('aria-busy');
                button.disabled = false;
            });
            return [];
        });
        return commentEmojiGroupsPromise;
    }

    function commentEmojiEntries(group) {
        var entries = [];
        (group.items || []).forEach(function (item) { entries.push([item.code, item.file, item.code, item.url]); });
        return entries;
    }

    function appendCommentEmojiBatch(panel) {
        if (!panel || !panel._dreamEmojiEntries) return;
        var start = panel._dreamEmojiRendered || 0;
        var entries = panel._dreamEmojiEntries.slice(start, start + 42);
        entries.forEach(function (entry) {
            var alias = entry[0];
            var option = document.createElement('button');
            option.type = 'button';
            option.className = 'dream-comment-emoji';
            option.title = alias;
            option.dataset.code = entry[2];
            option.dataset.src = entry[3];
            option.setAttribute('aria-label', '插入表情 ' + alias);
            var image = document.createElement('img');
            image.src = option.dataset.src;
            image.alt = alias;
            image.loading = 'lazy';
            image.decoding = 'async';
            option.appendChild(image);
            panel.appendChild(option);
        });
        panel._dreamEmojiRendered = start + entries.length;
    }

    function releaseCommentEmojiGroup(panel) {
        if (!panel) return;
        panel.querySelectorAll('img').forEach(function (image) {
            image.removeAttribute('src');
        });
        panel.replaceChildren();
        panel._dreamEmojiEntries = null;
        panel._dreamEmojiRendered = 0;
        panel.scrollTop = 0;
    }

    function renderCommentEmojiGroup(panel, group) {
        if (!panel || panel._dreamEmojiEntries) return;
        panel._dreamEmojiEntries = commentEmojiEntries(group);
        panel._dreamEmojiRendered = 0;
        appendCommentEmojiBatch(panel);
    }

    function releaseCommentEmojiPicker(picker, exceptPanel) {
        if (!picker) return;
        picker.querySelectorAll('.dream-comment-emoji-group').forEach(function (panel) {
            if (panel !== exceptPanel) releaseCommentEmojiGroup(panel);
        });
    }

    function renderCommentEmojis(root) {
        var groups = Dream2WP.emojiGroups || window.emojiLists || [];
        if (!groups.length) return;
        var emojiMap = {};
        groups.forEach(function (group) {
            commentEmojiEntries(group).forEach(function (entry) {
                var code = entry[2];
                if (!emojiMap[code]) emojiMap[code] = { src: entry[3], alt: entry[0] };
            });
        });
        var codes = Object.keys(emojiMap).sort(function (a, b) { return b.length - a.length; });
        if (!codes.length) return;
        var pattern = new RegExp('(' + codes.map(function (code) { return code.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }).join('|') + ')', 'g');
        (root || document).querySelectorAll('.comment-content').forEach(function (content) {
            var walker = document.createTreeWalker(content, NodeFilter.SHOW_TEXT);
            var textNodes = [];
            while (walker.nextNode()) {
                if (!walker.currentNode.parentElement.closest('code, pre, a')) textNodes.push(walker.currentNode);
            }
            textNodes.forEach(function (textNode) {
                if (!pattern.test(textNode.nodeValue)) {
                    pattern.lastIndex = 0;
                    return;
                }
                pattern.lastIndex = 0;
                var fragment = document.createDocumentFragment();
                var lastIndex = 0;
                textNode.nodeValue.replace(pattern, function (match, code, offset) {
                    fragment.appendChild(document.createTextNode(textNode.nodeValue.slice(lastIndex, offset)));
                    var image = document.createElement('img');
                    image.className = 'dream-comment-emoji-rendered';
                    image.src = emojiMap[code].src;
                    image.alt = code;
                    image.title = emojiMap[code].alt;
                    image.loading = 'lazy';
                    fragment.appendChild(image);
                    lastIndex = offset + match.length;
                    return match;
                });
                fragment.appendChild(document.createTextNode(textNode.nodeValue.slice(lastIndex)));
                textNode.replaceWith(fragment);
            });
        });
    }

    renderCommentEmojis(document);
    document.addEventListener('dream2:page-loaded', function () { renderCommentEmojis(document); });

    function toggleCommentEmojiPicker(editor, button) {
        var wrapper = editor.closest('.dream-comment-editor');
        var picker = wrapper.querySelector('.dream-comment-emoji-picker');
        if (!picker) {
            picker = document.createElement('div');
            picker.className = 'dream-comment-emoji-picker';
            picker.setAttribute('role', 'dialog');
            picker.setAttribute('aria-label', '选择表情');
            var tabs = document.createElement('div');
            tabs.className = 'dream-comment-emoji-tabs';
            var panels = document.createElement('div');
            panels.className = 'dream-comment-emoji-panels';
            var groups = Dream2WP.emojiGroups || window.emojiLists || [];
            groups.forEach(function (group, groupIndex) {
                var tab = document.createElement('button');
                tab.type = 'button';
                tab.className = 'dream-comment-emoji-tab' + (groupIndex === 0 ? ' is-active' : '');
                tab.textContent = group.name;
                tab.dataset.group = String(groupIndex);
                tabs.appendChild(tab);

                var panel = document.createElement('div');
                panel.className = 'dream-comment-emoji-group' + (groupIndex === 0 ? ' is-active' : '');
                panel.dataset.group = String(groupIndex);
                panel.addEventListener('scroll', function () {
                    if (panel.scrollTop + panel.clientHeight >= panel.scrollHeight - 80) {
                        appendCommentEmojiBatch(panel);
                    }
                }, { passive: true });
                panels.appendChild(panel);
            });
            picker.appendChild(tabs);
            picker.appendChild(panels);
            wrapper.appendChild(picker);
        }
        var open = !picker.classList.contains('is-open');
        document.querySelectorAll('.dream-comment-emoji-picker.is-open').forEach(function (other) {
            if (other !== picker) {
                other.classList.remove('is-open');
                releaseCommentEmojiPicker(other);
            }
        });
        picker.classList.toggle('is-open', open);
        button.classList.toggle('is-active', open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            var activePanel = picker.querySelector('.dream-comment-emoji-group.is-active');
            var activeGroup = activePanel ? Number(activePanel.dataset.group) : 0;
            var groups = Dream2WP.emojiGroups || window.emojiLists || [];
            releaseCommentEmojiPicker(picker, activePanel);
            if (activePanel && groups[activeGroup]) renderCommentEmojiGroup(activePanel, groups[activeGroup]);
            var wrapperRect = wrapper.getBoundingClientRect();
            var buttonRect = button.getBoundingClientRect();
            var pickerRect = picker.getBoundingClientRect();
            var gap = 8;
            var left = buttonRect.left - wrapperRect.left;
            var maxLeft = Math.max(0, wrapperRect.width - pickerRect.width);
            left = Math.max(0, Math.min(left, maxLeft));

            var top = buttonRect.bottom - wrapperRect.top + gap;
            var spaceBelow = window.innerHeight - buttonRect.bottom;
            var spaceAbove = buttonRect.top;
            if (spaceBelow < pickerRect.height + gap && spaceAbove > pickerRect.height + gap) {
                top = buttonRect.top - wrapperRect.top - pickerRect.height - gap;
            }

            picker.style.left = left + 'px';
            picker.style.top = top + 'px';
        } else {
            releaseCommentEmojiPicker(picker);
        }
    }

    function applyCommentFormat(editor, action) {
        var selection = window.getSelection();
        var range = editor._dreamSelection;
        if (!range && selection && selection.rangeCount && editor.contains(selection.anchorNode)) {
            range = selection.getRangeAt(0).cloneRange();
        }
        if (!range) {
            range = document.createRange();
            range.selectNodeContents(editor);
            range.collapse(false);
        }

        var tags = {
            bold: ['strong', '粗体文字'],
            italic: ['em', '斜体文字'],
            underline: ['u', '下划线文字'],
            strike: ['del', '删除线文字'],
            'inline-code': ['code', '代码'],
            quote: ['blockquote', '引用内容']
        };
        var toggleTags = {
            bold: ['strong', 'b'],
            italic: ['em', 'i'],
            underline: ['u'],
            strike: ['del', 's', 'strike'],
            'inline-code': ['code'],
            quote: ['blockquote'],
            'code-block': ['pre']
        };
        var activeToggleTags = toggleTags[action] || [];
        var inlineActions = ['bold', 'italic', 'underline', 'strike', 'inline-code'];
        if (range.collapsed && inlineActions.indexOf(action) !== -1) {
            var activeAncestor = range.startContainer.nodeType === 1 ? range.startContainer : range.startContainer.parentNode;
            while (activeAncestor && activeAncestor !== editor && activeToggleTags.indexOf(activeAncestor.tagName.toLowerCase()) === -1) {
                activeAncestor = activeAncestor.parentNode;
            }
            if (activeAncestor && activeAncestor !== editor) {
                range.setStartAfter(activeAncestor);
                range.collapse(true);
                editor._dreamPendingFormats = editor._dreamPendingFormats.filter(function (format) { return format !== action; });
            } else {
                var pendingIndex = editor._dreamPendingFormats.indexOf(action);
                if (pendingIndex === -1) editor._dreamPendingFormats.push(action);
                else editor._dreamPendingFormats.splice(pendingIndex, 1);
            }
            if (selection) {
                selection.removeAllRanges();
                selection.addRange(range);
            }
            return;
        }
        if (activeToggleTags.length) {
            var ancestor = range.commonAncestorContainer.nodeType === 1 ? range.commonAncestorContainer : range.commonAncestorContainer.parentNode;
            var matchingAncestors = [];
            while (ancestor && ancestor !== editor) {
                if (activeToggleTags.indexOf(ancestor.tagName.toLowerCase()) !== -1) matchingAncestors.push(ancestor);
                ancestor = ancestor.parentNode;
            }
            if (matchingAncestors.length) {
                var startContainer = range.startContainer;
                var startOffset = range.startOffset;
                var endContainer = range.endContainer;
                var endOffset = range.endOffset;
                matchingAncestors.forEach(function (matchingAncestor) {
                    var parent = matchingAncestor.parentNode;
                    if (!parent) return;
                    var contentNode = matchingAncestor.tagName.toLowerCase() === 'pre' && matchingAncestor.firstElementChild && matchingAncestor.firstElementChild.tagName.toLowerCase() === 'code' ? matchingAncestor.firstElementChild : matchingAncestor;
                    while (contentNode.firstChild) parent.insertBefore(contentNode.firstChild, matchingAncestor);
                    parent.removeChild(matchingAncestor);
                });
                try {
                    range.setStart(startContainer, startOffset);
                    range.setEnd(endContainer, endOffset);
                } catch (error) {
                    range.selectNodeContents(editor);
                    range.collapse(false);
                }
                if (selection) {
                    selection.removeAllRanges();
                    selection.addRange(range);
                }
                return;
            }
        }
        var node;
        if (action === 'emoji') {
            node = document.createTextNode('😀');
        } else if (range.collapsed && (action === 'quote' || action === 'code-block')) {
            node = document.createElement(action === 'quote' ? 'blockquote' : 'pre');
            var emptyBlock = action === 'code-block' ? document.createElement('code') : node;
            emptyBlock.appendChild(document.createElement('br'));
            if (action === 'code-block') node.appendChild(emptyBlock);
        } else if (range.collapsed) {
            return;
        } else if (action === 'code-block') {
            node = document.createElement('pre');
            var code = document.createElement('code');
            code.appendChild(range.extractContents());
            node.appendChild(code);
        } else if (tags[action]) {
            node = document.createElement(tags[action][0]);
            node.appendChild(range.extractContents());
        }
        if (!node) return;
        range.deleteContents();
        range.insertNode(node);
        range.selectNodeContents(action === 'code-block' ? node.firstElementChild : node);
        range.collapse(false);
        if (selection) {
            selection.removeAllRanges();
            selection.addRange(range);
        }
    }

    $(document).on('mousedown', '.dream-comment-toolbar [data-comment-action]', function (event) {
        var editor = this.closest('.dream-comment-editor').querySelector('.dream-comment-rich-editor');
        var selection = window.getSelection();
        if (editor && selection && selection.rangeCount && editor.contains(selection.anchorNode)) {
            editor._dreamSelection = selection.getRangeAt(0).cloneRange();
        }
        event.preventDefault();
    });

    $(document).on('change', '.dream-private-comment-input', function () {
        syncPrivateCommentControl(this);
    });

    $(document).on('click', '.dream-private-comment-field', function () {
        var input = this.closest('.dream-comment-editor').querySelector('.dream-private-comment-input');
        if (!input || input.disabled) return;
        input.checked = !input.checked;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    $(document).on('click', '.dream-comment-toolbar [data-comment-action]', function () {
        var editor = this.closest('.dream-comment-editor').querySelector('.dream-comment-rich-editor');
        if (!editor) return;
        var action = this.dataset.commentAction;
        var currentSelection = window.getSelection();
        if (action === 'emoji') {
            if (!editor._dreamSelection && currentSelection && currentSelection.rangeCount && editor.contains(currentSelection.anchorNode)) {
                editor._dreamSelection = currentSelection.getRangeAt(0).cloneRange();
            }
            if (document.activeElement === editor) editor.blur();
            var emojiButton = this;
            loadCommentEmojiGroups().then(function (groups) {
                if (groups.length && editor.isConnected) toggleCommentEmojiPicker(editor, emojiButton);
            });
            return;
        }
        if (editor._dreamSelection && currentSelection) {
            currentSelection.removeAllRanges();
            currentSelection.addRange(editor._dreamSelection);
        } else if (!currentSelection || !currentSelection.rangeCount || !editor.contains(currentSelection.anchorNode)) {
            editor.focus();
        }
        applyCommentFormat(editor, action);
        var activeTags = {
            bold: ['strong', 'b'], italic: ['em', 'i'], underline: ['u'], strike: ['del', 's', 'strike'],
            'inline-code': ['code'], quote: ['blockquote'], 'code-block': ['pre']
        }[action] || [];
        var selectionNode = currentSelection && currentSelection.rangeCount ? currentSelection.getRangeAt(0).startContainer : null;
        var selectionElement = selectionNode && selectionNode.nodeType === 1 ? selectionNode : selectionNode && selectionNode.parentNode;
        var active = editor._dreamPendingFormats.indexOf(action) !== -1;
        while (!active && selectionElement && selectionElement !== editor) {
            active = activeTags.indexOf(selectionElement.tagName.toLowerCase()) !== -1;
            selectionElement = selectionElement.parentNode;
        }
        this.classList.toggle('is-active', active);
        this.setAttribute('aria-pressed', active ? 'true' : 'false');
        editor._dreamSelection = null;
        syncCommentEditor(editor);
    });

    $(document).on('mousedown', '.dream-comment-emoji, .dream-comment-emoji-tab', function (event) {
        event.preventDefault();
    });

    $(document).on('click', '.dream-comment-emoji-tab', function () {
        var picker = this.closest('.dream-comment-emoji-picker');
        var group = this.dataset.group;
        var panel = picker.querySelector('.dream-comment-emoji-group[data-group="' + group + '"]');
        var groups = Dream2WP.emojiGroups || window.emojiLists || [];
        releaseCommentEmojiPicker(picker, panel);
        if (panel && groups[Number(group)]) renderCommentEmojiGroup(panel, groups[Number(group)]);
        picker.querySelectorAll('.dream-comment-emoji-tab').forEach(function (tab) {
            tab.classList.toggle('is-active', tab.dataset.group === group);
        });
        picker.querySelectorAll('.dream-comment-emoji-group').forEach(function (panel) {
            panel.classList.toggle('is-active', panel.dataset.group === group);
        });
    });

    $(document).on('click', '.dream-comment-emoji', function () {
        var wrapper = this.closest('.dream-comment-editor');
        var editor = wrapper.querySelector('.dream-comment-rich-editor');
        var selection = window.getSelection();
        var range = editor._dreamSelection;
        if (!range) {
            range = document.createRange();
            range.selectNodeContents(editor);
            range.collapse(false);
        }
        editor.focus();
        selection.removeAllRanges();
        selection.addRange(range);
        var emojiImage = document.createElement('img');
        emojiImage.className = 'dream-comment-emoji-image';
        emojiImage.src = this.dataset.src;
        emojiImage.alt = this.title;
        emojiImage.title = this.title;
        emojiImage.dataset.code = this.dataset.code;
        emojiImage.setAttribute('contenteditable', 'false');
        range.deleteContents();
        range.insertNode(emojiImage);
        range.setStartAfter(emojiImage);
        range.collapse(true);
        selection.removeAllRanges();
        selection.addRange(range);
        editor._dreamSelection = null;
        syncCommentEditor(editor);
        var picker = wrapper.querySelector('.dream-comment-emoji-picker');
        picker.classList.remove('is-open');
        releaseCommentEmojiPicker(picker);
        var trigger = wrapper.querySelector('[data-comment-action="emoji"]');
        trigger.classList.remove('is-active');
        trigger.setAttribute('aria-expanded', 'false');
    });

    $(document).on('click', function (event) {
        if (event.target.closest('.dream-comment-emoji-picker, [data-comment-action="emoji"]')) return;
        document.querySelectorAll('.dream-comment-emoji-picker.is-open').forEach(function (picker) {
            picker.classList.remove('is-open');
            releaseCommentEmojiPicker(picker);
            var trigger = picker.closest('.dream-comment-editor').querySelector('[data-comment-action="emoji"]');
            trigger.classList.remove('is-active');
            trigger.setAttribute('aria-expanded', 'false');
        });
    });

    $(document).on('submit', '.dream-comment-form', function (event) {
        var editor = this.querySelector('.dream-comment-rich-editor');
        var textarea = this.querySelector('.dream-comment-source');
        if (!editor || !textarea) return;
        syncCommentEditor(editor);
        if (!editor.textContent.trim()) {
            event.preventDefault();
            editor.focus();
        }
    });

    function showLinkApplicationNotice(form, type, message) {
        var notice = form.closest('.dream-link-application').querySelector('.dream-link-application-notice');
        if (!notice) {
            notice = document.createElement('div');
            notice.className = 'dream-link-application-notice';
            notice.setAttribute('role', 'alert');
            form.insertAdjacentElement('beforebegin', notice);
        }
        notice.className = 'dream-link-application-notice is-' + type;
        notice.innerHTML = '';
        var icon = document.createElement('i');
        icon.className = type === 'success' ? 'ri-checkbox-circle-line' : (type === 'info' ? 'ri-loader-4-line' : 'ri-error-warning-line');
        icon.setAttribute('aria-hidden', 'true');
        var text = document.createElement('span');
        text.textContent = message;
        notice.appendChild(icon);
        notice.appendChild(text);
        return notice;
    }

    function refreshLinkApplicationComments(data, notice) {
        if (!data || !data.approved || !data.comment_id || !data.refresh_url || !window.DOMParser) {
            return Promise.resolve();
        }
        return window.fetch(data.refresh_url, {
            credentials: 'same-origin',
            headers: { 'X-Dream-PJAX': '1' }
        }).then(function (response) {
            return response.text();
        }).then(function (html) {
            var nextDocument = new DOMParser().parseFromString(html, 'text/html');
            var currentComments = document.getElementById('comments');
            var nextComments = nextDocument.getElementById('comments');
            if (currentComments && nextComments) {
                currentComments.replaceWith(nextComments);
                document.dispatchEvent(new CustomEvent('dream2:page-loaded'));
            }
            var target = document.getElementById('comment-' + data.comment_id) || notice;
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }).catch(function () {});
    }

    $(document).on('submit', '.dream-link-application-form', function (event) {
        if (!window.fetch || !window.FormData) return;
        event.preventDefault();
        var form = this;
        if (form.dataset.dreamSubmitting === '1') return;
        var submitter = event.originalEvent ? event.originalEvent.submitter : form.querySelector('[type="submit"]');
        var originalHtml = submitter ? submitter.innerHTML : '';
        var data = new FormData(form);
        data.append('dream2_link_application_ajax', '1');
        form.dataset.dreamSubmitting = '1';
        form.setAttribute('aria-busy', 'true');
        if (submitter) {
            submitter.disabled = true;
            submitter.textContent = '正在检测反链...';
        }
        var notice = showLinkApplicationNotice(form, 'info', '正在检测反链，请稍等。');

        window.fetch(window.Dream2WP && Dream2WP.ajaxUrl ? Dream2WP.ajaxUrl : form.action, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: data
        }).then(function (response) {
            return response.text().then(function (text) {
                try {
                    return JSON.parse(text);
                } catch (error) {
                    throw {
                        message: response.redirected || /<!doctype|<html/i.test(text)
                            ? '提交接口返回了页面内容，请刷新后重试。'
                            : '提交接口返回异常，请稍后重试。'
                    };
                }
            });
        }).then(function (response) {
            var payload = response && response.data ? response.data : {};
            if (!response || !response.success) {
                throw payload;
            }
            notice = showLinkApplicationNotice(form, payload.type || 'success', payload.message || '友链申请已提交。');
            form.reset();
            return refreshLinkApplicationComments(payload, notice);
        }).catch(function (error) {
            showLinkApplicationNotice(form, 'error', error && error.message ? error.message : '申请提交失败，请稍后重试。');
        }).finally(function () {
            delete form.dataset.dreamSubmitting;
            form.removeAttribute('aria-busy');
            if (submitter) {
                submitter.disabled = false;
                submitter.innerHTML = originalHtml;
            }
        });
    });

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            if (Dream2WP.enableServiceWorker) {
                navigator.serviceWorker.register(Dream2WP.serviceWorkerUrl, { scope: '/' }).catch(function () {});
                return;
            }
            if (!navigator.serviceWorker.getRegistrations) return;
            navigator.serviceWorker.getRegistrations().then(function (registrations) {
                registrations.forEach(function (registration) {
                    var worker = registration.active || registration.waiting || registration.installing;
                    if (worker && worker.scriptURL.indexOf('dream2_sw=1') !== -1) {
                        registration.unregister();
                    }
                });
            }).catch(function () {});
        });
    }

    function insertMaintainedScript(source, configure) {
        Array.prototype.slice.call(document.scripts).forEach(function (script) {
            if (script.src === source || script.getAttribute('src') === source) {
                script.parentNode.removeChild(script);
            }
        });
        var script = document.createElement('script');
        script.src = source;
        if (configure) configure(script);
        var anchor = document.getElementsByTagName('script')[0];
        if (anchor && anchor.parentNode) {
            anchor.parentNode.insertBefore(script, anchor);
            return;
        }
        (document.head || document.body || document.documentElement).appendChild(script);
    }

    function loadMaintainScripts() {
        if (Dream2WP.enableBaiduPush) {
            insertMaintainedScript(window.location.protocol.split(':')[0] === 'https'
                ? 'https://zz.bdstatic.com/linksubmit/push.js'
                : 'http://push.zhanzhang.baidu.com/push.js');
        }
        if (Dream2WP.enableToutiaoPush) {
            insertMaintainedScript('https://lf1-cdn-tos.bytegoofy.com/goofy/ttzz/push.js?0fbcfbb1ed642c21419d5be02d56ade7d6ee5372ca221d12ba35df110760b2a830632485602430134f60bc55ca391050b680e2741bf7233a8f1da9902314a3fa', function (script) {
                script.id = 'ttzz';
            });
        }
    }
    window.addEventListener('load', loadMaintainScripts);
    document.addEventListener('dream2:page-loaded', loadMaintainScripts);

    var nightMode = localStorage.getItem('night');
    if (nightMode === null) {
        nightMode = Dream2WP.defaultTheme === 'night' ||
            (Dream2WP.defaultTheme === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
    } else {
        nightMode = nightMode === 'true';
    }

    function setNightMode(enabled) {
        document.documentElement.classList.toggle('night', enabled);
        localStorage.setItem('night', enabled ? 'true' : 'false');
        nightMode = enabled;
    }

    setNightMode(nightMode);
    document.addEventListener('DOMContentLoaded', function () {
        document.documentElement.classList.add('loaded');
    });

    $(document).on('click', '#toggle-mode', function () {
        setNightMode(nightMode.toString() !== 'true');
    });

    if (Dream2WP.defaultTheme === 'system' && window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (event) {
            setNightMode(event.matches);
        });
    }

    $(document).on('click', '#back-to-top', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    var previousScrollTop = window.pageYOffset || 0;
    function updateScrollUi() {
        var currentScrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
        $('.actions').toggleClass('show', currentScrollTop > 100);
        document.body.classList.toggle('move-up', currentScrollTop > previousScrollTop && currentScrollTop > 100);
        previousScrollTop = currentScrollTop;
    }
    window.addEventListener('scroll', updateScrollUi, { passive: true });
    updateScrollUi();

    $(document).on('click', '.navbar-slideicon', function () {
        document.documentElement.classList.add('disable-scroll');
        $('.navbar-slideout, .navbar-mask').addClass('active');
    });

    $(document).on('click', '.navbar-mask', function () {
        document.documentElement.classList.remove('disable-scroll');
        $('.navbar-slideout, .navbar-mask').removeClass('active');
    });

    function initializeDrawerMenu(root) {
        (root || document).querySelectorAll('.panel-side-menu a').forEach(function (link) {
            link.classList.add('link');
            var item = link.closest('li');
            if (item && /current-menu-(?:item|parent|ancestor)/.test(item.className)) {
                link.classList.add('current');
            }
        });

        (root || document).querySelectorAll('.navbar-slideout-menu .current').forEach(function (current) {
            var panelBody = current.closest('.panel-body');
            while (panelBody) {
                panelBody.style.display = 'block';
                var panel = panelBody.previousElementSibling;
                if (panel && panel.classList.contains('panel')) panel.classList.add('in');
                panelBody = panelBody.parentElement ? panelBody.parentElement.closest('.panel-body') : null;
            }
        });
    }

    initializeDrawerMenu(document);
    document.addEventListener('dream2:page-loaded', function () { initializeDrawerMenu(document); });

    $(document).on('click', '.navbar-slideout-menu .panel', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var $panel = $(this);
        var $body = $panel.siblings('.panel-body');
        var $panelBox = $panel.parent().parent();
        $panelBox.find('.panel').not($panel).removeClass('in');
        $panelBox.find('.panel-body').not($body).stop(true, true).hide('fast');
        $panel.toggleClass('in');
        $body.stop(true, true).toggle('fast');
    });

    $(document).on('keyup', function (event) {
        if (event.key === 'Escape') {
            document.documentElement.classList.remove('disable-scroll');
            $('.navbar-slideout, .navbar-mask').removeClass('active');
            $('.dream-search-overlay').prop('hidden', true);
        }
    });

    $(document).on('click', '.dream-search-toggle', function () {
        $('.dream-search-overlay').prop('hidden', false).find('.search-field').trigger('focus');
    });

    var searchTimer = null;
    var searchActiveIndex = -1;

    function updateSearchActive(index) {
        var items = Array.prototype.slice.call(document.querySelectorAll('.dream-search-result'));
        if (!items.length) {
            searchActiveIndex = -1;
            return;
        }
        searchActiveIndex = (index + items.length) % items.length;
        items.forEach(function (item, itemIndex) {
            item.classList.toggle('is-active', itemIndex === searchActiveIndex);
            item.setAttribute('aria-selected', itemIndex === searchActiveIndex ? 'true' : 'false');
        });
        items[searchActiveIndex].scrollIntoView({ block: 'nearest' });
    }

    function setSearchKeyboardMode(enabled) {
        var results = document.querySelector('.dream-search-results');
        if (results) results.classList.toggle('is-keyboarding', enabled);
    }

    function renderSearchResults(items) {
        var results = document.querySelector('.dream-search-results');
        var empty = document.querySelector('.dream-search-empty');
        if (!results || !empty) return;
        results.textContent = '';
        if (!items || !items.length) {
            searchActiveIndex = -1;
            results.hidden = true;
            empty.hidden = false;
            return;
        }
        items.forEach(function (item, index) {
            var link = document.createElement('a');
            link.className = 'dream-search-result';
            link.href = item.url || '#';
            link.setAttribute('role', 'option');
            link.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
            link.innerHTML = '<span class="dream-search-result-title"></span><span class="dream-search-result-action" aria-label="回车"><span class="dream-search-enter-symbol" aria-hidden="true"></span></span>';
            link.querySelector('.dream-search-result-title').textContent = item.title || '';
            results.appendChild(link);
        });
        results.hidden = false;
        empty.hidden = true;
        updateSearchActive(0);
    }

    $(document).on('input', '.dream-search-overlay .search-field', function () {
        var query = this.value.trim();
        clearTimeout(searchTimer);
        if (query.length < 2) {
            renderSearchResults([]);
            return;
        }
        searchTimer = setTimeout(function () {
            var url = new URL(Dream2WP.searchRestUrl || (Dream2WP.searchUrl + 'wp-json/wp/v2/search'));
            url.searchParams.set('search', query);
            url.searchParams.set('per_page', '8');
            url.searchParams.set('subtype', 'post,page');
            fetch(url.toString(), { credentials: 'same-origin' })
                .then(function (response) { return response.ok ? response.json() : []; })
                .then(renderSearchResults)
                .catch(function () { renderSearchResults([]); });
        }, 180);
    });

    $(document).on('keydown', '.dream-search-overlay .search-field', function (event) {
        var items = Array.prototype.slice.call(document.querySelectorAll('.dream-search-result'));
        if (!items.length) return;
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setSearchKeyboardMode(true);
            updateSearchActive(searchActiveIndex + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setSearchKeyboardMode(true);
            updateSearchActive(searchActiveIndex - 1);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            if (searchActiveIndex >= 0 && items[searchActiveIndex]) {
                window.location.href = items[searchActiveIndex].href;
            }
        }
    });

    $(document).on('mouseenter focus', '.dream-search-result', function () {
        setSearchKeyboardMode(false);
        var items = Array.prototype.slice.call(document.querySelectorAll('.dream-search-result'));
        var index = items.indexOf(this);
        if (index >= 0) updateSearchActive(index);
    });

    $(document).on('click', '.dream-search-close, .dream-search-overlay', function (event) {
        if (event.target === this || $(event.target).closest('.dream-search-close').length) {
            $('.dream-search-overlay').prop('hidden', true);
        }
    });

    $(document).on('click', '.click-close', function () {
        $(this).closest('.tips').slideUp(180);
    });

    $(document).on('click', '.dream-ad-close', function () {
        $(this).closest('.dream-ad').slideUp(180);
    });

    var colorCharacters = (Dream2WP.colorCharacters || []).map(function (item) {
        return String(item || '').trim();
    }).filter(Boolean);
    var sparkColors = ['rgb(110,64,170)', 'rgb(150,61,179)', 'rgb(191,60,175)', 'rgb(228,65,157)', 'rgb(254,75,131)', 'rgb(255,94,99)', 'rgb(255,120,71)', 'rgb(251,150,51)', 'rgb(226,183,47)', 'rgb(198,214,60)', 'rgb(175,240,91)', 'rgb(127,246,88)', 'rgb(82,246,103)', 'rgb(48,239,130)', 'rgb(29,223,163)', 'rgb(26,199,194)', 'rgb(35,171,216)', 'rgb(54,140,225)', 'rgb(76,110,219)', 'rgb(96,84,200)'];
    function appendSparkTail(target, length) {
        var fragment = document.createDocumentFragment();
        for (var index = 0; index < length; index++) {
            var span = document.createElement('span');
            span.textContent = String.fromCharCode(94 * Math.random() + 33);
            span.style.color = sparkColors[Math.floor(Math.random() * sparkColors.length)];
            fragment.appendChild(span);
        }
        target.appendChild(fragment);
    }
    function startSparkProvider(target, provider) {
        if (!Dream2WP.enableColorCharacter || target.dataset.dreamSparkReady) return false;
        var queue = [];
        var fetching = 0;
        var controller = {stopped: false, timer: 0, request: null};
        var state = {text: '', source: '', prefixP: -5, skillP: 0, direction: 'forward', holdUntil: 0, step: 1};
        controller.stop = function () {
            if (controller.stopped) return;
            controller.stopped = true;
            window.clearTimeout(controller.timer);
            if (controller.request) controller.request.abort();
        };
        target._dreamSparkController = controller;
        function prefetch() {
            if (controller.stopped || !target.isConnected) return;
            fetching++;
            var request = window.AbortController ? new AbortController() : null;
            controller.request = request;
            Promise.resolve().then(function () {
                return provider(request ? request.signal : undefined);
            }).then(function (text) {
                text = String(text || '').trim();
                if (text && !controller.stopped && target.isConnected) {
                    queue.push(text);
                }
            }).catch(function () {}).then(function () {
                fetching--;
                if (controller.request === request) controller.request = null;
            });
        }
        function ensurePrefetch(size) {
            while (queue.length + fetching < size) {
                prefetch();
            }
        }
        function prefetchInitial() {
            ensurePrefetch(1);
        }
        function useNextSource() {
            if (!queue.length) {
                ensurePrefetch(1);
                return false;
            }
            state.source = queue.shift();
            state.text = state.source.charAt(0);
            state.prefixP = 0;
            state.skillP = state.text ? 1 : 0;
            state.direction = 'forward';
            state.holdUntil = 0;
            state.step = 1;
            ensurePrefetch(1);
            return true;
        }
        function tick() {
            if (controller.stopped || !target.isConnected) {
                controller.stop();
                return;
            }
            if (!state.source && !useNextSource()) {
                controller.timer = window.setTimeout(tick, 75);
                return;
            }
            if (state.step) {
                state.step--;
            } else {
                state.step = 1;
                if (state.prefixP < 0) {
                    state.prefixP++;
                } else if (state.direction === 'forward') {
                    if (state.skillP < state.source.length) {
                        state.text += state.source[state.skillP];
                        state.skillP++;
                    } else if (!state.holdUntil) {
                        state.holdUntil = Date.now() + 300;
                    } else if (Date.now() >= state.holdUntil) {
                        state.direction = 'backward';
                        state.holdUntil = 0;
                    }
                } else if (state.skillP > 0) {
                    state.text = state.text.slice(0, -1);
                    state.skillP--;
                    if (!state.skillP && !useNextSource()) {
                        state.text = '';
                        state.source = '';
                        state.direction = 'forward';
                    }
                } else if (!useNextSource()) {
                    state.text = '';
                    state.source = '';
                    state.direction = 'forward';
                }
            }
            target.textContent = state.text;
            appendSparkTail(target, state.prefixP < 0 ? Math.min(5, 5 + state.prefixP) : Math.min(5, state.source.length - state.skillP));
            controller.timer = window.setTimeout(tick, 75);
        }
        target.setAttribute('data-dream-spark-ready', '1');
        prefetchInitial();
        tick();
        return true;
    }
    function startSparkInput(target, texts) {
        if (!Dream2WP.enableColorCharacter || target.dataset.dreamSparkReady) return false;
        texts = (texts || []).map(function (item) {
            return String(item || '').trim();
        }).filter(Boolean);
        if (!texts.length) return false;
        var index = 0;
        return startSparkProvider(target, function () {
            var text = texts[index % texts.length];
            index++;
            return text;
        });
    }
    function parseHitokotoData(data, fallbackText) {
        if (typeof data === 'string') return data.trim() || fallbackText;
        return data && (data.hitokoto || data.text || data.sentence || data.content) ? String(data.hitokoto || data.text || data.sentence || data.content).trim() : fallbackText;
    }
    var hitokotoRequestIndex = 0;
    function buildHitokotoUrl() {
        var source = Dream2WP.hitokotoUrl || 'https://v1.hitokoto.cn/?encode=json';
        var cacheKey = Date.now() + '-' + (++hitokotoRequestIndex);
        try {
            var url = new URL(source, window.location.href);
            url.searchParams.set('_dream2', cacheKey);
            return url.toString();
        } catch (error) {
            return source + (source.indexOf('?') === -1 ? '?' : '&') + '_dream2=' + encodeURIComponent(cacheKey);
        }
    }
    function fetchHitokotoText(fallbackText, signal) {
        return fetch(buildHitokotoUrl(), {cache: 'no-store', signal: signal})
            .then(function (response) {
                if (!response.ok) return Promise.reject();
                var contentType = response.headers.get('content-type') || '';
                return contentType.indexOf('json') !== -1 ? response.json() : response.text();
            })
            .then(function (data) {
                return parseHitokotoData(data, fallbackText);
            })
            .catch(function (error) {
                return error && error.name === 'AbortError' ? '' : fallbackText;
            });
    }
    function initializeHitokoto(root) {
        if (!Dream2WP.enableHitokoto || !window.fetch) return;
        $(root || document).find('.spark-input:not([data-dream-hitokoto-ready])').each(function () {
            var target = this;
            var fallbackText = target.textContent;
            target.setAttribute('data-dream-hitokoto-ready', '1');
            if (Dream2WP.enableColorCharacter) {
                startSparkProvider(target, function (signal) { return fetchHitokotoText(fallbackText, signal); });
                return;
            }
            target.textContent = '';
            var request = window.AbortController ? new AbortController() : null;
            target._dreamHitokotoController = request;
            fetchHitokotoText(fallbackText, request ? request.signal : undefined).then(function (text) {
                if (target.isConnected && text) target.textContent = text;
            });
        });
    }
    function initializeSparkInput(root) {
        if (isMobileDevice() || !Dream2WP.enableColorCharacter) return;
        if (Dream2WP.enableHitokoto) {
            initializeHitokoto(root);
            return;
        }
        $(root || document).find('.spark-input:not([data-dream-spark-ready])').each(function () {
            var defaultText = String(this.textContent || '').trim();
            var texts = colorCharacters.length ? colorCharacters : (defaultText ? [defaultText] : []);
            startSparkInput(this, texts);
        });
    }
    initializeSparkInput(document);
    document.addEventListener('dream2:page-loaded', function () { initializeSparkInput(document); });
    document.addEventListener('dream2:page-leaving', function () {
        document.querySelectorAll('.spark-input').forEach(function (target) {
            if (target._dreamSparkController) target._dreamSparkController.stop();
            if (target._dreamHitokotoController) target._dreamHitokotoController.abort();
        });
    });

    var colorizeTags = function (root, selector) {
        var palette = ['#50bfff', '#ff7b89', '#8e7dff', '#35c9a5', '#f1a43c', '#ec6ead'];
        (root || document).querySelectorAll(selector).forEach(function (tag, index) {
            var color = palette[index % palette.length];
            tag.style.setProperty('--dream-tag-color', color);
            tag.classList.add('dream-color-tag');
        });
    };
    function initializeColorTags(root) {
        var scope = root || document;
        colorizeTags(scope, '.widget.tags.dream-tags-colored a');
        colorizeTags(scope, '.widget.tagcloud.dream-tagcloud-colored a');
    }
    initializeColorTags(document);
    document.addEventListener('dream2:page-loaded', function () { initializeColorTags(document); });

    function initializeDrawerToc() {
        var toc = document.querySelector('.dream-drawer-toc');
        var tocList = toc && toc.querySelector('ol');
        if (!tocList) return;

        tocList.replaceChildren();
        document.querySelectorAll('.article h2, .article h3, .article h4').forEach(function (heading, index) {
            if (!heading.id) heading.id = 'dream-heading-' + index;
            var item = document.createElement('li');
            item.className = 'toc-level-' + heading.tagName.toLowerCase();
            var link = document.createElement('a');
            link.href = '#' + heading.id;
            link.setAttribute('data-no-pjax', '');
            link.textContent = heading.textContent;
            item.appendChild(link);
            tocList.appendChild(item);
        });
        toc.hidden = !tocList.children.length;
    }

    function buildTocItem(heading, index) {
        if (!heading.id) heading.id = 'dream-heading-' + index;
        var item = document.createElement('li');
        item.className = 'toc-level-' + heading.tagName.toLowerCase();
        var link = document.createElement('a');
        link.href = '#' + heading.id;
        link.setAttribute('data-no-pjax', '');
        link.innerHTML = '<i class="ri-attachment-2"></i>' + heading.textContent;
        item.appendChild(link);
        return item;
    }

    function initializeSidebarToc() {
        var headings = Array.from(document.querySelectorAll('.main-content:not(.not-toc) h1, .main-content:not(.not-toc) h2, .main-content:not(.not-toc) h3, .main-content:not(.not-toc) h4, .main-content:not(.not-toc) h5'));
        document.querySelectorAll('.widget.toc').forEach(function (toc) {
            var content = toc.querySelector('.toc-content');
            if (!content) return;
            content.replaceChildren();
            if (!headings.length) {
                toc.classList.add('is-hidden-all');
                return;
            }
            var list = document.createElement('ul');
            list.className = 'menu-list';
            headings.forEach(function (heading, index) {
                list.appendChild(buildTocItem(heading, index));
            });
            content.appendChild(list);
            toc.classList.remove('is-hidden-all');
        });
    }

    initializeDrawerToc();
    initializeSidebarToc();
    document.addEventListener('dream2:page-loaded', initializeDrawerToc);
    document.addEventListener('dream2:page-loaded', initializeSidebarToc);

    function scrollToTocTarget(anchor) {
        var target = anchor.hash ? document.getElementById(anchor.hash.slice(1)) : null;
        if (!target) return false;
        document.documentElement.classList.remove('disable-scroll');
        $('.navbar-slideout, .navbar-mask').removeClass('active');
        var navbar = document.querySelector('.navbar-above');
        var offset = (navbar ? navbar.offsetHeight : 0) + 16;
        window.setTimeout(function () {
            window.scrollTo({
                top: Math.max(0, target.getBoundingClientRect().top + window.pageYOffset - offset),
                behavior: 'smooth'
            });
        }, 0);
        window.history.replaceState(window.history.state, '', '#' + target.id);
        return true;
    }

    document.addEventListener('click', function (event) {
        var anchor = event.target && event.target.closest
            ? event.target.closest('.dream-drawer-toc a[href^="#"], .widget.toc .toc-content a[href^="#"]')
            : null;
        if (!anchor || !scrollToTocTarget(anchor)) return;
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
    }, true);

    $(document).on('click', '.dream-drawer-toc a[href^="#"], .widget.toc .toc-content a[href^="#"]', function (event) {
        if (scrollToTocTarget(this)) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    });

    function initializeCodeBlocks() {
        document.querySelectorAll('.article pre:not([data-dream-code-ready])').forEach(function (pre, index) {
            var block = pre.querySelector('code');
            if (!block) {
                block = document.createElement('code');
                block.textContent = pre.textContent.replace(/^\s*<code">\s*/i, '');
                pre.replaceChildren(block);
            }
            pre.dataset.dreamCodeReady = '1';

            if (window.hljs && !block.dataset.highlighted) {
                window.hljs.highlightElement(block);
            }

            var codeLines = block.textContent.replace(/\n$/, '').split('\n');
            var lineDigits = Math.max(2, String(codeLines.length).length);
            var lineNumbers = document.createElement('ul');
            lineNumbers.setAttribute('aria-hidden', 'true');
            codeLines.forEach(function (_, lineIndex) {
                var line = document.createElement('li');
                line.textContent = String(lineIndex + 1).padStart(lineDigits, '0');
                lineNumbers.appendChild(line);
            });
            pre.insertBefore(lineNumbers, block);

            var codeId = 'dream-code-' + index + '-' + Date.now();
            block.id = codeId;

            var figure = document.createElement('figure');
            figure.className = 'hljs';
            var caption = document.createElement('figcaption');
            var controls = document.createElement('div');
            var collapse = document.createElement('i');
            collapse.className = 'ri-arrow-down-s-line';
            collapse.setAttribute('role', 'button');
            collapse.setAttribute('tabindex', '0');
            collapse.setAttribute('title', '收起代码');
            collapse.dataset.code = '#' + codeId;
            var copy = document.createElement('i');
            copy.className = 'ri-file-copy-2-line btn-clipboard';
            copy.setAttribute('role', 'button');
            copy.setAttribute('tabindex', '0');
            copy.setAttribute('title', '复制代码');
            copy.dataset.clipboardTarget = '#' + codeId;
            controls.append(collapse, copy);
            caption.appendChild(controls);

            pre.parentNode.insertBefore(figure, pre);
            figure.append(caption, pre);

            collapse.setAttribute('aria-label', '收起代码');

            if (DreamConfig.code_fold_line > 0 && codeLines.length > DreamConfig.code_fold_line) {
                figure.classList.add('fold');
                var expand = document.createElement('div');
                expand.className = 'expand-done';
                expand.setAttribute('role', 'button');
                expand.setAttribute('tabindex', '0');
                expand.setAttribute('aria-label', '展开代码');
                expand.innerHTML = '<i class="ri-arrow-up-double-line" aria-hidden="true"></i>';
                pre.appendChild(expand);
            }
        });

        var ClipboardConstructor = window['ClipboardJS'];
        if (ClipboardConstructor && !window['clipboard']) {
            window['clipboard'] = new ClipboardConstructor('.btn-clipboard');
            window['clipboard'].on('error', function (event) {
                event.clearSelection();
                if (window.Qmsg) window.Qmsg.error('您的浏览器不支持复制');
            });
            window['clipboard'].on('success', function (event) {
                event.clearSelection();
                if (window.Qmsg) window.Qmsg.success('复制成功');
            });
        }
    }

    initializeCodeBlocks();
    document.addEventListener('dream2:page-loaded', initializeCodeBlocks);
    document.addEventListener('dream2:page-leaving', function () {
        if (window.clipboard && typeof window.clipboard.destroy === 'function') {
            window.clipboard.destroy();
            delete window.clipboard;
        }
    });

    $(document).on('click', 'figure > figcaption .ri-arrow-down-s-line', function () {
        var icon = $(this);
        var pre = $(icon.attr('data-code')).parent();
        if (icon.is('.close')) {
            pre.slideDown(200);
            icon.removeClass('close').attr({'title': '收起代码', 'aria-label': '收起代码'});
        } else {
            pre.slideUp(200);
            icon.addClass('close').attr({'title': '展开代码', 'aria-label': '展开代码'});
        }
    });

    $(document).on('keydown', 'figure > figcaption .ri-arrow-down-s-line', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            $(this).trigger('click');
        }
    });

    function toggleDreamFoldBlock(figure) {
        var oldHeight = figure.outerHeight();
        if (figure.is('.fold')) {
            figure.removeClass('fold').addClass('unfold');
        } else {
            var scrollTop = document.documentElement.scrollTop || document.body.scrollTop || window.pageYOffset;
            figure.addClass('fold').removeClass('unfold');
            $('body,html').scrollTop(scrollTop - oldHeight + figure.outerHeight());
        }
        figure.find('> pre > .expand-done').attr('aria-label', figure.is('.fold') ? '展开代码' : '收起代码');
    }

    $(document).on('click', 'figure > pre > .expand-done', function () {
        toggleDreamFoldBlock($(this).parent().parent());
    });

    $(document).on('keydown', 'figure > pre > .expand-done', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            $(this).trigger('click');
        }
    });

    document.querySelectorAll('.article p').forEach(function (paragraph) {
        if (!paragraph.querySelector('img:not(.not-gallery)')) {
            return;
        }
        while (paragraph.firstChild) {
            var child = paragraph.firstChild;
            if (child.nodeType === Node.ELEMENT_NODE && child.tagName === 'BR') {
                child.remove();
                continue;
            }
            paragraph.parentNode.insertBefore(child, paragraph);
        }
        paragraph.remove();
    });

    document.querySelectorAll('.article img:not(.not-gallery)').forEach(function (image) {
        if (!image.closest('[data-fancybox], mew-photos, .gallery-item')) {
            var gallery = document.createElement('div');
            gallery.className = 'gallery-item';
            var galleryInner = document.createElement('div');
            galleryInner.setAttribute('data-fancybox', 'gallery');
            galleryInner.setAttribute('href', image.currentSrc || image.src);
            if (image.alt) {
                galleryInner.setAttribute('data-caption', image.alt);
            }
            image.parentNode.insertBefore(gallery, image);
            galleryInner.appendChild(image);
            gallery.appendChild(galleryInner);
            if (DreamConfig.show_img_name && image.alt) {
                var caption = document.createElement('p');
                caption.className = 'dream-image-name';
                caption.textContent = image.alt;
                gallery.appendChild(caption);
            }
        }
        if (image.hasAttribute('width') && image.hasAttribute('height')) {
            image.style.height = image.getAttribute('height') + 'px';
        }
        image.addEventListener('load', function () {
            if (DreamConfig.img_fold_height > 0 && image.naturalHeight > DreamConfig.img_fold_height) {
                var wrapper = image.closest('.dream-folded-image');
                if (!wrapper) {
                    wrapper = document.createElement('div');
                    wrapper.className = 'dream-folded-image';
                    image.parentNode.insertBefore(wrapper, image);
                    wrapper.appendChild(image);
                    wrapper.addEventListener('click', function () {
                        wrapper.classList.toggle('dream-fold-open');
                    });
                }
            }
        });
        if (image.complete) {
            image.dispatchEvent(new Event('load'));
        }
    });

    function initializeKatex(root) {
        if (!window.katex) return;
        (root || document).querySelectorAll('.article .katex-inline:not([data-dream-katex-ready]), .article .katex-block:not([data-dream-katex-ready])').forEach(function (element) {
            var formula = element.textContent;
            element.dataset.dreamKatexReady = '1';
            if (formula.length > 10000) return;
            try {
                window.katex.render(formula, element, {
                    displayMode: element.classList.contains('katex-block'),
                    throwOnError: false,
                    trust: false,
                    maxExpand: 1000,
                    maxSize: 50
                });
            } catch (ignore) {}
        });
    }
    initializeKatex(document);
    document.addEventListener('dream2:page-loaded', function () { initializeKatex(document); });

    $(document).on('click', '.dream-copy-link', function () {
        var url = this.dataset.url || location.href;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url);
        } else {
            var input = document.createElement('textarea');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            input.remove();
        }
        this.setAttribute('title', '链接已复制');
    });

    $(document).on('click', '.dream-native-share', function () {
        var data = { title: this.dataset.title || document.title, url: this.dataset.url || location.href };
        if (navigator.share) {
            navigator.share(data).catch(function () {});
        } else {
            $('.dream-copy-link').first().trigger('click');
        }
    });

    function initializePhotos(root) {
        var gallery = $(root || document).find('.photos-gallery:not([data-gallery-ready])');
        if (!gallery.length || !$.fn.justifiedGallery) return;
        gallery.attr('data-gallery-ready', '1').justifiedGallery({
            rowHeight: 200,
            maxRowHeight: false,
            lastRow: 'nojustify',
            captions: false,
            waitThumbnailsLoad: true,
            margins: 10,
            cssAnimation: false
        }).removeClass('loading');
        if ($.fancybox) $('[data-fancybox="gallery"]').fancybox();
    }
    initializePhotos(document);
    $(window).on('load', function () { initializePhotos(document); });
    window.setTimeout(function () { initializePhotos(document); }, 500);
    document.addEventListener('dream2:page-loaded', function () { initializePhotos(document); });

    if (Dream2WP.enablePjax && window.DOMParser && window.history && window.fetch) {
        var pjaxLoading = false;
        var pjaxProgress = function () {
            return window['DProgress'];
        };
        var pjaxProgressEnabled = function () {
            return window.DreamConfig.load_progress !== 'none' && pjaxProgress();
        };
        var pjaxProgressStart = function () {
            if (!pjaxProgressEnabled()) {
                document.documentElement.classList.remove('dream-dprogress-active');
                return;
            }
            document.documentElement.classList.add('dream-dprogress-active');
            pjaxProgress().start();
        };
        var pjaxProgressInc = function () {
            if (pjaxProgressEnabled()) {
                pjaxProgress().inc();
            }
        };
        var pjaxProgressDone = function () {
            if (pjaxProgressEnabled()) {
                pjaxProgress().done();
            }
            document.documentElement.classList.remove('dream-dprogress-active');
        };
        var syncPjaxAssets = function (nextDocument) {
            var currentStyles = new Set(Array.from(document.querySelectorAll('link[rel="stylesheet"][href]')).map(function (link) { return link.href; }));
            var nextStyleUrls = new Set(Array.from(nextDocument.querySelectorAll('link[rel="stylesheet"][href]')).map(function (link) { return link.href; }));
            var obsoleteStyles = Array.from(document.querySelectorAll('link[data-dream-pjax-managed="1"][rel="stylesheet"][href]')).filter(function (link) {
                return !nextStyleUrls.has(link.href);
            });
            var styleLoads = Array.from(nextDocument.querySelectorAll('link[rel="stylesheet"][href]')).filter(function (link) {
                return !currentStyles.has(link.href);
            }).map(function (link) {
                return new Promise(function (resolve) {
                    var clone = document.createElement('link');
                    Array.from(link.attributes).forEach(function (attribute) {
                        clone.setAttribute(attribute.name, attribute.value);
                    });
                    clone.onload = clone.onerror = function () {
                        pjaxProgressInc();
                        resolve();
                    };
                    document.head.appendChild(clone);
                });
            });
            var currentScripts = new Set(Array.from(document.scripts).filter(function (script) { return script.src; }).map(function (script) { return script.src; }));
            var scripts = Array.from(nextDocument.querySelectorAll('script[src]')).filter(function (script) {
                return !currentScripts.has(script.src) && !script.src.includes('/wordpress-port.js');
            });
            var scriptLoads = scripts.reduce(function (chain, script) {
                return chain.then(function () {
                    return new Promise(function (resolve) {
                        var clone = document.createElement('script');
                        Array.from(script.attributes).forEach(function (attribute) {
                            if (attribute.name !== 'src') clone.setAttribute(attribute.name, attribute.value);
                        });
                        clone.async = false;
                        clone.src = script.src;
                        clone.onload = clone.onerror = function () {
                            pjaxProgressInc();
                            resolve();
                        };
                        document.body.appendChild(clone);
                    });
                });
            }, Promise.resolve());
            return Promise.all([Promise.all(styleLoads), scriptLoads]).then(function () {
                return function () {
                    obsoleteStyles.forEach(function (link) { link.remove(); });
                };
            });
        };
        var syncPjaxShell = function (nextDocument) {
            var currentNav = document.querySelector('.navbar-nav');
            var nextNav = nextDocument.querySelector('.navbar-nav');
            if (currentNav && nextNav) {
                currentNav.innerHTML = nextNav.innerHTML;
            }

            var currentSlideout = document.querySelector('.navbar-slideout');
            var nextSlideout = nextDocument.querySelector('.navbar-slideout');
            if (currentSlideout && nextSlideout) {
                currentSlideout.replaceWith(nextSlideout);
            }

            document.documentElement.classList.remove('disable-scroll');
            document.querySelectorAll('.navbar-mask, .navbar-slideout').forEach(function (element) {
                element.classList.remove('active');
            });
        };
        var loadPjaxPage = function (url, push) {
            if (pjaxLoading) {
                return;
            }
            pjaxLoading = true;
            document.documentElement.classList.add('dream-pjax-loading');
            pjaxProgressStart();
            window.fetch(url, { credentials: 'same-origin', headers: { 'X-Dream-PJAX': '1' } })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('PJAX request failed');
                    }
                    return response.text();
                })
                .then(function (html) {
                    var nextDocument = new window.DOMParser().parseFromString(html, 'text/html');
                    var currentSection = document.querySelector('.section');
                    var nextSection = nextDocument.querySelector('.section');
                    if (!currentSection || !nextSection) {
                        window.location.href = url;
                        return;
                    }
                    return syncPjaxAssets(nextDocument).then(function (cleanupStyles) {
                        document.dispatchEvent(new CustomEvent('dream2:page-leaving'));
                        currentSection.replaceWith(nextSection);
                        cleanupStyles();
                        document.title = nextDocument.title;
                        document.body.className = nextDocument.body.className;
                        syncPjaxShell(nextDocument);
                        if (push) {
                            window.history.pushState({ dreamPjax: true }, '', url);
                        }
                        window.scrollTo({ top: 0, behavior: 'auto' });
                        document.dispatchEvent(new CustomEvent('dream2:page-loaded'));
                    });
                })
                .catch(function () {
                    window.location.href = url;
                })
                .finally(function () {
                    pjaxLoading = false;
                    pjaxProgressDone();
                    document.documentElement.classList.remove('dream-pjax-loading');
                });
        };

        $(document).on('click', 'a[href]', function (event) {
            var anchor = this;
            var url;
            var rawHref = anchor.getAttribute('href');
            if (!rawHref || rawHref.charAt(0) === '#' || anchor.hasAttribute('data-no-pjax') || anchor.closest('.widget.toc, .dream-drawer-toc, .navbar-slideout-menu .panel')) {
                return;
            }
            try {
                url = new URL(anchor.href, location.href);
            } catch (ignore) {
                return;
            }
            if (url.hash && url.origin === location.origin && url.pathname === location.pathname && url.search === location.search) {
                return;
            }
            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ||
                anchor.target || anchor.hasAttribute('download') || url.origin !== location.origin ||
                url.href === location.href || url.hash && url.pathname === location.pathname || url.pathname.startsWith('/wp-admin') || url.pathname.startsWith('/wp-login')) {
                return;
            }
            event.preventDefault();
            loadPjaxPage(url.href, true);
        });

        window.addEventListener('popstate', function () {
            loadPjaxPage(location.href, false);
        });
    }

    function initializeCarousels(root) {
        if (!window.Swiper) return;
        (root || document).querySelectorAll('.dream-carousel:not([data-dream-swiper-ready])').forEach(function (carousel) {
            carousel.dataset.dreamSwiperReady = '1';
            carousel._dreamSwiper = new window.Swiper(carousel, {
                loop: true,
                speed: 650,
                autoplay: { delay: 5000, disableOnInteraction: false },
                pagination: { el: carousel.querySelector('.swiper-pagination'), clickable: true },
                navigation: {
                    nextEl: carousel.querySelector('.swiper-button-next'),
                    prevEl: carousel.querySelector('.swiper-button-prev')
                }
            });
        });
    }
    initializeCarousels(document);
    document.addEventListener('dream2:page-loaded', function () { initializeCarousels(document); });
    document.addEventListener('dream2:page-leaving', function () {
        document.querySelectorAll('.dream-carousel').forEach(function (carousel) {
            if (carousel._dreamSwiper && typeof carousel._dreamSwiper.destroy === 'function') {
                carousel._dreamSwiper.destroy(true, true);
                carousel._dreamSwiper = null;
            }
        });
    });

    if (Dream2WP.copyExplain) {
        document.addEventListener('copy', function (event) {
            if (document.querySelector('.dream-links-page')) {
                return;
            }
            var text = window.getSelection().toString();
            if (!text || !event.clipboardData) {
                return;
            }
            event.preventDefault();
            event.clipboardData.setData('text/plain', text + '\n' + Dream2WP.copyExplain);
        });
    }

    if (Dream2WP.hiddenTitle || Dream2WP.visibleTitle) {
        var originalTitle = document.title;
        document.addEventListener('visibilitychange', function () {
            document.title = document.hidden ?
                (Dream2WP.hiddenTitle || originalTitle) :
                (Dream2WP.visibleTitle || originalTitle);
            if (!document.hidden) {
                window.setTimeout(function () {
                    document.title = originalTitle;
                }, 1600);
            }
        });
    }

    function startElementTimer(element, render) {
        if (element.dataset.dreamTimerReady === '1') return;
        element.dataset.dreamTimerReady = '1';
        render();
        element._dreamTimer = window.setInterval(function () {
            if (!element.isConnected) {
                window.clearInterval(element._dreamTimer);
                return;
            }
            render();
        }, 1000);
    }
    function initializeTimeCounters(root) {
        var scope = root || document;
        var websiteDate = scope.querySelector('#websiteDate[data-start]');
        if (websiteDate) {
            var websiteStart = new Date(websiteDate.dataset.start.replace(/-/g, '/'));
            if (!isNaN(websiteStart.getTime())) {
                startElementTimer(websiteDate, function () {
                    var seconds = Math.max(0, Math.floor((Date.now() - websiteStart.getTime()) / 1000));
                    var days = Math.floor(seconds / 86400);
                    var hours = Math.floor(seconds % 86400 / 3600);
                    var minutes = Math.floor(seconds % 3600 / 60);
                    var remain = seconds % 60;
                    websiteDate.innerHTML = '建站<span class="stand">' + days + '</span>天<span class="stand">' +
                        hours + '</span>时<span class="stand">' + minutes + '</span>分<span class="stand">' +
                        remain + '</span>秒';
                });
            }
        }
        scope.querySelectorAll('.dream-love-time[data-time]').forEach(function (element) {
            var loveStart = new Date(element.dataset.time.replace(/-/g, '/'));
            if (isNaN(loveStart.getTime())) return;
            startElementTimer(element, function () {
                var seconds = Math.max(0, Math.floor((Date.now() - loveStart.getTime()) / 1000));
                var days = Math.floor(seconds / 86400);
                var hours = Math.floor(seconds % 86400 / 3600);
                var minutes = Math.floor(seconds % 3600 / 60);
                element.textContent = days + ' 天 ' + hours + ' 时 ' + minutes + ' 分 ' + seconds % 60 + ' 秒';
            });
        });
    }
    initializeTimeCounters(document);
    document.addEventListener('dream2:page-loaded', function () { initializeTimeCounters(document); });
    document.addEventListener('dream2:page-leaving', function () {
        var section = document.querySelector('.section');
        if (!section) return;
        section.querySelectorAll('[data-dream-timer-ready="1"]').forEach(function (element) {
            window.clearInterval(element._dreamTimer);
        });
    });
})(jQuery);
