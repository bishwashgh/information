// Payment Dashboard JavaScript
class PaymentDashboard {
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
            status: '',
            gateway: '',
            date_range: '30'
        };
    }

    async loadData() {
        try {
            await Promise.all([
                this.loadPaymentStats(),
                this.loadTransactions(),
                this.loadGatewayStatus(),
                this.loadRecentActivity()
            ]);
        } catch (error) {
            console.error('Error loading payment data:', error);
            this.showAlert('Error loading payment data', 'danger');
        }
    }

    async loadPaymentStats() {
        try {
            const response = await fetch(`${window.siteUrl}/api/payments.php?action=get_payment_stats&days=${this.filters.date_range}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateStatsDisplay(data.stats);
            }
        } catch (error) {
            console.error('Error loading payment stats:', error);
        }
    }

    updateStatsDisplay(stats) {
        document.getElementById('total-revenue').textContent = this.formatCurrency(stats.total_revenue);
        document.getElementById('total-transactions').textContent = stats.total_transactions.toLocaleString();
        document.getElementById('successful-payments').textContent = stats.successful_payments.toLocaleString();
        document.getElementById('failed-payments').textContent = stats.failed_payments.toLocaleString();
        document.getElementById('refund-amount').textContent = this.formatCurrency(stats.refund_amount);
        document.getElementById('average-transaction').textContent = this.formatCurrency(stats.average_transaction_amount);

        // Update success rate
        const successRate = stats.total_transactions > 0 ? 
            ((stats.successful_payments / stats.total_transactions) * 100).toFixed(1) : 0;
        document.getElementById('success-rate').textContent = `${successRate}%`;

        // Update gateway breakdown
        this.updateGatewayBreakdown(stats.gateway_breakdown);
    }

    updateGatewayBreakdown(breakdown) {
        const container = document.getElementById('gateway-breakdown');
        if (!container) return;

        container.innerHTML = Object.entries(breakdown).map(([gateway, data]) => `
            <div class="gateway-stat">
                <span class="gateway-name">${this.formatGatewayName(gateway)}</span>
                <span class="gateway-amount">${this.formatCurrency(data.amount)}</span>
                <span class="gateway-count">${data.count} transactions</span>
            </div>
        `).join('');
    }

    async loadTransactions() {
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: this.itemsPerPage,
                ...this.filters
            });

            const response = await fetch(`${window.siteUrl}/api/payments.php?action=get_transactions&${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateTransactionsTable(data.transactions.data);
                this.updatePagination(data.transactions.pagination);
            }
        } catch (error) {
            console.error('Error loading transactions:', error);
        }
    }

    updateTransactionsTable(transactions) {
        const tbody = document.getElementById('transactions-tbody');
        if (!tbody) return;

        tbody.innerHTML = transactions.map(transaction => `
            <tr>
                <td>${transaction.transaction_id}</td>
                <td>${new Date(transaction.created_at).toLocaleString()}</td>
                <td>${transaction.username || 'Guest'}</td>
                <td><span class="amount">${this.formatCurrency(transaction.amount, transaction.currency)}</span></td>
                <td>${this.formatGatewayName(transaction.gateway)}</td>
                <td><span class="status-badge status-${transaction.status}">${transaction.status}</span></td>
                <td>
                    <button class="btn btn-sm btn-secondary" onclick="paymentDashboard.viewTransactionDetails('${transaction.transaction_id}')">
                        View
                    </button>
                    ${transaction.status === 'completed' ? `
                        <button class="btn btn-sm btn-warning" onclick="paymentDashboard.processRefund('${transaction.transaction_id}')">
                            Refund
                        </button>
                    ` : ''}
                </td>
            </tr>
        `).join('');
    }

    async loadGatewayStatus() {
        try {
            const response = await fetch(`${window.siteUrl}/api/payments.php?action=get_gateway_status`);
            const data = await response.json();
            
            if (data.success) {
                this.updateGatewayStatus(data.gateways);
            }
        } catch (error) {
            console.error('Error loading gateway status:', error);
        }
    }

    updateGatewayStatus(gateways) {
        const container = document.getElementById('gateway-status');
        if (!container) return;

        container.innerHTML = Object.entries(gateways).map(([gateway, status]) => `
            <div class="gateway-indicator gateway-${status.active ? 'active' : 'inactive'}">
                ${this.formatGatewayName(gateway)}
            </div>
        `).join('');
    }

    async loadRecentActivity() {
        try {
            const response = await fetch(`${window.siteUrl}/api/payments.php?action=get_recent_activity&limit=10`);
            const data = await response.json();
            
            if (data.success) {
                this.updateRecentActivity(data.activities);
            }
        } catch (error) {
            console.error('Error loading recent activity:', error);
        }
    }

    updateRecentActivity(activities) {
        const container = document.getElementById('recent-activity');
        if (!container) return;

        if (activities.length === 0) {
            container.innerHTML = '<p>No recent activity</p>';
            return;
        }

        container.innerHTML = activities.map(activity => `
            <div class="activity-item">
                <div class="activity-info">
                    <div class="activity-description">${activity.description}</div>
                    <div class="activity-time">${this.formatTimeAgo(activity.created_at)}</div>
                </div>
                <div class="activity-amount">${this.formatCurrency(activity.amount, activity.currency)}</div>
            </div>
        `).join('');
    }

    async viewTransactionDetails(transactionId) {
        try {
            const response = await fetch(`${window.siteUrl}/api/payments.php?action=get_transaction_details&transaction_id=${transactionId}`);
            const data = await response.json();
            
            if (data.success) {
                this.showTransactionDetailsModal(data.transaction);
            } else {
                this.showAlert(data.error || 'Failed to load transaction details', 'danger');
            }
        } catch (error) {
            console.error('Error loading transaction details:', error);
            this.showAlert('Error loading transaction details', 'danger');
        }
    }

    showTransactionDetailsModal(transaction) {
        const modal = document.getElementById('transaction-details-modal');
        if (!modal) return;

        document.getElementById('transaction-details-content').innerHTML = `
            <div class="transaction-detail">
                <strong>Transaction ID:</strong> ${transaction.transaction_id}
            </div>
            <div class="transaction-detail">
                <strong>Date:</strong> ${new Date(transaction.created_at).toLocaleString()}
            </div>
            <div class="transaction-detail">
                <strong>Customer:</strong> ${transaction.username || 'Guest'} (${transaction.email || 'N/A'})
            </div>
            <div class="transaction-detail">
                <strong>Amount:</strong> ${this.formatCurrency(transaction.amount, transaction.currency)}
            </div>
            <div class="transaction-detail">
                <strong>Gateway:</strong> ${this.formatGatewayName(transaction.gateway)}
            </div>
            <div class="transaction-detail">
                <strong>Status:</strong> <span class="status-badge status-${transaction.status}">${transaction.status}</span>
            </div>
            <div class="transaction-detail">
                <strong>Payment Method:</strong> ${transaction.payment_method || 'N/A'}
            </div>
            ${transaction.gateway_transaction_id ? `
                <div class="transaction-detail">
                    <strong>Gateway Transaction ID:</strong> ${transaction.gateway_transaction_id}
                </div>
            ` : ''}
            ${transaction.metadata ? `
                <div class="transaction-detail">
                    <strong>Metadata:</strong>
                    <pre>${JSON.stringify(JSON.parse(transaction.metadata), null, 2)}</pre>
                </div>
            ` : ''}
        `;

        modal.style.display = 'block';
    }

    async processRefund(transactionId) {
        const amount = prompt('Enter refund amount (leave empty for full refund):');
        const reason = prompt('Reason for refund:') || 'Admin refund';
        
        if (reason === null) return; // User cancelled

        try {
            const refundData = {
                transaction_id: transactionId,
                reason: reason
            };

            if (amount && !isNaN(parseFloat(amount))) {
                refundData.amount = parseFloat(amount);
            }

            const response = await fetch(`${window.siteUrl}/api/payments.php?action=process_refund`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(refundData)
            });

            const data = await response.json();
            
            if (data.success) {
                this.showAlert('Refund processed successfully', 'success');
                this.loadData();
            } else {
                this.showAlert(data.error || 'Failed to process refund', 'danger');
            }
        } catch (error) {
            console.error('Error processing refund:', error);
            this.showAlert('Error processing refund', 'danger');
        }
    }

    async configureGateway(gateway) {
        // This would open a configuration modal for the specific gateway
        const modal = document.getElementById('gateway-config-modal');
        if (!modal) return;

        // Load current configuration
        try {
            const response = await fetch(`${window.siteUrl}/api/payments.php?action=get_gateway_config&gateway=${gateway}`);
            const data = await response.json();
            
            if (data.success) {
                this.showGatewayConfigModal(gateway, data.config);
            }
        } catch (error) {
            console.error('Error loading gateway config:', error);
        }
    }

    showGatewayConfigModal(gateway, config) {
        const modal = document.getElementById('gateway-config-modal');
        if (!modal) return;

        document.getElementById('gateway-config-content').innerHTML = this.generateGatewayConfigForm(gateway, config);
        modal.style.display = 'block';
    }

    generateGatewayConfigForm(gateway, config) {
        const fields = this.getGatewayConfigFields(gateway);
        
        return `
            <form id="gateway-config-form">
                <input type="hidden" name="gateway" value="${gateway}">
                <h4>Configure ${this.formatGatewayName(gateway)}</h4>
                ${fields.map(field => `
                    <div class="form-group">
                        <label for="${field.name}">${field.label}</label>
                        <input type="${field.type}" 
                               name="${field.name}" 
                               id="${field.name}"
                               value="${config[field.name] || ''}" 
                               ${field.required ? 'required' : ''}
                               ${field.type === 'password' ? 'autocomplete="new-password"' : ''}>
                        ${field.help ? `<small>${field.help}</small>` : ''}
                    </div>
                `).join('')}
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="enabled" ${config.enabled ? 'checked' : ''}> 
                        Enable this gateway
                    </label>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save Configuration</button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('gateway-config-modal').style.display='none'">Cancel</button>
                </div>
            </form>
        `;
    }

    getGatewayConfigFields(gateway) {
        const fields = {
            stripe: [
                { name: 'publishable_key', label: 'Publishable Key', type: 'text', required: true },
                { name: 'secret_key', label: 'Secret Key', type: 'password', required: true },
                { name: 'webhook_secret', label: 'Webhook Secret', type: 'password', help: 'Used to verify webhook signatures' }
            ],
            paypal: [
                { name: 'client_id', label: 'Client ID', type: 'text', required: true },
                { name: 'client_secret', label: 'Client Secret', type: 'password', required: true },
                { name: 'sandbox', label: 'Sandbox Mode', type: 'checkbox', help: 'Enable for testing' }
            ],
            razorpay: [
                { name: 'key_id', label: 'Key ID', type: 'text', required: true },
                { name: 'key_secret', label: 'Key Secret', type: 'password', required: true },
                { name: 'webhook_secret', label: 'Webhook Secret', type: 'password' }
            ],
            square: [
                { name: 'application_id', label: 'Application ID', type: 'text', required: true },
                { name: 'access_token', label: 'Access Token', type: 'password', required: true },
                { name: 'location_id', label: 'Location ID', type: 'text', required: true },
                { name: 'sandbox', label: 'Sandbox Mode', type: 'checkbox' }
            ]
        };

        return fields[gateway] || [];
    }

    async exportTransactions() {
        const startDate = document.getElementById('export-start-date').value;
        const endDate = document.getElementById('export-end-date').value;
        const status = document.getElementById('export-status').value;
        
        const params = new URLSearchParams({
            start_date: startDate,
            end_date: endDate,
            status: status
        });

        window.open(`${window.siteUrl}/api/payments.php?action=export_transactions&${params}`, '_blank');
    }

    setupEventListeners() {
        // Filter changes
        document.getElementById('status-filter')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.currentPage = 1;
            this.loadTransactions();
        });

        document.getElementById('gateway-filter')?.addEventListener('change', (e) => {
            this.filters.gateway = e.target.value;
            this.currentPage = 1;
            this.loadTransactions();
        });

        document.getElementById('date-range-filter')?.addEventListener('change', (e) => {
            this.filters.date_range = e.target.value;
            this.loadData();
        });

        // Gateway configuration form
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'gateway-config-form') {
                e.preventDefault();
                this.saveGatewayConfig(e.target);
            }
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

    async saveGatewayConfig(form) {
        try {
            const formData = new FormData(form);
            const config = {};
            
            for (let [key, value] of formData.entries()) {
                if (key !== 'gateway') {
                    config[key] = value;
                }
            }

            const response = await fetch(`${window.siteUrl}/api/payments.php?action=configure_gateway`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    gateway: formData.get('gateway'),
                    config: config
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showAlert('Gateway configuration saved successfully', 'success');
                document.getElementById('gateway-config-modal').style.display = 'none';
                this.loadGatewayStatus();
            } else {
                this.showAlert(data.error || 'Failed to save gateway configuration', 'danger');
            }
        } catch (error) {
            console.error('Error saving gateway config:', error);
            this.showAlert('Error saving gateway configuration', 'danger');
        }
    }

    setupAutoRefresh() {
        // Refresh data every 60 seconds
        setInterval(() => {
            this.loadData();
        }, 60000);
    }

    formatCurrency(amount, currency = 'INR') {
        const symbol = {
            'INR': '₹',
            'USD': '$',
            'EUR': '€',
            'GBP': '£'
        }[currency] || currency;
        
        return `${symbol}${parseFloat(amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    }

    formatGatewayName(gateway) {
        return gateway.charAt(0).toUpperCase() + gateway.slice(1);
    }

    formatTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diff = now - time;
        
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`;
        if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        return 'Just now';
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
            html += `<button onclick="paymentDashboard.changePage(${current_page - 1})">Previous</button>`;
        }
        
        for (let i = Math.max(1, current_page - 2); i <= Math.min(total_pages, current_page + 2); i++) {
            html += `<button class="${i === current_page ? 'active' : ''}" onclick="paymentDashboard.changePage(${i})">${i}</button>`;
        }
        
        if (has_next) {
            html += `<button onclick="paymentDashboard.changePage(${current_page + 1})">Next</button>`;
        }
        
        container.innerHTML = html;
    }

    changePage(page) {
        this.currentPage = page;
        this.loadTransactions();
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.paymentDashboard = new PaymentDashboard();
});
