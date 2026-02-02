// ========================================
// DOM Elements
// ========================================
const elements = {
    // Navigation
    navItems: document.querySelectorAll('.nav-item'),
    sections: document.querySelectorAll('.content-section'),
    pageTitle: document.getElementById('pageTitle'),
    menuToggle: document.getElementById('menuToggle'),
    sidebar: document.querySelector('.sidebar'),
    
    // Quick Create
    quickCreateBtn: document.getElementById('quickCreateBtn'),
    createModal: document.getElementById('createModal'),
    closeCreateModal: document.getElementById('closeCreateModal'),
    quickCreateForm: document.getElementById('quickCreateForm'),
    
    // Main Create Form
    createLinkForm: document.getElementById('createLinkForm'),
    
    // Loading Modal
    loadingModal: document.getElementById('loadingModal'),
    
    // Success Modal
    successModal: document.getElementById('successModal'),
    generatedLink: document.getElementById('generatedLink'),
    copyLinkBtn: document.getElementById('copyLinkBtn'),
    closeSuccessModal: document.getElementById('closeSuccessModal'),
    copyAndClose: document.getElementById('copyAndClose'),
    
    // Details Modal
    detailsModal: document.getElementById('detailsModal'),
    closeDetailsModal: document.getElementById('closeDetailsModal'),
    linkDetailsContent: document.getElementById('linkDetailsContent'),
    
    // Edit Modal
    editModal: document.getElementById('editModal'),
    closeEditModal: document.getElementById('closeEditModal'),
    editLinkForm: document.getElementById('editLinkForm'),
    
    // Delete Modal
    deleteModal: document.getElementById('deleteModal'),
    cancelDelete: document.getElementById('cancelDelete'),
    confirmDelete: document.getElementById('confirmDelete'),
    deleteLinkId: document.getElementById('deleteLinkId'),
    
    // Links Containers
    recentLinksContainer: document.getElementById('recentLinksContainer'),
    allLinksContainer: document.getElementById('allLinksContainer'),
    
    // Filters
    searchLinks: document.getElementById('searchLinks'),
    filterStatus: document.getElementById('filterStatus'),
    
    // Toast
    toast: document.getElementById('toast')
};

