import { applyFormErrors, errorFeedback, showFeedback, showOperationError } from '../../core/feedback.js';
import { initReportDownloads } from '../report-download.js';
import { initSummaryCounters } from '../../components/summary-counter.js';

function element(tag, text, className) {
    const node = document.createElement(tag);
    if (text !== undefined) node.textContent = text;
    if (className) node.className = className;
    return node;
}

const measureValue = value => value === null || value === undefined ? 'Unobserved' : String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

function initReportControls(root, form, readFilters, defaultYear) {
    const toggle = root.querySelector('[data-report-filter-toggle]');
    const panel = root.querySelector('#report-filter-panel');
    const count = root.querySelector('[data-report-filter-count]');
    const indicator = root.querySelector('[data-report-filter-status]');
    const exportMenu = root.querySelector('[data-report-export-menu]');

    function setOpen(open, returnFocus = false) {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        if (returnFocus) toggle.focus();
    }

    toggle.hidden = false;
    // Use the compact association report controls in the desktop admin workspace too.
    const associationReport = Boolean(root.closest?.('.association-workspace') || (root.closest?.('.admin-workspace') && window.matchMedia('(min-width: 768px)').matches));
    setOpen(!associationReport);
    toggle.addEventListener('click', () => setOpen(panel.hidden));
    if (associationReport) toggle.closest('.section-heading')?.addEventListener('click', event => {
        if (event.target.closest('button, a, input, select, textarea, summary, .area-info')) return;
        setOpen(panel.hidden);
    });
    panel.addEventListener('keydown', event => {
        if (event.key === 'Escape') { event.preventDefault(); setOpen(false, true); }
    });
    form.addEventListener('invalid', () => setOpen(true), true);
    exportMenu?.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            event.preventDefault();
            exportMenu.open = false;
            exportMenu.querySelector('summary').focus();
        }
    });

    return function sync() {
        const active = [...readFilters()].filter(([key, value]) => key !== 'report_type'
            && !(key === 'year' && Number(value) === defaultYear)).length;
        count.textContent = String(active);
        count.hidden = active === 0;
        toggle.setAttribute('aria-label', active ? `Build your report, ${active} active filters` : 'Build your report');
        if (indicator) indicator.textContent = active ? `${active} active filter(s)` : '';
    };
}

