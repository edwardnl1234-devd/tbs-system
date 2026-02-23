import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Request interceptor to add auth token
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

// Response interceptor for error handling
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

// Auth API
export const authApi = {
    login: (credentials) => api.post('/auth/login', credentials),
    logout: () => api.post('/auth/logout'),
    getUser: () => api.get('/auth/user'),
    updateProfile: (data) => api.put('/auth/profile', data),
    changePassword: (data) => api.put('/auth/password', data),
};

// Dashboard API
export const dashboardApi = {
    getAll: () => api.get('/dashboard'),
    getQueueStats: () => api.get('/dashboard/queue-stats'),
    getProductionStats: () => api.get('/dashboard/production-stats'),
    getStockSummary: () => api.get('/dashboard/stock-summary'),
    getSalesStats: () => api.get('/dashboard/sales-stats'),
    getMargin: () => api.get('/dashboard/margin'),
    getEfficiency: () => api.get('/dashboard/efficiency'),
};

// Queue API
export const queueApi = {
    getAll: (params) => api.get('/queues', { params }),
    getOne: (id) => api.get(`/queues/${id}`),
    create: (data) => api.post('/queues', data),
    update: (id, data) => api.put(`/queues/${id}`, data),
    delete: (id) => api.delete(`/queues/${id}`),
    getActive: () => api.get('/queues/active'),
    getToday: () => api.get('/queues/today'),
    getByBank: (bank) => api.get(`/queues/by-bank/${bank}`),
    updateStatus: (id, status) => api.put(`/queues/${id}/status`, { status }),
    getStatistics: () => api.get('/queues/statistics'),
};

// Weighing API
export const weighingApi = {
    getAll: (params) => api.get('/weighings', { params }),
    getOne: (id) => api.get(`/weighings/${id}`),
    create: (data) => api.post('/weighings', data),
    update: (id, data) => api.put(`/weighings/${id}`, data),
    delete: (id) => api.delete(`/weighings/${id}`),
    weighIn: (id, data) => api.post(`/weighings/${id}/weigh-in`, data),
    weighOut: (id, data) => api.post(`/weighings/${id}/weigh-out`, data),
    complete: (id) => api.post(`/weighings/${id}/complete`),
    updateDerivatives: (id, data) => api.post(`/weighings/${id}/derivatives`, data),
    getToday: () => api.get('/weighings/today'),
    getPending: () => api.get('/weighings/pending'),
};

// Sortation API
export const sortationApi = {
    getAll: (params) => api.get('/sortations', { params }),
    getOne: (id) => api.get(`/sortations/${id}`),
    create: (data) => api.post('/sortations', data),
    update: (id, data) => api.put(`/sortations/${id}`, data),
    getToday: () => api.get('/sortations/today'),
    getPerformance: () => api.get('/sortations/performance'),
};

// Production API
export const productionApi = {
    getAll: (params) => api.get('/productions', { params }),
    getOne: (id) => api.get(`/productions/${id}`),
    create: (data) => api.post('/productions', data),
    update: (id, data) => api.put(`/productions/${id}`, data),
    delete: (id) => api.delete(`/productions/${id}`),
    getToday: () => api.get('/productions/today'),
    getByDate: (date) => api.get(`/productions/by-date/${date}`),
    getStatistics: () => api.get('/productions/statistics'),
    getEfficiency: () => api.get('/productions/efficiency'),
};

// Stock API
export const stockApi = {
    // CPO
    getCpo: (params) => api.get('/stock/cpo', { params }),
    getCpoSummary: () => api.get('/stock/cpo/summary'),
    getCpoAvailable: () => api.get('/stock/cpo/available'),
    createCpo: (data) => api.post('/stock/cpo', data),
    // Kernel
    getKernel: (params) => api.get('/stock/kernel', { params }),
    getKernelSummary: () => api.get('/stock/kernel/summary'),
    getKernelAvailable: () => api.get('/stock/kernel/available'),
    createKernel: (data) => api.post('/stock/kernel', data),
    // Shell
    getShell: (params) => api.get('/stock/shell', { params }),
    getShellSummary: () => api.get('/stock/shell/summary'),
    getShellAvailable: () => api.get('/stock/shell/available'),
    createShell: (data) => api.post('/stock/shell', data),
};