// ========================================
// Utility Functions
// ========================================
function showToast(message, type = 'success') {
    const toast = elements.toast;
    const icon = toast.querySelector('.toast-icon');
    const msg = toast.querySelector('.toast-message');
    
    toast.className = `toast ${type}`;
    icon.className = `toast-icon fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}`;
    msg.textContent = message;
    
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

function showModal(modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function hideModal(modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function truncateUrl(url, maxLength = 50) {
    if (url.length <= maxLength) return url;
    return url.substring(0, maxLength) + '...';
}

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Link copied to clipboard!');
        }).catch(() => {
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    showToast('Link copied to clipboard!');
}

// ========================================
// API Functions
// ========================================
async function apiRequest(action, data = {}, method = 'POST') {
    const formData = new FormData();
    formData.append('action', action);
    
    for (const key in data) {
        formData.append(key, data[key]);
    }
    
    try {
        const response = await fetch(`api.php?action=${action}`, {
            method: method,
            body: method === 'POST' ? formData : undefined
        });

        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (jsonError) {
            // Optionally, log the raw response for debugging
            console.error('API raw response:', text);
            // Show a user-friendly error message
            return { success: false, message: 'Server returned invalid response', raw_response: text };
        }
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'Network error occurred' };
    }
}

// ========================================
// Link Rendering
// ========================================
function renderLinkItem(link) {
    return `
        <div class="link-item" data-id="${link.id}">
            <div class="link-info">
                <div class="link-title">
                    ${escapeHtml(link.title)}
                    <span class="status-badge ${link.status}">${link.status}</span>
                </div>
                <div class="link-urls">
                    <div class="link-short">
                        <i class="fas fa-link"></i>
                        <a href="${escapeHtml(link.short_url)}" target="_blank">${escapeHtml(link.short_url)}</a>
                    </div>
                    <div class="link-original">
                        <i class="fas fa-globe"></i>
                        <span title="${escapeHtml(link.original_url)}">${truncateUrl(link.original_url)}</span>
                    </div>
                </div>
                <div class="link-meta">
                    <div class="meta-item">
                        <i class="fas fa-folder"></i>
                        ${escapeHtml(link.category)}
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-mouse-pointer"></i>
                        ${link.clicks} clicks
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        ${formatDate(link.created_at)}
                    </div>
                </div>
            </div>
            <div class="link-actions">
                <button class="btn btn-icon" onclick="copyLink('${escapeHtml(link.short_url)}')" title="Copy">
                    <i class="fas fa-copy"></i>
                </button>
                <button class="btn btn-icon" onclick="shareLink('${escapeHtml(link.short_url)}', '${escapeHtml(link.title)}')" title="Share">
                    <i class="fas fa-share-alt"></i>
                </button>
                <button class="btn btn-icon" onclick="viewDetails('${link.id}')" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-icon" onclick="editLink('${link.id}')" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-icon danger" onclick="deleteLink('${link.id}')" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function renderEmptyState() {
    return `
        <div class="empty-state">
            <i class="fas fa-link"></i>
            <h3>No links yet</h3>
            <p>Create your first shortened link to get started</p>
        </div>
    `;
}

// ========================================
// Load Links
// ========================================
async function loadLinks(container, limit = null) {
    const search = elements.searchLinks?.value || '';
    const status = elements.filterStatus?.value || 'all';
    
    const result = await apiRequest('list', { search, status }, 'GET');
    
    if (result.success) {
        let links = result.links;
        
        if (limit) {
            links = links.slice(0, limit);
        }
        
        if (links.length === 0) {
            container.innerHTML = renderEmptyState();
        } else {
            container.innerHTML = links.map(link => renderLinkItem(link)).join('');
        }
    } else {
        container.innerHTML = '<p class="error">Error loading links</p>';
    }
}

async function refreshLinks() {
    await loadLinks(elements.recentLinksContainer, 5);
    await loadLinks(elements.allLinksContainer);
}

// ========================================
// Create Link
// ========================================
async function createLink(formData) {
    showModal(elements.loadingModal);

    const data = {
        title: formData.get('title'),
        url: formData.get('url'),
        category: formData.get('category') || 'general',
        notes: formData.get('notes') || ''
    };

    // Improved URL validation
    const urlPattern = /^(https?:\/\/)[^\s$.?#].[^\s]*$/i;
    if (!urlPattern.test(data.url)) {
        hideModal(elements.loadingModal);
        showToast('Please enter a valid URL (including http:// or https://)', 'error');
        return;
    }

    const result = await apiRequest('create', data);

    hideModal(elements.loadingModal);

    if (result.success) {
        hideModal(elements.createModal);
        elements.generatedLink.value = result.link.short_url;
        showModal(elements.successModal);
        refreshLinks();
        updateStats();
    } else {
        showToast(result.message || 'Failed to create link', 'error');
    }
}

// ========================================
// Link Actions
// ========================================
function copyLink(url) {
    copyToClipboard(url);
}

function shareLink(url, title) {
    if (navigator.share) {
        navigator.share({
            title: title,
            url: url
        }).catch(() => {});
    } else {
        copyToClipboard(url);
        showToast('Link copied! Share it anywhere.');
    }
}

async function viewDetails(id) {
    const result = await apiRequest('get', { id }, 'GET');
    
    if (result.success) {
        const link = result.link;
        
        elements.linkDetailsContent.innerHTML = `
            <div class="detail-group">
                <div class="detail-label">Title</div>
                <div class="detail-value">${escapeHtml(link.title)}</div>
            </div>
            <div class="detail-group">
                <div class="detail-label">Short URL</div>
                <div class="detail-value">
                    <a href="${escapeHtml(link.short_url)}" target="_blank">${escapeHtml(link.short_url)}</a>
                </div>
            </div>
            <div class="detail-group">
                <div class="detail-label">Original URL</div>
                <div class="detail-value">
                    <a href="${escapeHtml(link.original_url)}" target="_blank">${escapeHtml(link.original_url)}</a>
                </div>
            </div>
            <div class="detail-grid">
                <div class="detail-group">
                    <div class="detail-label">Category</div>
                    <div class="detail-value">${escapeHtml(link.category)}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="status-badge ${link.status}">${link.status}</span>
                    </div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Clicks</div>
                    <div class="detail-value">${link.clicks}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Created</div>
                    <div class="detail-value">${formatDate(link.created_at)}</div>
                </div>
            </div>
            ${link.notes ? `
                <div class="detail-group">
                    <div class="detail-label">Notes</div>
                    <div class="detail-value">${escapeHtml(link.notes)}</div>
                </div>
            ` : ''}
        `;
        
        showModal(elements.detailsModal);
    } else {
        showToast('Failed to load link details', 'error');
    }
}

async function editLink(id) {
    const result = await apiRequest('get', { id }, 'GET');
    
    if (result.success) {
        const link = result.link;
        
        document.getElementById('editLinkId').value = link.id;
        document.getElementById('editTitle').value = link.title;
        document.getElementById('editUrl').value = link.original_url;
        document.getElementById('editCategory').value = link.category;
        document.getElementById('editNotes').value = link.notes || '';
        document.getElementById('editStatus').value = link.status;
        
        showModal(elements.editModal);
    } else {
        showToast('Failed to load link', 'error');
    }
}

function deleteLink(id) {
    elements.deleteLinkId.value = id;
    showModal(elements.deleteModal);
}

async function confirmDeleteLink() {
    const id = elements.deleteLinkId.value;
    
    const result = await apiRequest('delete', { id });
    
    hideModal(elements.deleteModal);
    
    if (result.success) {
        showToast('Link deleted successfully');
        refreshLinks();
        updateStats();
    } else {
        showToast('Failed to delete link', 'error');
    }
}

async function saveEditLink(formData) {
    const data = {
        id: formData.get('id'),
        title: formData.get('title'),
        url: formData.get('url'),
        category: formData.get('category'),
        notes: formData.get('notes'),
        status: formData.get('status')
    };
    
    const result = await apiRequest('update', data);
    
    hideModal(elements.editModal);
    
    if (result.success) {
        showToast('Link updated successfully');
        refreshLinks();
    } else {
        showToast('Failed to update link', 'error');
    }
}

// ========================================
// Stats
// ========================================
async function updateStats() {
    const result = await apiRequest('stats', {}, 'GET');
    
    if (result.success) {
        // Update stat cards if needed
        // Stats are loaded on page load from PHP
    }
}

// ========================================
// Navigation
// ========================================
function switchSection(sectionId) {
    elements.sections.forEach(section => {
        section.classList.remove('active');
    });
    
    elements.navItems.forEach(item => {
        item.classList.remove('active');
    });
    
    const targetSection = document.getElementById(`${sectionId}Section`);
    const targetNav = document.querySelector(`[data-section="${sectionId}"]`);
    
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    if (targetNav) {
        targetNav.classList.add('active');
    }
    
    const titles = {
        dashboard: 'Dashboard',
        links: 'All Links',
        create: 'Create New Link'
    };
    
    elements.pageTitle.textContent = titles[sectionId] || 'Dashboard';
    
    // Close mobile sidebar
    elements.sidebar.classList.remove('active');
}

// ========================================
// Event Listeners
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    // Load initial data
    refreshLinks();
    
    // Navigation
    elements.navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const section = item.dataset.section;
            switchSection(section);
        });
    });
    
    // View all links
    document.querySelectorAll('[data-section]').forEach(el => {
        if (el.classList.contains('view-all')) {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                switchSection('links');
            });
        }
    });
    
    // Mobile menu
    elements.menuToggle?.addEventListener('click', () => {
        elements.sidebar.classList.toggle('active');
    });
    
    // Quick create button
    elements.quickCreateBtn?.addEventListener('click', () => {
        showModal(elements.createModal);
    });
    
    // Close modals
    elements.closeCreateModal?.addEventListener('click', () => {
        hideModal(elements.createModal);
    });
    
    elements.closeSuccessModal?.addEventListener('click', () => {
        hideModal(elements.successModal);
    });
    
    elements.closeDetailsModal?.addEventListener('click', () => {
        hideModal(elements.detailsModal);
    });
    
    elements.closeEditModal?.addEventListener('click', () => {
        hideModal(elements.editModal);
    });
    
    elements.cancelDelete?.addEventListener('click', () => {
        hideModal(elements.deleteModal);
    });
    
    // Click outside modal to close
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                hideModal(modal);
            }
        });
    });
    
    // Quick create form
    elements.quickCreateForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        createLink(formData);
        e.target.reset();
    });
    
    // Main create form
    elements.createLinkForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        createLink(formData);
        e.target.reset();
    });
    
    // Edit form
    elements.editLinkForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        saveEditLink(formData);
    });
    
    // Confirm delete
    elements.confirmDelete?.addEventListener('click', () => {
        confirmDeleteLink();
    });
    
    // Copy link button
    elements.copyLinkBtn?.addEventListener('click', () => {
        copyToClipboard(elements.generatedLink.value);
    });
    
    // Copy and close
    elements.copyAndClose?.addEventListener('click', () => {
        copyToClipboard(elements.generatedLink.value);
        hideModal(elements.successModal);
    });
    
    // Search and filter
    let searchTimeout;
    elements.searchLinks?.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadLinks(elements.allLinksContainer);
        }, 300);
    });
    
    elements.filterStatus?.addEventListener('change', () => {
        loadLinks(elements.allLinksContainer);
    });
});

// Expose functions globally for onclick handlers
window.copyLink = copyLink;
window.shareLink = shareLink;
window.viewDetails = viewDetails;
window.editLink = editLink;
window.deleteLink = deleteLink;