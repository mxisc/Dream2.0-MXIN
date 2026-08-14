(function () {
    'use strict';

    document.querySelectorAll('.dream2-emoji-manager').forEach(function (manager) {
        var dataInput = manager.querySelector('.dream2-emoji-data');
        var groupList = manager.querySelector('.dream2-emoji-groups');
        var fileInput = manager.querySelector('.dream2-emoji-files');
        var groups = [];
        var activeGroup = 0;
        var expandedGroups = Object.create(null);
        try { groups = JSON.parse(manager.dataset.groups || '[]'); } catch (error) { groups = []; }

        function sync() { dataInput.value = JSON.stringify(groups); }
        function slug() { return 'group-' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7); }
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
                render();
            });
        }
        function render() {
            groupList.textContent = '';
            groups.forEach(function (group, groupIndex) {
                if (!group.slug) group.slug = slug();
                var card = document.createElement('section');
                card.className = 'dream2-emoji-group' + (groupIndex === activeGroup ? ' is-active' : '');
                card.addEventListener('click', function () { activeGroup = groupIndex; });

                var header = document.createElement('div');
                header.className = 'dream2-emoji-group-header';
                var name = document.createElement('input');
                name.type = 'text'; name.maxLength = 12; name.value = group.name || ''; name.setAttribute('aria-label', '分组名称');
                name.addEventListener('input', function () { groups[groupIndex].name = name.value; sync(); });
                header.appendChild(name);
                var bodyId = 'dream2-emoji-group-' + group.slug;
                var expanded = expandedGroups[group.slug] === true;
                var toggle = button(expanded ? '收起' : '展开', 'dream2-emoji-toggle');
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                toggle.setAttribute('aria-controls', bodyId);
                toggle.addEventListener('click', function () { expandedGroups[group.slug] = !expanded; render(); });
                header.appendChild(toggle);
                var up = button('前移', 'dream2-emoji-move-up');
                up.disabled = groupIndex === 0;
                up.addEventListener('click', function () { var item = groups.splice(groupIndex, 1)[0]; groups.splice(groupIndex - 1, 0, item); activeGroup = groupIndex - 1; render(); });
                header.appendChild(up);
                var down = button('后移', 'dream2-emoji-move-down');
                down.disabled = groupIndex === groups.length - 1;
                down.addEventListener('click', function () { var item = groups.splice(groupIndex, 1)[0]; groups.splice(groupIndex + 1, 0, item); activeGroup = groupIndex + 1; render(); });
                header.appendChild(down);
                var removeGroup = button('删除分组', 'dream2-emoji-remove-group');
                removeGroup.addEventListener('click', function () { groups.splice(groupIndex, 1); activeGroup = Math.max(0, Math.min(activeGroup, groups.length - 1)); render(); });
                header.appendChild(removeGroup);
                card.appendChild(header);

                var body = document.createElement('div');
                body.id = bodyId;
                body.className = 'dream2-emoji-group-body';
                body.hidden = !expanded;

                var path = document.createElement('code');
                path.className = 'dream2-emoji-path';
                path.textContent = 'uploads/dream2-mxin/emoji/' + group.slug;
                body.appendChild(path);

                var items = document.createElement('div');
                items.className = 'dream2-emoji-items';
                (group.items || []).forEach(function (emoji, emojiIndex) {
                    var row = document.createElement('div'); row.className = 'dream2-emoji-item';
                    var image = document.createElement('img'); image.src = emoji.url; image.alt = emoji.code; row.appendChild(image);
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
                body.appendChild(items); card.appendChild(body); groupList.appendChild(card);
            });
            sync();
        }

        manager.querySelector('.dream2-emoji-add-group').addEventListener('click', function () {
            groups.push({ name: '新分组', slug: slug(), items: [] }); activeGroup = groups.length - 1; render();
        });
        manager.querySelector('.dream2-emoji-import-files').addEventListener('click', function () {
            if (!groups.length) { groups.push({ name: '默认', slug: slug(), items: [] }); activeGroup = 0; render(); }
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