export function initReports() {
    const root = document.querySelector('[data-report-workspace]');
    if (!root || root.dataset.reportsInitialized === 'true') return;
    const form = root.querySelector('[data-report-form]');
    const preview = root.querySelector('[data-report-result]');
    const status = root.querySelector('[data-report-status]');
    // Share the presentation without changing admin filters or report data.
    const associationReport = Boolean(root.closest?.('.association-workspace') || (root.closest?.('.admin-workspace') && window.matchMedia('(min-width: 768px)').matches));
    const plain = value => {
        if (!associationReport || typeof value !== 'string') return value;
        // Admin reports may cover several associations, not only one.
        if (root.closest?.('.admin-workspace') && value === 'Archived records are retained within the current authorized association scope. Counts describe recorded data only.') {
            return 'Archived records are included for associations covered by this report. Counts reflect only information recorded in the system.';
        }
        const incomeNote = value.match(/^(\d+) quarterly entries excluded: quarterly income cannot be allocated to the selected month\.$/);
        if (incomeNote) return `${incomeNote[1]} quarterly income entries are excluded because they cannot be assigned to the selected month.`;
        return initial.plainLanguage?.[value] || value;
    };
    let guideFrame = null;
    function updateReportGuide() {
        guideFrame = null;
        const links = [...preview.querySelectorAll('.report-section-nav a[href^="#report-"]')];
        let active = null;
        for (const link of links) {
            const target = preview.querySelector(link.hash);
            if (target && target.getBoundingClientRect().top <= 150) active = link;
        }
        if (!active) active = links[0];
        links.forEach(link => {
            if (link === active) link.setAttribute('aria-current', 'location');
            else link.removeAttribute('aria-current');
        });
    }
    function scheduleReportGuide() {
        if (associationReport && guideFrame === null) guideFrame = window.requestAnimationFrame(updateReportGuide);
    }
    if (associationReport) {
        window.addEventListener('scroll', scheduleReportGuide, { passive: true });
        window.addEventListener('resize', scheduleReportGuide, { passive: true });
    }
    const exportForm = root.querySelector('[data-report-export]');
    initReportDownloads(exportForm);
    const initial = JSON.parse(root.querySelector('[data-report-initial]').textContent);
    root.addEventListener('click', event => {
        const link = event.target.closest?.('a[href^="#report-"]');
        const target = link && preview.querySelector(link.hash);
        if (!target) return;
        if (target.tagName === 'DETAILS') target.open = true;
        for (let parent = target.parentElement; parent && parent !== preview; parent = parent.parentElement) {
            if (parent.tagName === 'DETAILS') parent.open = true;
        }
        target.focus();
    });
    root.dataset.reportsInitialized = 'true';
    const field = name => form.elements.namedItem(name);
    let request;
    let optionsRequest;
    let revision = 0;
    let currentAnalytics;
    let analyticsWidth;
    if (typeof ResizeObserver === 'function') {
        new ResizeObserver(([entry]) => {
            const width = Math.round(entry.contentRect.width);
            if (analyticsWidth && width !== analyticsWidth && currentAnalytics && preview.contains(currentAnalytics.node)) {
                const open = [...currentAnalytics.node.querySelectorAll('details')].map(details => details.open);
                currentAnalytics.render(currentAnalytics.result, currentAnalytics.node);
                currentAnalytics.node.querySelectorAll('details').forEach((details, index) => { details.open = Boolean(open[index]); });
            }
            analyticsWidth = width;
        }).observe(preview);
    }
    const syncControls = initReportControls(root, form, filters, initial.defaultYear);

    function syncBusy() {
        form.querySelector('[data-report-submit]').disabled = Boolean(request || optionsRequest);
        const refresh = root.querySelector('[data-report-refresh]');
        if (refresh) refresh.disabled = Boolean(request || optionsRequest);
        preview.setAttribute('aria-busy', String(Boolean(request)));
    }

    function exportsEnabled(enabled) {
        if (exportForm) exportForm.dataset.exportReady = String(enabled);
        exportForm?.querySelectorAll('button').forEach(button => { button.disabled = !enabled || exportForm.dataset.downloadPending === 'true'; });
    }

    function showStatus(message, error = false) {
        delete status.dataset.feedbackFallback;
        status.textContent = message;
        status.setAttribute('role', error ? 'alert' : 'status');
        status.classList.toggle('notice-error', error);
    }

    function filters() {
        const type = field('report_type').value;
        const allowed = new Set(['report_type', ...initial.filterMap[type]]);
        return new URLSearchParams([...new FormData(form)].filter(([name, value]) => allowed.has(name) && value !== ''));
    }

    function relevance() {
        const supported = initial.filterMap[field('report_type').value];
        root.querySelectorAll('[data-report-filter]').forEach(container => {
            const select = container.querySelector('select');
            const visible = supported.includes(container.dataset.reportFilter);
            container.hidden = !visible;
            select.disabled = !visible;
            select.required = visible && select.name === 'year';
            if (!visible && select.name !== 'year') select.value = '';
            if (visible && select.name === 'year' && !select.value) {
                select.value = String(initial.defaultYear);
                if (!select.value) select.selectedIndex = 0;
            }
        });
    }

    function associationOptions() {
        const municipality = field('municipality_id')?.value;
        const select = field('association_id');
        if (!select) return;
        for (const option of select.options) {
            const available = !option.value || !municipality || option.dataset.municipality === municipality;
            option.disabled = !available;
            option.hidden = !available;
        }
    }

    async function json(url, signal) {
        let response;
        try {
            response = await fetch(url, { signal, credentials: 'same-origin', redirect: 'error', headers: { Accept: 'application/json' } });
        } catch (error) {
            if (error.name === 'AbortError') throw error;
            throw new Error('Unable to load the report. Check your connection or sign in again, then retry.');
        }
        const contentType = response.headers.get('content-type') || '';
        const failure = new Error(errorFeedback(response.ok ? 503 : response.status, { context: 'Report' }).message);
        failure.status = response.ok ? 503 : response.status;
        if (!contentType.includes('application/json')) throw failure;
        let body;
        try { body = await response.json(); }
        catch { throw failure; }
        if (!response.ok) {
            failure.fields = response.status === 422 ? body?.errors || {} : {};
            throw failure;
        }
        if (!body?.data || typeof body.data !== 'object') throw failure;
        return body.data;
    }

    function fill(name, options, placeholder) {
        const select = field(name);
        if (!select) return;
        const previous = select.value;
        select.replaceChildren();
        if (placeholder) select.append(new Option(placeholder, ''));
        for (const option of options) select.append(new Option(String(option.name), String(option.id)));
        if ([...select.options].some(option => option.value === previous)) select.value = previous;
        else if (name === 'year') {
            select.value = String(initial.defaultYear);
            if (!select.value) select.selectedIndex = 0;
        }
    }

    async function refreshOptions() {
        optionsRequest?.abort();
        optionsRequest = new AbortController();
        const current = optionsRequest;
        const query = new URLSearchParams({ report_type: field('report_type').value });
        for (const key of ['municipality_id', 'association_id', 'project_id']) {
            if (field(key)?.value && !field(key).disabled) query.set(key, field(key).value);
        }
        syncBusy();
        try {
            const options = await json(`${root.dataset.optionsUrl}?${query}`, current.signal);
            if (current !== optionsRequest || current.signal.aborted) return;
            fill('project_id', options.projects, 'All recorded projects');
            fill('product_id', options.products, 'All recorded products');
            fill('component_id', options.components, 'All recorded components');
            fill('program_year', options.program_years.map(year => ({ id: year, name: year })), 'All recorded program years');
            fill('year', options.years.map(year => ({ id: year, name: year })));
            relevance();
            syncControls();
            showStatus('Apply filters to update the report.');
        } catch (error) {
            if (current === optionsRequest && error.name !== 'AbortError') showStatus(error.message, true);
        } finally {
            if (current === optionsRequest) { optionsRequest = undefined; syncBusy(); }
        }
    }

    function table(section, container) {
        if (!section.rows.length) { container.append(element('p', 'No recorded rows for this section.', 'report-empty')); return; }
        const scroll = element('div', undefined, 'report-table-scroll');
        scroll.tabIndex = 0;
        scroll.setAttribute('role', 'region');
        scroll.setAttribute('aria-label', section.title);
        const table = element('table');
        table.append(element('caption', plain(section.title)));
        const head = element('thead');
        const tr = element('tr');
        for (const column of section.columns) {
            const th = element('th', plain(column.label));
            th.scope = 'col';
            if (['integer', 'decimal'].includes(column.type)) th.className = 'report-numeric';
            tr.append(th);
        }
        head.append(tr);
        const body = element('tbody');
        table.append(head, body);
        scroll.append(table);
        const controls = element('div', undefined, 'report-pagination');
        controls.setAttribute('role', 'group');
        controls.setAttribute('aria-label', `${section.title} table pages`);
        const previous = element('button', 'Previous', 'button');
        const next = element('button', 'Next', 'button');
        previous.type = next.type = 'button';
        previous.setAttribute('aria-label', `Previous page: ${section.title}`);
        next.setAttribute('aria-label', `Next page: ${section.title}`);
        const context = element('span');
        context.setAttribute('aria-live', 'polite');
        controls.append(previous, context, next);
        let page = 0;
        function renderPage() {
            body.replaceChildren();
            for (const row of section.rows.slice(page * 50, (page + 1) * 50)) {
                const rowNode = element('tr');
                for (const column of section.columns) rowNode.append(element('td', row[column.key] ?? plain('Unobserved / N/A'),
                    ['integer', 'decimal'].includes(column.type) ? 'report-numeric' : undefined));
                body.append(rowNode);
            }
            previous.disabled = page === 0;
            next.disabled = (page + 1) * 50 >= section.rows.length;
            context.textContent = `Rows ${page * 50 + 1}–${Math.min((page + 1) * 50, section.rows.length)} of ${section.rows.length}`;
        }
        previous.addEventListener('click', () => { page--; renderPage(); });
        next.addEventListener('click', () => { page++; renderPage(); });
        renderPage();
        container.append(scroll, controls);
    }

    function render(result) {
        const navigation = element('nav', undefined, 'report-section-nav');
        navigation.setAttribute('aria-label', 'Report sections');
        for (const [label, target] of [['Scope', 'report-scope'], ['Overview', 'report-summary'], ['Charts', 'report-analytics'], ['Interpretation', 'report-quality'], ['Detailed records →', 'report-details']]) {
            const link = element('a', plain(label)); link.href = `#${target}`; navigation.append(link);
        }
        const content = element('div', undefined, 'report-result-content');
        content.append(element('h2', result.title));
        preview.replaceChildren(navigation, content);
        const scope = element('section', undefined, 'report-context-card');
        scope.id = 'report-scope';
        const applied = result.meta.filters;
        const associationScope = result.meta.scope.length === 1 ? result.meta.scope[0] : `${result.meta.scope.length} authorized associations`;
        const period = `${applied.year ? `Reporting year ${applied.year}` : (associationReport ? 'Current information' : 'Current snapshot')}${applied.quarter ? ` · Q${applied.quarter}` : ''}${applied.month ? ` · Month ${applied.month}` : ''}${applied.program_year ? ` · Program year ${applied.program_year}` : ''}`;
        scope.append(element('h3', plain('Reporting scope and period')), element('p', `${associationScope} · ${period}`, 'report-context-brief'));
        const scopeDetails = element('details', undefined, 'report-scope-details');
        scopeDetails.append(element('summary', plain('Scope and applied filters')), element('p', plain(result.meta.scope_basis)),
            element('p', `Associations: ${result.meta.scope.join('; ') || 'None in this scope'}`),
            element('p', `Generated ${result.meta.generated_at} · ${result.meta.timezone}`, 'field-help'));
        const snapshot = ['associations', 'members', 'projects'].includes(result.meta.filters.report_type);
        scopeDetails.append(element('p', associationReport ? (snapshot ? 'These counts show current information. Program year selects projects by their recorded program year; reporting dates do not show past counts.' : 'Reporting year and quarter select training start dates, activity dates, and monitoring periods. Association and member counts and project statuses show their current state.') : (snapshot ? 'Current snapshot. Program year selects a cohort; reporting dates do not reconstruct historical counts.' : 'Reporting year/quarter selects training start dates, activity dates and quarterly monitoring periods. Association/member counts and project statuses remain current snapshots.'), 'field-help'));
        const context = Object.entries(result.meta.filters).map(([key, value]) => {
            const select = field(key);
            const label = select ? [...select.options].find(option => option.value === String(value))?.textContent : value;
            return `${key.replaceAll('_', ' ')}: ${label || value}`;
        }).join(' · ');
        scopeDetails.append(element('p', context, 'field-help')); scope.append(scopeDetails);
        content.append(scope);
        if (result.empty) content.append(element('p', 'No recorded results match the selected report and filters.', 'notice'));
        const summary = element('section', undefined, 'report-summary');
        summary.id = 'report-summary'; summary.tabIndex = -1;
        summary.setAttribute('aria-labelledby', 'report-summary-title');
        const summaryHeading = element('div', undefined, 'report-section-heading'), summaryCopy = element('div');
        const summaryTitle = element('h3', 'At a glance'); summaryTitle.id = 'report-summary-title';
        summaryCopy.append(summaryTitle, element('p', plain('Recorded indicators within the applied scope. Unobserved values are not zero.')));
        summaryHeading.append(summaryCopy); summary.append(summaryHeading);
        const primary = element('dl', undefined, 'report-measures report-primary-measures');
        primary.setAttribute('data-report-kpis', '');
        for (const [key, label] of initial.primaryMeasureMap?.[applied.report_type] || []) {
            const measure = result.sections.find(section => section.key === key)?.measures.find(measure => measure.label === label);
            if (!measure) continue;
            const card = element('div'), definition = element('dd'), value = element('span', plain(measureValue(measure.value)));
            if (associationReport && /^\d+$/.test(String(measure.value))) value.setAttribute('data-summary-counter', '');
            const labelNode = element('dt', plain(measure.label));
            if (associationReport) {
                const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                icon.setAttribute('viewBox', '0 0 24 24'); icon.setAttribute('fill', 'none'); icon.setAttribute('stroke', 'currentColor'); icon.setAttribute('stroke-width', '1.5'); icon.setAttribute('aria-hidden', 'true'); icon.classList.add('icon');
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('stroke-linecap', 'round'); path.setAttribute('stroke-linejoin', 'round');
                path.setAttribute('d', key === 'members' ? 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8-7a4 4 0 0 1 0 8m4 9v-2a4 4 0 0 0-3-3.87' : key === 'projects' ? 'M4 7h16v13H4zM9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2' : key === 'trainings' ? 'M3 9l9-5 9 5-9 5-9-5Zm3 2v5c3 3 9 3 12 0v-5' : 'M4 20h16M6 17v-6m6 6V5m6 12V9');
                icon.append(path); labelNode.append(icon);
            }
            definition.append(value); card.append(labelNode, definition); primary.append(card);
        }
        summary.append(primary);
        const allMeasures = element('details', undefined, 'report-all-measures');
        allMeasures.append(element('summary', 'All report measures'), element('p', plain('Recorded values within the applied scope. Unobserved values are not zero.'), 'field-help'));
        const measureGroups = element('div', undefined, 'report-measure-groups');
        for (const section of result.sections.filter(section => section.measures.length)) {
            const group = element('section', undefined, 'report-measure-group');
            const groupTitle = element('h4', associationReport ? undefined : plain(section.title));
            if (associationReport) {
                const paths = {
                    associations: 'M4 21V6l8-3 8 3v15M9 21v-6h6v6M8 9h.01M16 9h.01',
                    members: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8-7a4 4 0 0 1 0 8m4 9v-2a4 4 0 0 0-3-3.87',
                    projects: 'M4 7h16v13H4zM9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2',
                    trainings: 'M3 9l9-5 9 5-9 5-9-5Zm3 2v5c3 3 9 3 12 0v-5',
                    income: 'M3 6h18v12H3zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z',
                    activities: 'M4 5h16v15H4zM8 2v6m8-6v6M4 10h16',
                    monitoring: 'M8 4h8M9 2h6v4H9zM6 4H5v17h14V4h-1M8 14l2 2 5-5',
                };
                const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                for (const [name, value] of Object.entries({ viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5', 'aria-hidden': 'true' })) icon.setAttribute(name, value);
                icon.classList.add('icon');
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('stroke-linecap', 'round'); path.setAttribute('stroke-linejoin', 'round'); path.setAttribute('d', paths[section.key] || 'M4 20h16M6 17v-6m6 6V5m6 12V9');
                icon.append(path); groupTitle.append(icon);
            }
            if (associationReport) groupTitle.append(element('span', plain(section.title)));
            group.append(groupTitle);
            const measures = element('dl', undefined, 'report-secondary-measures');
            for (const measure of section.measures) {
                const card = element('div');
                const value = element('span', plain(measureValue(measure.value)));
                const definition = element('dd'); definition.append(value);
                card.append(element('dt', plain(measure.label)), definition); measures.append(card);
            }
            group.append(measures); measureGroups.append(group);
        }
        allMeasures.append(measureGroups);
        summary.append(allMeasures);
        content.append(summary);
        if (associationReport) initSummaryCounters(primary);
        const analytics = element('section', undefined, 'report-analytics');
        analytics.id = 'report-analytics'; analytics.tabIndex = -1;
        analytics.setAttribute('aria-labelledby', 'report-analytics-title');
        analytics.setAttribute('aria-busy', 'true');
        const analyticsTitle = element('h3', 'Visual analytics'); analyticsTitle.id = 'report-analytics-title';
        analytics.append(analyticsTitle, element('p', 'Loading charts. Summary indicators and detailed data remain available.', 'field-help'));
        content.append(analytics);
        // Load presentation code only on report pages; never request a dataset per chart.
        import('../../components/report-analytics.js').then(({ renderReportAnalytics }) => {
            if (!preview.contains(analytics)) return;
            currentAnalytics = { result, node: analytics, render: renderReportAnalytics };
            renderReportAnalytics(result, analytics);
            scheduleReportGuide();
        }).catch(() => {
            if (!preview.contains(analytics)) return;
            analytics.removeAttribute('aria-busy');
            const title = element('h3', 'Visual analytics'); title.id = 'report-analytics-title';
            analytics.replaceChildren(title, element('p', 'Charts are unavailable. Summary indicators and detailed data tables remain available.', 'report-empty'));
        });
        const quality = element('details', undefined, 'report-quality');
        quality.id = 'report-quality'; quality.tabIndex = -1;
        quality.append(element('summary', plain('Data interpretation and quality')));
        const notes = element('ul');
        for (const note of result.notes) notes.append(element('li', plain(note)));
        quality.append(notes);
        for (const section of result.sections.filter(section => section.notes.length)) {
            quality.append(element('h4', plain(section.title)));
            const sectionNotes = element('ul');
            for (const note of section.notes) sectionNotes.append(element('li', plain(note)));
            quality.append(sectionNotes);
        }
        content.append(quality);
        const detailed = element('details', undefined, 'report-detailed-data');
        detailed.id = 'report-details'; detailed.tabIndex = -1;
        const detailedHeading = element('summary', 'Detailed records ');
        detailedHeading.append(element('span', `${result.sections.length} sections`, 'report-detail-count'));
        detailed.append(detailedHeading);
        result.sections.forEach((section, index) => {
            const container = element('section', undefined, 'report-section report-data-section');
            container.id = `report-table-${section.key || index}`;
            container.tabIndex = -1;
            container.append(element('h4', plain(section.title)));
            table(section, container);
            detailed.append(container);
        });
        content.append(detailed);
        scheduleReportGuide();
        const hidden = exportForm?.querySelector('[data-export-filters]');
        if (hidden) {
            hidden.replaceChildren();
            for (const [key, value] of Object.entries(result.meta.filters)) {
                const input = element('input');
                input.type = 'hidden'; input.name = key; input.value = value;
                hidden.append(input);
            }
        }
        exportsEnabled(true);
    }

    form.addEventListener('change', event => {
        ++revision;
        exportsEnabled(false);
        preview.replaceChildren();
        showStatus(request ? 'Wait for the current request, then apply filters.' : 'Apply filters to update the report.');
        relevance();
        const name = event.target.name;
        if (name === 'municipality_id' && field('association_id')) {
            const selected = field('association_id').selectedOptions[0];
            if (selected?.dataset.municipality !== field('municipality_id').value) field('association_id').value = '';
        }
        associationOptions();
        if (['municipality_id', 'association_id'].includes(name)) {
            field('project_id').value = '';
            field('product_id').value = '';
        }
        syncControls();
        if (['municipality_id', 'association_id', 'project_id', 'report_type'].includes(name)) refreshOptions();
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (request || optionsRequest) return;
        relevance();
        if (!form.reportValidity()) return;
        const query = filters();
        request = new AbortController();
        const current = request;
        const started = revision;
        exportsEnabled(false);
        preview.replaceChildren();
        syncBusy();
        showStatus('Loading authorized report…');
        try {
            const result = await json(`${root.dataset.dataUrl}?${query}`, current.signal);
            if (current !== request || started !== revision || current.signal.aborted) return;
            render(result);
            showStatus(result.empty ? 'Report loaded. No matching observations.' : (root.closest?.('.association-workspace') ? '' : 'Report loaded from authorized records.'));
            void showFeedback({ type: 'success', title: 'Report Loaded', message: result.empty
                ? 'The report was loaded. No recorded observations match these filters.' : 'The report was loaded from your authorized records.',
                returnFocus: form.querySelector('[data-report-submit]') });
            status.dataset.feedbackFallback = '';
            history.replaceState(null, '', `${form.action}?${query}`);
        } catch (error) {
            if (current === request && started === revision && error.name !== 'AbortError') {
                showStatus(error.message, true);
                applyFormErrors(form, error.fields);
                void showOperationError(error, { context: 'Report', returnFocus: form.querySelector('[data-report-submit]') });
                status.dataset.feedbackFallback = '';
            }
        } finally {
            if (current === request) {
                request = undefined;
                syncBusy();
                if (started !== revision && !optionsRequest && status.getAttribute('role') !== 'alert') showStatus('Apply filters to update the report.');
            }
        }
    });
    relevance();
    associationOptions();
    syncControls();
    if (initial.result) {
        const selected = Object.fromEntries(filters());
        const applied = initial.result.meta.filters;
        const keys = new Set([...Object.keys(selected), ...Object.keys(applied)]);
        if ([...keys].every(key => String(selected[key] ?? '') === String(applied[key] ?? ''))) render(initial.result);
        else {
            preview.replaceChildren();
            exportsEnabled(false);
            showStatus('Apply filters to update the report.');
        }
    }
}
