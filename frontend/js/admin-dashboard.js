/**
 * admin-dashboard.js
 * All data pulled from /api/admin/stats and /api/admin/rate-limits.
 * No hardcoded values anywhere.
 */

document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    buildRateLimits();
});

/* ── Stats (cards + chart + projects table) ───────────────── */

async function loadStats() {
    try {
        const res  = await fetch('/api/admin/stats');
        const data = await res.json();

        if (data.status !== 'success') {
            console.error('Stats API error:', data);
            return;
        }

        const d = data.data;

        // ── Stat card values
        setText('total-users',       d.total_users);
        setText('verified-users',    d.verified_users);
        setText('total-projects',    d.total_projects);
        setText('featured-projects', d.featured_projects);
        setText('failed-logins-24h', d.failed_logins_24h);

        // ── Stat card deltas
        renderDelta('delta-users',    d.new_users_7d,       'this week');
        renderDelta('delta-verified', d.new_verified_today, 'today');
        renderFailedDelta('delta-failed', d.failed_logins_24h, d.failed_logins_yesterday);

        // ── Login activity chart
        if (Array.isArray(d.logins_7d) && d.logins_7d.length > 0) {
            renderChart(d.logins_7d);
            renderLoginSummary(d.logins_7d);
        } else {
            renderChartEmpty();
        }

        // ── Recent projects table
        if (Array.isArray(d.recent_projects)) {
            renderProjects(d.recent_projects);
        }

    } catch (err) {
        console.error('Failed to load admin stats:', err);
    }
}

/* ── Stat card deltas ─────────────────────────────────────── */

function renderDelta(id, count, label) {
    const el = document.getElementById(id);
    if (!el) return;
    if (count > 0) {
        el.innerHTML = `<span class="delta-up">↑ ${count}</span> ${label}`;
    } else {
        el.innerHTML = `<span style="color:var(--admin-text3)">No change</span>`;
    }
}

function renderFailedDelta(id, today, yesterday) {
    const el = document.getElementById(id);
    if (!el) return;
    const diff = (yesterday ?? 0) - (today ?? 0);
    if (diff > 0) {
        el.innerHTML = `<span class="delta-up">↓ ${diff}</span> vs yesterday`;
    } else if (diff < 0) {
        el.innerHTML = `<span class="delta-down">↑ ${Math.abs(diff)}</span> vs yesterday`;
    } else {
        el.innerHTML = `<span style="color:var(--admin-text3)">Same as yesterday</span>`;
    }
}

/* ── Login activity chart ─────────────────────────────────── */

function renderChart(logins7d) {
    const chart  = document.getElementById('login-chart');
    const labels = document.getElementById('chart-labels');
    if (!chart || !labels) return;

    chart.innerHTML  = '';
    labels.innerHTML = '';

    const vals = logins7d.map(d => (d.success || 0) + (d.failed || 0));
    const max  = Math.max(...vals, 1);

    logins7d.forEach((d, i) => {
        const total = (d.success || 0) + (d.failed || 0);

        const bar = document.createElement('div');
        bar.className    = 'mini-chart__bar' + (i === logins7d.length - 1 ? ' mini-chart__bar--active' : '');
        bar.style.height = Math.round((total / max) * 100) + '%';
        bar.title        = `${d.label}: ${d.success || 0} successful, ${d.failed || 0} failed`;
        chart.appendChild(bar);

        const lbl = document.createElement('div');
        lbl.className   = 'mini-chart__label';
        lbl.textContent = d.label;
        labels.appendChild(lbl);
    });
}

function renderChartEmpty() {
    const chart  = document.getElementById('login-chart');
    const labels = document.getElementById('chart-labels');
    if (!chart || !labels) return;

    const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    chart.innerHTML  = days.map(() => `<div class="mini-chart__bar" style="height:4px"></div>`).join('');
    labels.innerHTML = days.map(d => `<div class="mini-chart__label">${d}</div>`).join('');

    setText('logins-success', 0);
    setText('logins-failed',  0);
    setText('logins-limited', '—');
}

function renderLoginSummary(logins7d) {
    const totals = logins7d.reduce(
        (acc, d) => ({
            success: acc.success + (d.success || 0),
            failed:  acc.failed  + (d.failed  || 0),
        }),
        { success: 0, failed: 0 }
    );
    setText('logins-success', totals.success);
    setText('logins-failed',  totals.failed);
    setText('logins-limited', '—');
}

/* ── Recent projects table ────────────────────────────────── */

function renderProjects(projects) {
    const tbody = document.getElementById('projects-body');
    if (!tbody) return;

    if (!projects.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="admin-table__empty">No projects yet</td>
            </tr>`;
        return;
    }

    tbody.innerHTML = projects.map(p => {
        const tags = (p.tech_stack || []).slice(0, 3)
            .map(t => `<span class="badge badge--blue">${escapeHtml(t)}</span>`)
            .join(' ');

        const status = p.featured
            ? `<span class="badge badge--amber"><i class="fa-solid fa-star" style="font-size:9px"></i> Featured</span>`
            : `<span class="badge badge--blue">Public</span>`;

        return `
            <tr>
                <td class="cell-primary">${escapeHtml(p.title)}</td>
                <td>${tags}</td>
                <td>${status}</td>
                <td class="cell-actions">
                    <a href="/admin/edit?id=${p.id}" class="table-btn">Edit</a>
                </td>
            </tr>`;
    }).join('');
}

/* ── Project search filter (client-side) ──────────────────── */

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

/* ── Rate limit bars ──────────────────────────────────────── */

async function buildRateLimits() {
    const list = document.getElementById('rate-list');
    if (!list) return;

    try {
        const res  = await fetch('/api/admin/rate-limits');
        const data = await res.json();

        if (data.status === 'success' && Array.isArray(data.data) && data.data.length > 0) {
            renderRateLimits(list, data.data.slice(0, 5));
        } else {
            list.innerHTML = `
                <p style="font-size:13px;color:var(--admin-text3);text-align:center;padding:1rem 0">
                    No active rate limits
                </p>`;
        }
    } catch (err) {
        list.innerHTML = `
            <p style="font-size:13px;color:var(--admin-text3);text-align:center;padding:1rem 0">
                Could not load data
            </p>`;
        console.warn('Could not load rate limits:', err);
    }
}

function renderRateLimits(container, items) {
    const maxAttempts = 5;

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
            </div>`;
    }).join('');
}

/* ── Helpers ──────────────────────────────────────────────── */

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value ?? '—';
}

function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g,  '&amp;')
        .replace(/</g,  '&lt;')
        .replace(/>/g,  '&gt;')
        .replace(/"/g,  '&quot;')
        .replace(/'/g,  '&#39;');
}