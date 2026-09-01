(function () {
    'use strict';

    document.querySelectorAll('.dream2-emoji-manager').forEach(function (manager) {
        var dataInput = manager.querySelector('.dream2-emoji-data');
        var groupList = manager.querySelector('.dream2-emoji-groups');
        var fileInput = manager.querySelector('.dream2-emoji-files');
        var groups = [];
        var activeGroup = 0;
        var expandedGroupSlug = '';
        var draggedGroup = -1;
        try { groups = JSON.parse(manager.dataset.groups || '[]'); } catch (error) { groups = []; }

        function sync() { dataInput.value = JSON.stringify(groups); }
        function slug() { return 'group-' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7); }
        function focusDragHandle(groupSlug) {
            window.requestAnimationFrame(function () {
                var card = Array.prototype.find.call(groupList.children, function (item) { return item.dataset.groupSlug === groupSlug; });
                var handle = card && card.querySelector('.dream2-emoji-drag-handle');
                if (handle) handle.focus();
            });
        }
        function scrollGroupToStart(groupSlug) {
            window.requestAnimationFrame(function () {
                var card = Array.prototype.find.call(groupList.children, function (item) { return item.dataset.groupSlug === groupSlug; });
                if (!card) return;
                groupList.scrollTop = Math.max(0, groupList.scrollTop + card.getBoundingClientRect().top - groupList.getBoundingClientRect().top);
            });
        }
        function moveGroup(groupIndex, offset) {
            var targetIndex = groupIndex + offset;
            if (targetIndex < 0 || targetIndex >= groups.length) return;
            var item = groups.splice(groupIndex, 1)[0];
            groups.splice(targetIndex, 0, item);
            activeGroup = targetIndex;
            render();
            focusDragHandle(item.slug);
        }
        function request(action, values) {
            var body = values instanceof FormData ? values : new FormData();
            body.set('action', action);
            body.set('nonce', window.Dream2EmojiAdmin.nonce);
            if (!(values instanceof FormData)) Object.keys(values || {}).forEach(function (key) { body.set(key, values[key]); });
            return fetch(window.Dream2EmojiAdmin.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' }).then(function (response) { return response.json(); });
        }
        function button(label, className) {
            var element = document.createElement('button');
            element.type = 'button';
            element.className = 'button ' + className;
            element.textContent = label;
            return element;
        }
        function scan() {
            return request('dream2_mxin_scan_emojis', { groups: JSON.stringify(groups) }).then(function (result) {
                if (!result.success) throw new Error(result.data && result.data.message || 'scan_failed');
                groups = result.data.groups || [];
                activeGroup = Math.max(0, Math.min(activeGroup, groups.length - 1));
                if (!groups.some(function (group) { return group.slug === expandedGroupSlug; })) expandedGroupSlug = '';
                render();
            });
        }
        function render() {
            groupList.textContent = '';
            groups.forEach(function (group, groupIndex) {
                if (!group.slug) group.slug = slug();
                var expanded = group.slug === expandedGroupSlug;
                var card = document.createElement('section');
                card.className = 'dream2-emoji-group' + (expanded ? ' is-expanded' : '');
                card.dataset.groupSlug = group.slug;
                card.addEventListener('click', function () { activeGroup = groupIndex; });

                var header = document.createElement('div');
                header.className = 'dream2-emoji-group-header';
                var dragHandle = document.createElement('button');
                dragHandle.type = 'button';
                dragHandle.className = 'dream2-emoji-drag-handle';
                dragHandle.setAttribute('aria-label', '拖动排序');
                dragHandle.setAttribute('title', '拖动排序，可使用上下方向键调整');
                dragHandle.addEventListener('pointerdown', function () { card.draggable = true; dragHandle.focus(); });
                dragHandle.addEventListener('pointerup', function () { card.draggable = false; });
                dragHandle.addEventListener('pointercancel', function () { card.draggable = false; });
                dragHandle.addEventListener('keydown', function (event) {
                    if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') return;
                    event.preventDefault();
                    event.stopPropagation();
                    moveGroup(groupIndex, event.key === 'ArrowUp' ? -1 : 1);
                });
                card.addEventListener('dragstart', function (event) {
                    draggedGroup = groupIndex;
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', group.slug);
                    card.classList.add('is-dragging');
                });
                card.addEventListener('dragend', function () {
                    card.draggable = false;
                    draggedGroup = -1;
                    card.classList.remove('is-dragging');
                    groupList.querySelectorAll('.dream2-emoji-group.is-drag-over').forEach(function (item) { item.classList.remove('is-drag-over'); });
                });
                header.appendChild(dragHandle);
                var name = document.createElement('input');
                name.type = 'text'; name.maxLength = 12; name.value = group.name || ''; name.setAttribute('aria-label', '分组名称');
                name.addEventListener('input', function () { groups[groupIndex].name = name.value; sync(); });
                header.appendChild(name);
                var count = document.createElement('span');
                count.className = 'dream2-emoji-count';
                count.textContent = (group.items || []).length + ' 个表情';
                header.appendChild(count);
                var bodyId = 'dream2-emoji-group-' + group.slug;
                var removeGroup = button('删除分组', 'dream2-emoji-remove-group');
                removeGroup.addEventListener('click', function () {
                    groups.splice(groupIndex, 1);
                    if (expandedGroupSlug === group.slug) expandedGroupSlug = '';
                    activeGroup = Math.max(0, Math.min(activeGroup, groups.length - 1));
                    render();
                });
                header.appendChild(removeGroup);
                var toggle = button(expanded ? '收起' : '展开', 'dream2-emoji-toggle');
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                toggle.addEventListener('click', function () {
                    expandedGroupSlug = expanded ? '' : group.slug;
                    activeGroup = groupIndex;
                    render();
                    if (!expanded) scrollGroupToStart(group.slug);
                });
                header.appendChild(toggle);
                card.appendChild(header);
                card.addEventListener('dragover', function (event) {
                    if (draggedGroup < 0 || draggedGroup === groupIndex) return;
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                    card.classList.add('is-drag-over');
                });
                card.addEventListener('dragleave', function () { card.classList.remove('is-drag-over'); });
                card.addEventListener('drop', function (event) {
                    event.preventDefault();
                    if (draggedGroup < 0 || draggedGroup === groupIndex) return;
                    var activeSlug = groups[activeGroup] && groups[activeGroup].slug;
                    var targetSlug = group.slug;
                    var movedGroup = groups.splice(draggedGroup, 1)[0];
                    var targetIndex = groups.findIndex(function (item) { return item.slug === targetSlug; });
                    groups.splice(targetIndex < 0 ? groups.length : targetIndex, 0, movedGroup);
                    activeGroup = Math.max(0, groups.findIndex(function (item) { return item.slug === activeSlug; }));
                    draggedGroup = -1;
                    sync();
                    render();
                });
                groupList.appendChild(card);
                if (!expanded) return;

                var body = document.createElement('div');
                body.id = bodyId;
                body.className = 'dream2-emoji-group-body';

                var path = document.createElement('code');
                path.className = 'dream2-emoji-path';
                path.textContent = 'uploads/dream2-mxin/emoji/' + group.slug;
                body.appendChild(path);

                var items = document.createElement('div');
                items.className = 'dream2-emoji-items';
                var groupItems = group.items || [];
                groupItems.forEach(function (emoji, emojiIndex) {
                    var row = document.createElement('div'); row.className = 'dream2-emoji-item';
                    var image = document.createElement('img'); image.src = emoji.url; image.alt = emoji.code; image.loading = 'lazy'; image.decoding = 'async'; row.appendChild(image);
                    var code = document.createElement('input'); code.type = 'text'; code.maxLength = 40; code.value = emoji.code; code.setAttribute('aria-label', '表情代码');
                    code.addEventListener('input', function () { groups[groupIndex].items[emojiIndex].code = code.value; sync(); });
                    row.appendChild(code);
                    var remove = button('删除', 'dream2-emoji-remove');
                    remove.addEventListener('click', function () {
                        request('dream2_mxin_delete_emoji', { group: group.slug, file: emoji.file || '' }).then(function (result) {
                            if (!result.success) throw new Error('delete_failed');
                            groups[groupIndex].items.splice(emojiIndex, 1); render();
                        });
                    });
                    row.appendChild(remove); items.appendChild(row);
                });
                body.appendChild(items);
                card.appendChild(body);
            });
            sync();
        }

        manager.querySelector('.dream2-emoji-add-group').addEventListener('click', function () {
            var group = { name: '新分组', slug: slug(), items: [] };
            groups.push(group); activeGroup = groups.length - 1; expandedGroupSlug = group.slug; render();
        });
        manager.querySelector('.dream2-emoji-import-files').addEventListener('click', function () {
            if (!groups.length) {
                groups.push({ name: '默认', slug: slug(), items: [] });
                activeGroup = 0;
                expandedGroupSlug = groups[0].slug;
                render();
            }
            fileInput.click();
        });
        fileInput.addEventListener('change', function () {
            if (!fileInput.files.length || !groups[activeGroup]) return;
            var upload = new FormData(); upload.set('group', groups[activeGroup].slug);
            Array.prototype.forEach.call(fileInput.files, function (file) { upload.append('emoji_files[]', file); });
            request('dream2_mxin_upload_emojis', upload).then(function (result) {
                if (!result.success) throw new Error(result.data && result.data.message || 'upload_failed');
                fileInput.value = ''; return scan();
            });
        });
        manager.querySelector('.dream2-emoji-scan').addEventListener('click', scan);
        render();
    });
}());
