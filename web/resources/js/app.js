import { initDisclosures } from './core/helpers.js';
import { initDialogs } from './core/dialogs.js';
import { initAdminNavigation, initWorkspaceHeaderScroll } from './layouts/admin-navigation.js';
import { initWorkspaceSidebar } from './layouts/workspace-sidebar.js';
import { initPublicNavigation } from './layouts/public-navigation.js';
import { initForms } from './pages/admin/forms.js';
import { initUserMenus } from './pages/admin/users.js';
import { initAssociationUi } from './pages/admin/associations.js';
import { initReports } from './pages/admin/reports.js';
import { initStaffLogin } from './pages/staff-login.js';
import { initUserInvitations } from './pages/user-invitations.js';
import { initProjectClosure } from './pages/project-closure.js';
import { initProgramLookups } from './pages/program-lookups.js';
import { initProgramActivities } from './pages/program-activities.js';
import { initFeedback, showFeedback } from './core/feedback.js';
import { mountSummaryCounters } from './components/summary-counter.js';
import { initContextualReports } from './components/contextual-report.js';
import { initDashboardGisGallery } from './pages/dashboard-gis-gallery.js';
import { initMemberEvidencePhotos } from './pages/member-evidence-photos.js';
import { countApplicationWords } from './pages/public-application.js';

initFeedback();
initDisclosures();
initDialogs();
// Keep new input numeric; the server checks the complete Philippine format.
document.querySelectorAll('[data-ph-mobile]').forEach(input => {
    input.addEventListener('input', () => { input.value = input.value.replace(/[^0-9]/g, '').slice(0, 11); });
});
// Give applicants immediate feedback; the server also enforces the word limit.
const publicReason = document.querySelector('#public-reason');
if (publicReason) {
    const counter = document.querySelector('[data-word-count]');
    const updateReasonCount = () => {
        const count = countApplicationWords(publicReason.value);
        counter.textContent = `${count} / 500 words · ${publicReason.value.length} / 5,000 characters`;
        publicReason.setCustomValidity(count > 500 ? 'Please use no more than 500 words.' : '');
    };
    publicReason.addEventListener('input', updateReasonCount);
    updateReasonCount();
}
// Show the follow-up message after the submission confirmation.
const applicationSuccess = document.querySelector('[data-application-success]');
if (applicationSuccess && typeof applicationSuccess.showModal === 'function') {
    applicationSuccess.showModal();
    applicationSuccess.querySelector('[data-application-next]').addEventListener('click', () => {
        applicationSuccess.close();
        document.querySelector('#application-next').showModal();
    });
}
const sidebar = initWorkspaceSidebar();
initAdminNavigation(sidebar, initWorkspaceHeaderScroll());
initPublicNavigation();
initForms();
initUserMenus();
initAssociationUi();
initReports();
initContextualReports();
initDashboardGisGallery();
initMemberEvidencePhotos();
mountSummaryCounters();
const dashboardCharts = document.querySelectorAll('[data-dashboard-chart]');
if (dashboardCharts.length && 'IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-chart-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });
    dashboardCharts.forEach((chart) => observer.observe(chart));
}
initStaffLogin();
initUserInvitations();
initProjectClosure();
initProgramLookups();
initProgramActivities();

const areaToast = document.querySelector('[data-area-toast]');
if (areaToast) {
    areaToast.querySelector('[data-area-toast-close]')?.addEventListener('click', () => areaToast.remove());
    let toastTimer;
    const scheduleDismissal = () => { toastTimer = window.setTimeout(() => areaToast.remove(), 8000); };
    const pauseDismissal = () => window.clearTimeout(toastTimer);
    areaToast.addEventListener('mouseenter', pauseDismissal);
    areaToast.addEventListener('mouseleave', scheduleDismissal);
    areaToast.addEventListener('focusin', pauseDismissal);
    areaToast.addEventListener('focusout', (event) => { if (!areaToast.contains(event.relatedTarget)) scheduleDismissal(); });
    scheduleDismissal();
}

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-area-info]');
    document.querySelectorAll('[data-area-info][aria-expanded="true"]').forEach((open) => {
        if (open !== button) open.setAttribute('aria-expanded', 'false');
    });
    if (button) button.setAttribute('aria-expanded', String(button.getAttribute('aria-expanded') !== 'true'));
});
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const open = document.querySelector('[data-area-info][aria-expanded="true"]');
    if (open) { open.setAttribute('aria-expanded', 'false'); open.focus(); }
});

const gisRoot = document.querySelector('[data-gis-workspace], [data-gis-editor]');
if (gisRoot) {
    const module = gisRoot.hasAttribute('data-gis-editor') ? import('./pages/gis-editor.js') : import('./pages/gis-workspace.js');
    module.then(({ initGis }) => initGis(gisRoot)).catch(() => {
        const status = gisRoot.querySelector('[data-gis-status], [data-gis-editor-status]');
        status.textContent = 'GIS is unavailable. Reload or try again later.';
        status.classList.add('notice-error');
        status.setAttribute('role', 'alert');
        void showFeedback({ type: 'error', title: 'GIS Unavailable', message: 'GIS could not be loaded. Reload the page or try again later.' });
        status.dataset.feedbackFallback = '';
    });
}

if (document.querySelector('[data-monitoring-photos]')) {
    import('./pages/monitoring-photos.js').then(({ initMonitoringPhotos }) => initMonitoringPhotos());
}

if (document.querySelector('[data-project-cover], [data-project-cover-thumbnail]')) {
    import('./pages/project-cover.js').then(({ initProjectCovers }) => initProjectCovers());
}
