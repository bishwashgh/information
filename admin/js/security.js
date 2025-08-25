// Security Dashboard JavaScript
class SecurityDashboard {
    constructor() {
        this.init();
        this.loadData();
        this.setupEventListeners();
        this.setupAutoRefresh();
    }

    init() {
        this.currentPage = 1;
        this.itemsPerPage = 20;
        this.filters = {
            severity: '',
            event_type: '',
            date_range: '7'
        };
    }

    async loadData() {
        try {
            await Promise.all([
                this.loadSecurityStats(),
                this.loadSecurityEvents(),
                this.loadLockedAccounts(),
                this.loadThreatLevel()
            ]);
        } catch (error) {
            console.error('Error loading security data:', error);
            this.showAlert('Error loading security data', 'danger');
        }
    }

    async loadSecurityStats() {
        try {
            const response = await fetch(`${window.siteUrl}/api/security.php?action=security_report&days=${this.filters.date_range}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateStatsDisplay(data.report.stats);
                this.updateEventBreakdown(data.report.event_breakdown);
                this.updateThreatSources(data.report.threat_sources);
            }
        } catch (error) {
            console.error('Error loading security stats:', error);
        }
    }

    updateStatsDisplay(stats) {
        document.getElementById('total-events').textContent = stats.total_events.toLocaleString();
        document.getElementById('high-severity').textContent = stats.high_severity_events.toLocaleString();
        document.getElementById('blocked-attempts').textContent = stats.blocked_login_attempts.toLocaleString();
        document.getElementById('failed-logins').textContent = stats.failed_logins.toLocaleString();
    }

    updateEventBreakdown(breakdown) {
        const container = document.getElementById('event-breakdown');
        if (!container) return;

        container.innerHTML = breakdown.map(item => `
            <div class="breakdown-item">
                <span class="event-type">${this.formatEventType(item.event_type)}</span>
                <span class="event-count">${item.count}</span>
            </div>
        `).join('');
    }

    updateThreatSources(sources) {
        const container = document.getElementById('threat-sources');
        if (!container) return;

        container.innerHTML = sources.map(source => `
            <div class="threat-source">
                <span class="ip-address">${source.ip_address}</span>
                <span class="threat-count">${source.threat_count} threats</span>
                <button class="btn btn-sm btn-danger" onclick="securityDashboard.banIP('${source.ip_address}')">
                    Ban IP
                </button>
            </div>
        `).join('');
    }

    async loadSecurityEvents() {
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: this.itemsPerPage,
                ...this.filters
            });

            const response = await fetch(`${window.siteUrl}/api/security.php?action=get_events&${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateEventsTable(data.events);
                this.updatePagination(data.pagination);
            }
        } catch (error) {
            console.error('Error loading security events:', error);
        }
    }

    updateEventsTable(events) {
        const tbody = document.getElementById('events-tbody');
        if (!tbody) return;

        tbody.innerHTML = events.map(event => `
            <tr>
                <td>${new Date(event.created_at).toLocaleString()}</td>
                <td>${this.formatEventType(event.event_type)}</td>
                <td>${event.username || 'Guest'}</td>
                <td>${event.ip_address}</td>
                <td><span class="severity-badge severity-${event.severity}">${event.severity}</span></td>
                <td>
                    <button class="btn btn-sm btn-secondary" onclick="securityDashboard.viewEventDetails(${event.id})">
                        View Details
                    </button>
                </td>
            </tr>
        `).join('');
    }

    async loadLockedAccounts() {
        try {
            const response = await fetch(`${window.siteUrl}/api/security.php?action=get_locked_accounts`);
            const data = await response.json();
            
            if (data.success) {
                this.updateLockedAccountsList(data.accounts);
            }
        } catch (error) {
            console.error('Error loading locked accounts:', error);
        }
    }

    updateLockedAccountsList(accounts) {
        const container = document.getElementById('locked-accounts-list');
        if (!container) return;

        if (accounts.length === 0) {
            container.innerHTML = '<p>No locked accounts</p>';
            return;
        }

        container.innerHTML = accounts.map(account => `
            <div class="locked-account">
                <div class="account-info">
                    <div class="account-identifier">${account.identifier}</div>
                    <div class="account-ip">${account.ip_address}</div>
                </div>
                <button class="unlock-btn" onclick="securityDashboard.unlockAccount('${account.identifier}', '${account.ip_address}')">
                    Unlock
                </button>
            </div>
        `).join('');
    }

    async loadThreatLevel() {
        try {
            const response = await fetch(`${window.siteUrl}/api/security.php?action=get_threat_level`);
            const data = await response.json();
            
            if (data.success) {
                this.updateThreatLevel(data.threat_level);
            }
        } catch (error) {
            console.error('Error loading threat level:', error);
        }
    }

    updateThreatLevel(level) {
        const container = document.getElementById('threat-level');
        if (!container) return;

        container.className = `threat-level threat-${level}`;
        container.querySelector('.threat-level-text').textContent = level.toUpperCase();
    }

    async unlockAccount(identifier, ipAddress) {
        try {
            const response = await fetch(`${window.siteUrl}/api/security.php?action=unlock_account`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    identifier: identifier,
                    ip_address: ipAddress
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showAlert('Account unlocked successfully', 'success');
                this.loadLockedAccounts();
            } else {
                this.showAlert(data.error || 'Failed to unlock account', 'danger');
            }
        } catch (error) {
            console.error('Error unlocking account:', error);
            this.showAlert('Error unlocking account', 'danger');
        }
    }

    async banIP(ipAddress) {
        const reason = prompt('Reason for banning this IP:');
        if (!reason) return;

        try {
            const response = await fetch(`${window.siteUrl}/api/security.php?action=ban_ip`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ip_address: ipAddress,
                    reason: reason
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showAlert('IP address banned successfully', 'success');
                this.loadData();
            } else {
                this.showAlert(data.error || 'Failed to ban IP address', 'danger');
            }
        } catch (error) {
            console.error('Error banning IP:', error);
            this.showAlert('Error banning IP address', 'danger');
        }
    }

    async viewEventDetails(eventId) {
        try {
            const response = await fetch(`${window.siteUrl}/api/security.php?action=get_event_details&id=${eventId}`);
            const data = await response.json();
            
            if (data.success) {
                this.showEventDetailsModal(data.event);
            } else {
                this.showAlert(data.error || 'Failed to load event details', 'danger');
            }
        } catch (error) {
            console.error('Error loading event details:', error);
            this.showAlert('Error loading event details', 'danger');
        }
    }

    showEventDetailsModal(event) {
        const modal = document.getElementById('event-details-modal');
        if (!modal) return;

        document.getElementById('event-details-content').innerHTML = `
            <div class="event-detail">
                <strong>Event ID:</strong> ${event.id}
            </div>
            <div class="event-detail">
                <strong>Type:</strong> ${this.formatEventType(event.event_type)}
            </div>
            <div class="event-detail">
                <strong>Date:</strong> ${new Date(event.created_at).toLocaleString()}
            </div>
            <div class="event-detail">
                <strong>User:</strong> ${event.username || 'Guest'}
            </div>
            <div class="event-detail">
                <strong>IP Address:</strong> ${event.ip_address}
            </div>
            <div class="event-detail">
                <strong>Severity:</strong> <span class="severity-badge severity-${event.severity}">${event.severity}</span>
            </div>
            <div class="event-detail">
                <strong>Details:</strong>
                <pre>${JSON.stringify(JSON.parse(event.event_data || '{}'), null, 2)}</pre>
            </div>
        `;

        modal.style.display = 'block';
    }

    async exportSecurityLog() {
        const days = document.getElementById('export-days').value || 30;
        window.open(`${window.siteUrl}/api/security.php?action=export_security_log&days=${days}`, '_blank');
    }

    async runSecurityTest(testType) {
        try {
            const response = await fetch(`${window.siteUrl}/api/security.php?action=test_security&test_type=${testType}`);
            const data = await response.json();
            
            if (data.success) {
                this.showTestResult(data);
            } else {
                this.showAlert(data.error || 'Security test failed', 'danger');
            }
        } catch (error) {
            console.error('Error running security test:', error);
            this.showAlert('Error running security test', 'danger');
        }
    }

    showTestResult(result) {
        const modal = document.getElementById('test-result-modal');
        if (!modal) return;

        document.getElementById('test-result-content').innerHTML = `
            <h4>${result.test}</h4>
            <div class="test-result">
                <strong>Input:</strong> <code>${result.input || 'N/A'}</code>
            </div>
            <div class="test-result">
                <strong>Output:</strong> <code>${result.output || 'N/A'}</code>
            </div>
            <div class="test-result">
                <strong>Status:</strong> 
                <span class="badge ${result.blocked || result.detected ? 'badge-success' : 'badge-warning'}">
                    ${result.blocked ? 'Blocked' : result.detected ? 'Detected' : 'Not Detected'}
                </span>
            </div>
            ${result.results ? `
                <div class="test-result">
                    <strong>Results:</strong>
                    <pre>${JSON.stringify(result.results, null, 2)}</pre>
                </div>
            ` : ''}
        `;

        modal.style.display = 'block';
    }

    setupEventListeners() {
        // Filter changes
        document.getElementById('severity-filter')?.addEventListener('change', (e) => {
            this.filters.severity = e.target.value;
            this.currentPage = 1;
            this.loadSecurityEvents();
        });

        document.getElementById('event-type-filter')?.addEventListener('change', (e) => {
            this.filters.event_type = e.target.value;
            this.currentPage = 1;
            this.loadSecurityEvents();
        });

        document.getElementById('date-range-filter')?.addEventListener('change', (e) => {
            this.filters.date_range = e.target.value;
            this.loadData();
        });

        // Modal close handlers
        document.querySelectorAll('.modal .close').forEach(close => {
            close.addEventListener('click', (e) => {
                e.target.closest('.modal').style.display = 'none';
            });
        });

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        });
    }

    setupAutoRefresh() {
        // Refresh data every 30 seconds
        setInterval(() => {
            this.loadData();
        }, 30000);
    }

    formatEventType(eventType) {
        return eventType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alert-container') || document.body;
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="close" onclick="this.parentElement.remove()">
                <span>&times;</span>
            </button>
        `;
        alertContainer.insertBefore(alert, alertContainer.firstChild);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 5000);
    }

    updatePagination(pagination) {
        const container = document.getElementById('pagination');
        if (!container || !pagination) return;

        const { current_page, total_pages, has_prev, has_next } = pagination;
        
        let html = '';
        
        if (has_prev) {
            html += `<button onclick="securityDashboard.changePage(${current_page - 1})">Previous</button>`;
        }
        
        for (let i = Math.max(1, current_page - 2); i <= Math.min(total_pages, current_page + 2); i++) {
            html += `<button class="${i === current_page ? 'active' : ''}" onclick="securityDashboard.changePage(${i})">${i}</button>`;
        }
        
        if (has_next) {
            html += `<button onclick="securityDashboard.changePage(${current_page + 1})">Next</button>`;
        }
        
        container.innerHTML = html;
    }

    changePage(page) {
        this.currentPage = page;
        this.loadSecurityEvents();
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.securityDashboard = new SecurityDashboard();
});
