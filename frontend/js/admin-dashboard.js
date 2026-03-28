/**
 * admin-dashboard.js
 * Handles: stat cards, login activity chart, activity feed, rate limit bars
 * All data comes from /api/admin/stats and /api/admin/activity
 */

document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    buildChart();
    buildActivityFeed();
    buildRateLimits();
});

/* ── Stats ────────────────────────────────────────────────── */

async function loadStats() {
    try {
        const res  = await fetch('/api/admin/stats');
        const data = await res.json();

        if (data.status !== 'success') {
            console.error('Stats API error:', data);
            return;
        }

        const d = data.data;
        setText('total-users',      d.total_users);
        setText('verified-users',   d.verified_users);
        setText('total-projects',   d.total_projects);
        setText('featured-projects',d.featured_projects);
        setText('failed-logins-24h',d.failed_logins_24h);

        // Activity summary (reuse from stats if available)
        if (d.logins_7d) {
            const totals = d.logins_7d.reduce(
                (acc, day) => ({
                    success:  acc.success  + (day.success  || 0),
                    failed:   acc.failed   + (day.failed   || 0),
                    limited:  acc.limited  + (day.limited  || 0),
                }),
                { success: 0, failed: 0, limited: 0 }
            );
            setText('logins-success', totals.success);
            setText('logins-failed',  totals.failed);
            setText('logins-limited', totals.limited);
            buildChartFromData(d.logins_7d);
        }

    } catch (err) {
        console.error('Failed to load admin stats:', err);
    }
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value ?? '—';
}

/* ── Login activity bar chart ─────────────────────────────── */

function buildChart() {
    // Placeholder data — replaced by buildChartFromData() once the API responds
    const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const vals  = [0, 0, 0, 0, 0, 0, 0];
    renderChart(days, vals);
}

function buildChartFromData(loginDays) {
    // loginDays: array of { label: 'Mon', success: 5, failed: 1, limited: 0 }
    const days = loginDays.map(d => d.label);
    const vals  = loginDays.map(d => (d.success || 0) + (d.failed || 0));
    renderChart(days, vals);

    const totals = loginDays.reduce(
        (acc, d) => ({
            success: acc.success + (d.success || 0),
            failed:  acc.failed  + (d.failed  || 0),
            limited: acc.limited + (d.limited || 0),
        }),
        { success: 0, failed: 0, limited: 0 }
    );
    setText('logins-success', totals.success);
    setText('logins-failed',  totals.failed);
    setText('logins-limited', totals.limited);
}

function renderChart(days, vals) {
    const chart  = document.getElementById('login-chart');
    const labels = document.getElementById('chart-labels');
    if (!chart || !labels) return;

    chart.innerHTML  = '';
    labels.innerHTML = '';

    const max = Math.max(...vals, 1);
    vals.forEach((v, i) => {
        const bar = document.createElement('div');
        bar.className = 'mini-chart__bar' + (i === vals.length - 1 ? ' mini-chart__bar--active' : '');
        bar.style.height = Math.round((v / max) * 100) + '%';
        bar.title = days[i] + ': ' + v + ' logins';
        chart.appendChild(bar);

        const lbl = document.createElement('div');
        lbl.className = 'mini-chart__label';
        lbl.textContent = days[i];
        labels.appendChild(lbl);
    });
}

/* ── Activity feed ────────────────────────────────────────── */

async function buildActivityFeed() {
    const feed = document.getElementById('activity-feed');
    if (!feed) return;

    // Default placeholder items while API loads
    const placeholders = [
        { icon: 'fa-right-to-bracket', colour: 'green',  title: 'Successful admin login',      sub: 'via GitHub OAuth · just now' },
        { icon: 'fa-pen',              colour: 'purple', title: 'Project updated',              sub: 'Loading…' },
        { icon: 'fa-user-check',       colour: 'blue',   title: 'New user verified email',      sub: 'Loading…' },
        { icon: 'fa-ban',              colour: 'red',    title: 'Login rate limited',            sub: 'Loading…' },
        { icon: 'fa-envelope',         colour: 'amber',  title: 'Verification email resent',    sub: 'Loading…' },
    ];

    renderFeed(feed, placeholders);

    try {
        const res  = await fetch('/api/admin/activity');
        const data = await res.json();
        if (data.status === 'success' && Array.isArray(data.data)) {
            renderFeed(feed, data.data.map(mapActivityItem));
        }
    } catch (err) {
        // Keep placeholders visible — activity feed is non-critical
        console.warn('Could not load activity feed:', err);
    }
}

