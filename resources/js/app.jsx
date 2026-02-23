import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import '../css/app.css';
import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth, AuthProvider } from './contexts/AuthContext';
import ErrorBoundary from './components/ErrorBoundary';
import Layout from './components/Layout';
import Login from './pages/auth/Login';
import Dashboard from './pages/Dashboard';
import QueueList from './pages/queue/QueueList';
import QueueForm from './pages/queue/QueueForm';
import WeighingList from './pages/weighing/WeighingList';
import WeighingProcess from './pages/weighing/WeighingProcess';
import ProductionList from './pages/production/ProductionList';
import ProductionForm from './pages/production/ProductionForm';
import StockDashboard from './pages/stock/StockDashboard';
import StockPurchase from './pages/stock/StockPurchase';
import PurchaseHistory from './pages/stock/PurchaseHistory';
import SalesList from './pages/sales/SalesList';
import SalesForm from './pages/sales/SalesForm';
import SupplierList from './pages/master/SupplierList';
import CustomerList from './pages/master/CustomerList';
import TruckList from './pages/master/TruckList';
import TbsPriceList from './pages/master/TbsPriceList';
import UserList from './pages/users/UserList';
import Reports from './pages/reports/Reports';

const PrivateRoute = ({ children }) => {
    const { user, loading } = useAuth();
    
    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
            </div>
        );
    }
    
    return user ? children : <Navigate to="/login" />;
};

const PublicRoute = ({ children }) => {
    const { user, loading } = useAuth();
    
    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
            </div>
        );
    }
    
    return user ? <Navigate to="/" /> : children;
};

export default function App() {
    return (
        <Routes>
            {/* Public Routes */}
            <Route path="/login" element={
                <PublicRoute>
                    <Login />
                </PublicRoute>
            } />
            
            {/* Protected Routes */}
            <Route path="/" element={
                <PrivateRoute>
                    <Layout />
                </PrivateRoute>
            }>
                <Route index element={<Dashboard />} />
                
                {/* Queue Management */}
                <Route path="queues" element={<QueueList />} />
                <Route path="queues/create" element={<QueueForm />} />
                <Route path="queues/:id/edit" element={<QueueForm />} />
                
                {/* Weighing */}
                <Route path="weighings" element={<WeighingList />} />
                <Route path="weighings/:id/process" element={<WeighingProcess />} />
                
                {/* Production */}
                <Route path="productions" element={<ProductionList />} />
                <Route path="productions/create" element={<ProductionForm />} />
                <Route path="productions/:id/edit" element={<ProductionForm />} />
                
                {/* Stock */}
                <Route path="stock" element={<StockDashboard />} />
                <Route path="stock/purchases" element={<StockPurchase />} />
                <Route path="stock/purchases/history" element={<PurchaseHistory />} />
                
                {/* Sales */}
                <Route path="sales" element={<SalesList />} />
                <Route path="sales/create" element={<SalesForm />} />
                <Route path="sales/:id/edit" element={<SalesForm />} />
                
                {/* Master Data */}
                <Route path="suppliers" element={<SupplierList />} />
                <Route path="customers" element={<CustomerList />} />
                <Route path="trucks" element={<TruckList />} />
                <Route path="tbs-prices" element={<TbsPriceList />} />
                
                {/* Users */}
                <Route path="users" element={<UserList />} />
                
                {/* Reports */}
                <Route path="reports" element={<Reports />} />
            </Route>
        </Routes>
    );
}

// Render the app
const container = document.getElementById('app');
if (container) {
    const root = createRoot(container);
    root.render(
        <React.StrictMode>
            <ErrorBoundary>
                <BrowserRouter>
                    <AuthProvider>
                        <App />
                    </AuthProvider>
                </BrowserRouter>
            </ErrorBoundary>
        </React.StrictMode>
    );
}
