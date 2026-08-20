(function () {
    'use strict';

    var STORAGE_KEY = 'dream2_visitor_save_v1';
    var SESSION_KEY = 'dream2_visitor_session_v1';
    var SCHEMA_VERSION = 1;
    var LEVEL_ENABLED = !!(window.Dream2WP && Dream2WP.visitorLevelEnabled);
    var LEVEL_NOTICE_ENABLED = !!(window.Dream2WP && Dream2WP.visitorLevelNotice);
    var ACHIEVEMENTS_ENABLED = !!(window.Dream2WP && Dream2WP.visitorAchievementsEnabled);
    var ACHIEVEMENT_NOTICE_ENABLED = !!(window.Dream2WP && Dream2WP.visitorAchievementNotice);
    var ACHIEVEMENTS = [
        { id: 'first_visit', name: '初次登录', description: '第一次访问博客', rarity: '普通', className: 'common', test: function (save) { return save.visits >= 1; } },
        { id: 'read_5', name: '开始刷本', description: '阅读 5 篇文章', rarity: '普通', className: 'common', test: function (save) { return Object.keys(save.articles).length >= 5; } },
        { id: 'read_10', name: '档案猎手', description: '阅读 10 篇文章', rarity: '稀有', className: 'rare', test: function (save) { return Object.keys(save.articles).length >= 10; } },
        { id: 'categories_5', name: '跨区探索', description: '发现 5 个分类', rarity: '稀有', className: 'rare', test: function (save) { return Object.keys(save.categories).length >= 5; } },
        { id: 'visits_7', name: '常驻账号', description: '累计访问 7 个会话', rarity: '稀有', className: 'rare', test: function (save) { return save.visits >= 7; } },
        { id: 'level_5', name: '脱离游客区', description: '达到 LV.5', rarity: '史诗', className: 'epic', test: function (save) { return save.level >= 5; } },
        { id: 'logo_node', name: '入口之外', description: '发现 Logo 隐藏节点', rarity: '隐藏', className: 'hidden', test: function (save) { return !!save.hiddenNodes.logo; } },
        { id: 'night_session', name: '夜间协议', description: '凌晨 0–4 点访问博客', rarity: '隐藏', className: 'hidden', test: function () { var hour = new Date().getHours(); return hour >= 0 && hour < 5; } }
    ];

    function now() {
        return new Date().toISOString();
    }

    function visitorId() {
        var bytes;
        if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
            bytes = new Uint8Array(16);
            window.crypto.getRandomValues(bytes);
            return Array.from(bytes, function (byte) {
                return byte.toString(16).padStart(2, '0');
            }).join('');
        }
        return 'visitor-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 12);
    }

    function freshSave() {
        var timestamp = now();
        return {
            version: SCHEMA_VERSION,
            visitorId: visitorId(),
            createdAt: timestamp,
            lastVisitAt: timestamp,
            visits: 0,
            pageViews: 0,
            xp: 0,
            level: 1,
            rank: '游客',
            pages: {},
            articles: {},
            categories: {},
            achievements: {},
            hiddenNodes: {},
            completedTasks: {},
            title: '',
            impression: ''
        };
    }

    function objectValue(value) {
        return value && typeof value === 'object' && !Array.isArray(value) ? value : {};
    }

    function normalizeSave(value) {
        var fallback = freshSave();
        if (!value || value.version !== SCHEMA_VERSION) {
            return fallback;
        }
        var pages = objectValue(value.pages);
        var articles = objectValue(value.articles);
        var categories = objectValue(value.categories);
        var xp = Number(value.xp);
        if (!Number.isFinite(xp) || xp < 0) {
            xp = Math.max(0, Number(value.visits) || 0) * 5 + Object.keys(pages).length * 2 +
                Object.keys(articles).length * 20 + Object.keys(categories).length * 10;
        }
        var normalized = {
            version: SCHEMA_VERSION,
            visitorId: typeof value.visitorId === 'string' && value.visitorId ? value.visitorId : fallback.visitorId,
            createdAt: typeof value.createdAt === 'string' ? value.createdAt : fallback.createdAt,
            lastVisitAt: typeof value.lastVisitAt === 'string' ? value.lastVisitAt : fallback.lastVisitAt,
            visits: Math.max(0, Number(value.visits) || 0),
            pageViews: Math.max(0, Number(value.pageViews) || 0),
            xp: Math.floor(xp),
            level: 1,
            rank: '游客',
            pages: pages,
            articles: articles,
            categories: categories,
            achievements: objectValue(value.achievements),
            hiddenNodes: objectValue(value.hiddenNodes),
            completedTasks: objectValue(value.completedTasks),
            title: typeof value.title === 'string' ? value.title : '',
            impression: typeof value.impression === 'string' ? value.impression : ''
        };
        syncLevel(normalized);
        return normalized;
    }

    function levelThreshold(level) {
        return 10 * Math.pow(Math.max(0, level - 1), 2);
    }

    function levelForXp(xp) {
        return Math.min(30, Math.floor(Math.sqrt(Math.max(0, xp) / 10)) + 1);
    }

    function rankForLevel(level) {
        if (level >= 30) return '管理员';
        if (level >= 20) return '骇客';
        if (level >= 10) return '常客';
        if (level >= 5) return '探索者';
        return '游客';
    }

    function syncLevel(save) {
        save.level = levelForXp(save.xp);
        save.rank = rankForLevel(save.level);
    }

    function addXp(save, amount) {
        if (!LEVEL_ENABLED || amount <= 0) return;
        save.xp += amount;
        syncLevel(save);
    }

    function readSave() {
        try {
            return normalizeSave(JSON.parse(window.localStorage.getItem(STORAGE_KEY)));
        } catch (error) {
            return freshSave();
        }
    }

    function writeSave(save) {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(save));
            return true;
        } catch (error) {
            return false;
        }
    }

    function recordEntry(collection, key, timestamp) {
        var entry = objectValue(collection[key]);
        var isNew = typeof entry.firstSeenAt !== 'string';
        collection[key] = {
            firstSeenAt: typeof entry.firstSeenAt === 'string' ? entry.firstSeenAt : timestamp,
            lastSeenAt: timestamp,
            views: Math.max(0, Number(entry.views) || 0) + 1
        };
        return isNew;
    }

    function currentArticle() {
        if (!document.body.classList.contains('single-post')) {
            return null;
        }
        var article = document.querySelector('article[id^="post-"]');
        if (!article) {
            return null;
        }
        var match = article.id.match(/^post-(\d+)$/);
        if (!match) {
            return null;
        }
        return {
            id: match[1],
            categories: Array.from(article.classList).filter(function (className) {
                return className.indexOf('category-') === 0;
            }).map(function (className) {
                return className.slice(9);
            })
        };
    }

    function recordPage() {
        var save = readSave();
        var timestamp = now();
        var pageKey = window.location.pathname.replace(/\/+$/, '') || '/';
        var oldLevel = save.level;

        save.lastVisitAt = timestamp;
        save.pageViews += 1;
        if (recordEntry(save.pages, pageKey, timestamp)) addXp(save, 2);

        var article = currentArticle();
        if (article) {
            if (recordEntry(save.articles, article.id, timestamp)) addXp(save, 20);
            article.categories.forEach(function (category) {
                if (recordEntry(save.categories, category, timestamp)) addXp(save, 10);
            });
        }

        var unlockedAchievements = unlockAchievements(save);
        writeSave(save);
        document.documentElement.dataset.dreamSaveArticle = article ? article.id : '';
        document.dispatchEvent(new CustomEvent('dream2:save-updated', { detail: save }));
        if (save.level > oldLevel) showLevelNotice(save);
        showAchievementNotices(unlockedAchievements);
    }

    function startSession() {
        var isNewSession = false;
        try {
            isNewSession = window.sessionStorage.getItem(SESSION_KEY) !== '1';
            window.sessionStorage.setItem(SESSION_KEY, '1');
        } catch (error) {
            isNewSession = true;
        }
        if (!isNewSession) {
            return;
        }
        var save = readSave();
        var oldLevel = save.level;
        save.visits += 1;
        save.lastVisitAt = now();
        addXp(save, 5);
        writeSave(save);
        if (save.level > oldLevel) showLevelNotice(save);
    }

    function showLevelNotice(save) {
        if (!LEVEL_NOTICE_ENABLED) return;
        var previous = document.querySelector('.dream-level-notice');
        if (previous) previous.remove();
        var notice = document.createElement('div');
        notice.className = 'dream-level-notice';
        notice.setAttribute('role', 'status');
        notice.innerHTML = '<span>LEVEL UP</span><strong>LV.' + save.level + ' ' + save.rank + '</strong>';
        document.body.appendChild(notice);
        window.setTimeout(function () { notice.classList.add('is-visible'); }, 20);
        window.setTimeout(function () {
            notice.classList.remove('is-visible');
            window.setTimeout(function () { notice.remove(); }, 250);
        }, 2800);
    }

    function unlockAchievements(save) {
        if (!ACHIEVEMENTS_ENABLED) return [];
        var unlocked = [];
        ACHIEVEMENTS.forEach(function (achievement) {
            if (save.achievements[achievement.id] || !achievement.test(save)) return;
            save.achievements[achievement.id] = { unlockedAt: now() };
            unlocked.push(achievement);
        });
        return unlocked;
    }

    function showAchievementNotices(achievements) {
        if (!ACHIEVEMENT_NOTICE_ENABLED) return;
        achievements.forEach(function (achievement, index) {
            window.setTimeout(function () {
                var notice = document.createElement('div');
                notice.className = 'dream-achievement-notice is-' + achievement.className;
                notice.setAttribute('role', 'status');
                notice.innerHTML = '<span>成就解锁 · ' + achievement.rarity + '</span><strong>' + achievement.name + '</strong>';
                document.body.appendChild(notice);
                window.setTimeout(function () { notice.classList.add('is-visible'); }, 20);
                window.setTimeout(function () {
                    notice.classList.remove('is-visible');
                    window.setTimeout(function () { notice.remove(); }, 250);
                }, 2800);
            }, index * 3200);
        });
    }

    function formatDate(value) {
        var date = new Date(value);
        return isNaN(date.getTime()) ? '--' : date.toLocaleString([], {
            year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit'
        });
    }

    function renderPanel(save) {
        var panel = document.getElementById('dream-save-panel');
        if (!panel) return;
        var values = {
            visits: save.visits,
            pageViews: save.pageViews,
            articles: Object.keys(save.articles).length,
            categories: Object.keys(save.categories).length
        };
        Object.keys(values).forEach(function (key) {
            var target = panel.querySelector('[data-dream-save-stat="' + key + '"]');
            if (target) target.textContent = String(values[key]);
        });
        var title = panel.querySelector('[data-dream-save-title]');
        var created = panel.querySelector('[data-dream-save-created]');
        var updated = panel.querySelector('[data-dream-save-updated]');
        var level = panel.querySelector('[data-dream-save-level]');
        var rank = panel.querySelector('[data-dream-save-rank]');
        var exp = panel.querySelector('[data-dream-save-exp]');
        var nextExp = panel.querySelector('[data-dream-save-next-exp]');
        var expBar = panel.querySelector('[data-dream-save-exp-bar]');
        var progress = panel.querySelector('[role="progressbar"]');
        var currentThreshold = levelThreshold(save.level);
        var nextThreshold = save.level >= 30 ? currentThreshold : levelThreshold(save.level + 1);
        var percent = save.level >= 30 ? 100 : Math.max(0, Math.min(100,
            (save.xp - currentThreshold) / (nextThreshold - currentThreshold) * 100
        ));
        if (title) title.textContent = save.title || save.rank || '游客';
        if (created) created.textContent = formatDate(save.createdAt);
        if (updated) updated.textContent = formatDate(save.lastVisitAt);
        if (level) level.textContent = String(save.level);
        if (rank) rank.textContent = save.rank;
        if (exp) exp.textContent = String(save.xp);
        if (nextExp) nextExp.textContent = save.level >= 30 ? 'MAX' : String(nextThreshold);
        if (expBar) expBar.style.width = percent + '%';
        if (progress) progress.setAttribute('aria-valuenow', String(Math.round(percent)));
        renderAchievements(panel, save);
    }

    function renderAchievements(panel, save) {
        var list = panel.querySelector('[data-dream-achievement-list]');
        if (!list) return;
        var unlockedCount = 0;
        list.innerHTML = '';
        ACHIEVEMENTS.forEach(function (achievement) {
            var unlocked = !!save.achievements[achievement.id];
            if (unlocked) unlockedCount += 1;
            var item = document.createElement('article');
            item.className = 'dream-achievement-item is-' + achievement.className + (unlocked ? ' is-unlocked' : ' is-locked');
            var displayName = !unlocked && achievement.className === 'hidden' ? '???' : achievement.name;
            var description = !unlocked && achievement.className === 'hidden' ? '隐藏条件尚未发现' : achievement.description;
            item.innerHTML = '<i class="' + (unlocked ? 'ri-trophy-line' : 'ri-lock-line') + '" aria-hidden="true"></i>' +
                '<div><strong>' + displayName + '</strong><span>' + description + '</span></div>' +
                '<em>' + achievement.rarity + '</em>';
            list.appendChild(item);
        });
        var count = panel.querySelector('[data-dream-achievement-count]');
        var total = panel.querySelector('[data-dream-achievement-total]');
        if (count) count.textContent = String(unlockedCount);
        if (total) total.textContent = String(ACHIEVEMENTS.length);
    }

    function initializePanel() {
        var panel = document.getElementById('dream-save-panel');
        var toggle = document.getElementById('dream-save-toggle');
        if (!panel || !toggle || panel.dataset.dreamSavePanelReady === '1') return;
        panel.dataset.dreamSavePanelReady = '1';
        var reset = panel.querySelector('[data-dream-save-reset]');
        var resetTimer = 0;
        var resetLabel = reset ? reset.textContent : '';

        function closePanel() {
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            document.documentElement.classList.remove('disable-scroll');
            toggle.focus();
        }
        function openPanel() {
            renderPanel(readSave());
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            document.documentElement.classList.add('disable-scroll');
            var close = panel.querySelector('.dream-save-header [data-dream-save-close]');
            if (close) close.focus();
        }

        toggle.addEventListener('click', function () {
            if (panel.hidden) openPanel(); else closePanel();
        });
        panel.querySelectorAll('[data-dream-save-close]').forEach(function (button) {
            button.addEventListener('click', closePanel);
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !panel.hidden) closePanel();
        });
        document.addEventListener('dream2:save-updated', function (event) {
            if (!panel.hidden) renderPanel(event.detail);
        });
        if (reset) {
            reset.addEventListener('click', function () {
                if (reset.dataset.confirm !== '1') {
                    reset.dataset.confirm = '1';
                    reset.textContent = '再次点击确认删除';
                    window.clearTimeout(resetTimer);
                    resetTimer = window.setTimeout(function () {
                        reset.dataset.confirm = '0';
                        reset.textContent = resetLabel;
                    }, 5000);
                    return;
                }
                window.clearTimeout(resetTimer);
                reset.dataset.confirm = '0';
                reset.textContent = resetLabel;
                renderPanel(window.Dream2VisitorSave.reset());
            });
        }
    }

    window.Dream2VisitorSave = {
        key: STORAGE_KEY,
        get: function () {
            return readSave();
        },
        reset: function () {
            var save = freshSave();
            writeSave(save);
            document.dispatchEvent(new CustomEvent('dream2:save-reset', { detail: save }));
            return save;
        }
    };

    document.documentElement.dataset.dreamSaveReady = '1';
    document.documentElement.dataset.dreamSaveVersion = String(SCHEMA_VERSION);
    startSession();
    recordPage();
    initializePanel();
    document.addEventListener('dream2:page-loaded', recordPage);
    document.addEventListener('dream2:discover-node', function (event) {
        var node = event.detail && String(event.detail.node || '');
        if (!node) return;
        var save = readSave();
        if (save.hiddenNodes[node]) return;
        save.hiddenNodes[node] = { discoveredAt: now() };
        addXp(save, 30);
        var unlockedAchievements = unlockAchievements(save);
        writeSave(save);
        document.dispatchEvent(new CustomEvent('dream2:save-updated', { detail: save }));
        showAchievementNotices(unlockedAchievements);
    });
})();