// Stock Purchase API (Pembelian dari Supplier)
export const stockPurchaseApi = {
    getAll: (params) => api.get('/stock-purchases', { params }),
    getSummary: () => api.get('/stock-purchases/summary'),
    getHistory: (params) => api.get('/stock-purchases/history', { params }),
    getSuppliers: () => api.get('/stock-purchases/suppliers'),
    getBySupplier: (supplierId) => api.get(`/stock-purchases/by-supplier/${supplierId}`),
    purchaseCpo: (data) => api.post('/stock-purchases/cpo', data),
    purchaseKernel: (data) => api.post('/stock-purchases/kernel', data),
    purchaseShell: (data) => api.post('/stock-purchases/shell', data),
    updateCpoStatus: (id, status) => api.patch(`/stock-purchases/cpo/${id}/status`, { purchase_status: status }),
    updateKernelStatus: (id, status) => api.patch(`/stock-purchases/kernel/${id}/status`, { purchase_status: status }),
    updateShellStatus: (id, status) => api.patch(`/stock-purchases/shell/${id}/status`, { purchase_status: status }),
    deleteCpo: (id) => api.delete(`/stock-purchases/cpo/${id}`),
    deleteKernel: (id) => api.delete(`/stock-purchases/kernel/${id}`),
    deleteShell: (id) => api.delete(`/stock-purchases/shell/${id}`),
};

// Sales API
export const salesApi = {
    getAll: (params) => api.get('/sales', { params }),
    getOne: (id) => api.get(`/sales/${id}`),
    create: (data) => api.post('/sales', data),
    update: (id, data) => api.put(`/sales/${id}`, data),
    delete: (id) => api.delete(`/sales/${id}`),
    deliver: (id) => api.post(`/sales/${id}/deliver`),
    complete: (id) => api.post(`/sales/${id}/complete`),
    getToday: () => api.get('/sales/today'),
    getPending: () => api.get('/sales/pending'),
    getStatistics: () => api.get('/sales/statistics'),
};

// Supplier API
export const supplierApi = {
    getAll: (params) => api.get('/suppliers', { params }),
    getOne: (id) => api.get(`/suppliers/${id}`),
    create: (data) => api.post('/suppliers', data),
    update: (id, data) => api.put(`/suppliers/${id}`, data),
    delete: (id) => api.delete(`/suppliers/${id}`),
    getByType: (type) => api.get(`/suppliers/by-type/${type}`),
};

// Customer API
export const customerApi = {
    getAll: (params) => api.get('/customers', { params }),
    getOne: (id) => api.get(`/customers/${id}`),
    create: (data) => api.post('/customers', data),
    update: (id, data) => api.put(`/customers/${id}`, data),
    delete: (id) => api.delete(`/customers/${id}`),
    getActive: () => api.get('/customers/active'),
    search: (query) => api.get('/customers/search', { params: { q: query } }),
};

// Truck API
export const truckApi = {
    getAll: (params) => api.get('/trucks', { params }),
    getOne: (id) => api.get(`/trucks/${id}`),
    create: (data) => api.post('/trucks', data),
    update: (id, data) => api.put(`/trucks/${id}`, data),
    delete: (id) => api.delete(`/trucks/${id}`),
    search: (query) => api.get('/trucks/search', { params: { q: query } }),
};

// TBS Price API
export const tbsPriceApi = {
    getAll: (params) => api.get('/tbs-prices', { params }),
    getOne: (id) => api.get(`/tbs-prices/${id}`),
    create: (data) => api.post('/tbs-prices', data),
    update: (id, data) => api.put(`/tbs-prices/${id}`, data),
    delete: (id) => api.delete(`/tbs-prices/${id}`),
    getToday: () => api.get('/tbs-prices/today'),
    getLatest: () => api.get('/tbs-prices/latest'),
    getHistory: () => api.get('/tbs-prices/history'),
};

// User API
export const userApi = {
    getAll: (params) => api.get('/users', { params }),
    getOne: (id) => api.get(`/users/${id}`),
    create: (data) => api.post('/users', data),
    update: (id, data) => api.put(`/users/${id}`, data),
    delete: (id) => api.delete(`/users/${id}`),
};

// Report API
export const reportApi = {
    getDaily: (params) => api.get('/reports/daily', { params }),
    getWeekly: (params) => api.get('/reports/weekly', { params }),
    getMonthly: (params) => api.get('/reports/monthly', { params }),
    getMargin: (params) => api.get('/reports/margin', { params }),
    getStockMovement: (params) => api.get('/reports/stock-movement', { params }),
    getProduction: (params) => api.get('/reports/production', { params }),
};

// Polling API (for display screens)
export const pollingApi = {
    getQueue: () => api.get('/polling/queue'),
    getWeighing: () => api.get('/polling/weighing'),
    getStock: () => api.get('/polling/stock'),
    getDashboard: () => api.get('/polling/dashboard'),
    getProduction: () => api.get('/polling/production'),
};

export default api;