function mapActivityItem(item) {
    const map = {
        login_success:   { icon: 'fa-right-to-bracket', colour: 'green'  },
        login_failed:    { icon: 'fa-xmark',            colour: 'red'    },
        project_updated: { icon: 'fa-pen',              colour: 'purple' },
        project_created: { icon: 'fa-plus',             colour: 'purple' },
        user_verified:   { icon: 'fa-user-check',       colour: 'blue'   },
        rate_limited:    { icon: 'fa-ban',              colour: 'red'    },
        email_resent:    { icon: 'fa-envelope',         colour: 'amber'  },
    };
    const meta = map[item.type] || { icon: 'fa-circle-dot', colour: 'blue' };
    return {
        icon:   meta.icon,
        colour: meta.colour,
        title:  item.title || item.type,
        sub:    item.description + ' · ' + formatRelativeTime(item.created_at),
    };
}

function renderFeed(container, items) {
    container.innerHTML = items.map(item => `
        <div class="activity-item">
            <div class="activity-item__dot activity-item__dot--${escapeHtml(item.colour)}">
                <i class="fa-solid ${escapeHtml(item.icon)}" style="font-size:10px"></i>
            </div>
            <div>
                <div class="activity-item__main">${escapeHtml(item.title)}</div>
                <div class="activity-item__sub">${escapeHtml(item.sub)}</div>
            </div>
        </div>
    `).join('');
}

/* ── Rate limit bars ──────────────────────────────────────── */

async function buildRateLimits() {
    const list = document.getElementById('rate-list');
    if (!list) return;

    try {
        const res  = await fetch('/api/admin/rate-limits');
        const data = await res.json();

        if (data.status === 'success' && Array.isArray(data.data) && data.data.length > 0) {
            renderRateLimits(list, data.data.slice(0, 5)); // Show top 5
        } else {
            list.innerHTML = '<p style="font-size:13px;color:var(--admin-text3);text-align:center;padding:1rem 0">No active rate limits</p>';
        }
    } catch (err) {
        list.innerHTML = '<p style="font-size:13px;color:var(--admin-text3);text-align:center;padding:1rem 0">Could not load data</p>';
        console.warn('Could not load rate limits:', err);
    }
}

function renderRateLimits(container, items) {
    const maxAttempts = 5; // Matches PHP RateLimiterService MAX_ATTEMPTS

    container.innerHTML = items.map(item => {
        const pct   = Math.min(Math.round((item.attempts / maxAttempts) * 100), 100);
        const color = pct >= 100
            ? 'var(--admin-red)'
            : pct >= 60
                ? 'var(--admin-amber)'
                : 'var(--admin-blue)';

        return `
            <div class="rate-row">
                <div class="rate-row__info">
                    <div class="rate-row__id" title="${escapeHtml(item.identifier)}">${escapeHtml(item.identifier)}</div>
                    <div class="rate-row__action">${escapeHtml(item.action)}</div>
                </div>
                <div class="rate-row__bar-wrap">
                    <div class="rate-row__bar-bg">
                        <div class="rate-row__bar-fill" style="width:${pct}%;background:${color}"></div>
                    </div>
                    <div class="rate-row__count">${item.attempts}/${maxAttempts}</div>
                </div>
            </div>
        `;
    }).join('');
}

/* ── Project search filter ────────────────────────────────── */

function filterProjects(query) {
    const rows  = document.querySelectorAll('#projects-body tr');
    const empty = document.getElementById('projects-empty');
    let   shown = 0;
    const q     = query.toLowerCase();

    rows.forEach(row => {
        const match = row.textContent.toLowerCase().includes(q);
        row.style.display = match ? '' : 'none';
        if (match) shown++;
    });

    if (empty) {
        empty.style.display = (shown === 0 && query.length > 0) ? 'block' : 'none';
    }
}

/* ── Helpers ──────────────────────────────────────────────── */

function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g,  '&amp;')
        .replace(/</g,  '&lt;')
        .replace(/>/g,  '&gt;')
        .replace(/"/g,  '&quot;')
        .replace(/'/g,  '&#39;');
}

function formatRelativeTime(dateStr) {
    if (!dateStr) return '';
    const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
    if (diff < 60)   return diff + 's ago';
    if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hr ago';
    return Math.floor(diff / 86400) + 'd ago';
}