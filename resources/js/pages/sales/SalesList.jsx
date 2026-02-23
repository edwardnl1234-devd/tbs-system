import React, { useState, useEffect } from 'react';
import {
    PlusIcon,
    PencilIcon,
    TruckIcon,
    CheckIcon,
    DocumentTextIcon,
} from '@heroicons/react/24/outline';
import { salesApi, customerApi, stockApi } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import Modal from '../../components/ui/Modal';
import Badge from '../../components/ui/Badge';
import Table, { Thead, Tbody, Tr, Th, Td } from '../../components/ui/Table';
import Pagination from '../../components/ui/Pagination';
import { PageLoading } from '../../components/ui/Loading';
import Alert from '../../components/ui/Alert';
import { formatDate, formatNumber, formatCurrency, getStatusColor, getStatusLabel } from '../../utils/helpers';

const productTypes = [
    { value: 'CPO', label: 'CPO' },
    { value: 'Kernel', label: 'Kernel' },
    { value: 'Shell', label: 'Shell' },
];

export default function SalesList() {
    const { user } = useAuth();
    const [sales, setSales] = useState([]);
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filterStatus, setFilterStatus] = useState('');
    const [filterProduct, setFilterProduct] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [pagination, setPagination] = useState({});
    
    // Modal
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingSale, setEditingSale] = useState(null);
    const [customers, setCustomers] = useState([]);
    const [formData, setFormData] = useState({
        customer_id: '',
        product_type: '',
        quantity: '',
        price_per_kg: '',
        delivery_date: '',
        notes: '',
    });
    const [formErrors, setFormErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const [alert, setAlert] = useState(null);
    const [stockError, setStockError] = useState(null);

    useEffect(() => {
        fetchSales();
        fetchStats();
    }, [currentPage, filterStatus, filterProduct]);

    const fetchSales = async () => {
        try {
            setLoading(true);
            const response = await salesApi.getAll({
                page: currentPage,
                status: filterStatus,
                product_type: filterProduct,
            });
            setSales(response.data.data || []);
            setPagination(response.data.meta || {});
        } catch (error) {
            showAlert('error', 'Gagal memuat data penjualan');
        } finally {
            setLoading(false);
        }
    };

    const fetchStats = async () => {
        try {
            const response = await salesApi.getStatistics();
            setStats(response.data.data);
        } catch (error) {
            console.error('Failed to fetch stats:', error);
        }
    };

    const fetchCustomers = async () => {
        try {
            const response = await customerApi.getActive();
            setCustomers(response.data.data);
        } catch (error) {
            console.error('Failed to fetch customers:', error);
        }
    };

    const showAlert = (type, message) => {
        setAlert({ type, message });
        setTimeout(() => setAlert(null), 5000);
    };

    const openModal = (sale = null) => {
        fetchCustomers();
        if (sale) {
            setEditingSale(sale);
            setFormData({
                customer_id: sale.customer_id || '',
                product_type: sale.product_type || '',
                quantity: sale.quantity || '',
                price_per_kg: sale.price_per_kg || '',
                order_date: sale.order_date?.split('T')[0] || new Date().toISOString().split('T')[0],
                delivery_date: sale.delivery_date?.split('T')[0] || '',
                notes: sale.notes || '',
            });
        } else {
            setEditingSale(null);
            setFormData({
                customer_id: '',
                product_type: '',
                quantity: '',
                price_per_kg: '',
                order_date: new Date().toISOString().split('T')[0], // Default to today
                delivery_date: '',
                notes: '',
            });
        }
        setFormErrors({});
        setStockError(null);
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingSale(null);
        setFormErrors({});
        setStockError(null);
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
        if (formErrors[name]) {
            setFormErrors(prev => ({ ...prev, [name]: null }));
        }
    };

    const validateForm = () => {
        const errors = {};
        if (!formData.customer_id) errors.customer_id = 'Customer wajib dipilih';
        if (!formData.product_type) errors.product_type = 'Jenis produk wajib dipilih';
        if (!formData.quantity) errors.quantity = 'Jumlah wajib diisi';
        if (!formData.price_per_kg) errors.price_per_kg = 'Harga wajib diisi';
        if (!formData.order_date) errors.order_date = 'Tanggal order wajib diisi';
        setFormErrors(errors);
        return Object.keys(errors).length === 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!validateForm()) return;

        setSubmitting(true);
        setStockError(null);
        try {
            const data = {
                ...formData,
                quantity: parseFloat(formData.quantity),
                price_per_kg: parseFloat(formData.price_per_kg),
            };

            if (editingSale) {
                await salesApi.update(editingSale.id, data);
                showAlert('success', 'Penjualan berhasil diperbarui');
            } else {
                await salesApi.create(data);
                showAlert('success', 'Penjualan berhasil ditambahkan');
            }
            closeModal();
            fetchSales();
            fetchStats();
        } catch (error) {
            const message = error.response?.data?.message || 'Terjadi kesalahan';
            // Jika error terkait stock, tampilkan di modal
            if (message.toLowerCase().includes('stock') || message.toLowerCase().includes('stok')) {
                setStockError(message);
            } else {
                showAlert('error', message);
            }
            if (error.response?.data?.errors) {
                setFormErrors(error.response.data.errors);
            }
        } finally {
            setSubmitting(false);
        }
    };

    const handleDeliver = async (sale) => {
        if (!confirm('Tandai penjualan ini sudah dikirim?')) return;

        try {
            await salesApi.deliver(sale.id);
            showAlert('success', 'Status pengiriman diperbarui');
            fetchSales();
            fetchStats();
        } catch (error) {
            showAlert('error', 'Gagal memperbarui status');
        }
    };

    const handleComplete = async (sale) => {
        if (!confirm('Selesaikan transaksi penjualan ini?')) return;

        try {
            await salesApi.complete(sale.id);
            showAlert('success', 'Penjualan selesai');
            fetchSales();
            fetchStats();
        } catch (error) {
            showAlert('error', 'Gagal menyelesaikan penjualan');
        }
    };

    const calculateTotal = () => {
        const qty = parseFloat(formData.quantity) || 0;
        const price = parseFloat(formData.price_per_kg) || 0;
        return qty * price;
    };

    return (
        <div className="space-y-6">
            {alert && (
                <Alert type={alert.type} message={alert.message} />
            )}

            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Penjualan</h1>
                    <p className="text-gray-500">Kelola penjualan CPO, Kernel, dan Shell</p>
                </div>
                {/* Add button: Admin, Accounting only */}
                {(user?.role === 'admin' || user?.role === 'accounting') && (
                    <Button onClick={() => openModal()}>
                        <PlusIcon className="h-5 w-5 mr-2" />
                        Tambah Penjualan
                    </Button>
                )}
            </div>

            {/* Stats Cards */}
            {stats && (
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Card>
                        <CardBody>
                            <div className="text-sm text-gray-500">Pending</div>
                            <div className="text-2xl font-bold text-yellow-600">
                                {stats.pending || 0}
                            </div>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody>
                            <div className="text-sm text-gray-500">Dalam Pengiriman</div>
                            <div className="text-2xl font-bold text-blue-600">
                                {stats.delivered || 0}
                            </div>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody>
                            <div className="text-sm text-gray-500">Selesai</div>
                            <div className="text-2xl font-bold text-green-600">
                                {stats.completed || 0}
                            </div>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody>
                            <div className="text-sm text-gray-500">Total Revenue</div>
                            <div className="text-2xl font-bold text-gray-900">
                                {formatCurrency(stats.total_revenue || 0)}
                            </div>
                        </CardBody>
                    </Card>
                </div>
            )}

            {/* Filters */}
            <Card>
                <CardBody>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Select
                            placeholder="Semua Status"
                            options={[
                                { value: 'pending', label: 'Pending' },
                                { value: 'delivered', label: 'Dikirim' },
                                { value: 'completed', label: 'Selesai' },
                            ]}
                            value={filterStatus}
                            onChange={(e) => {
                                setFilterStatus(e.target.value);
                                setCurrentPage(1);
                            }}
                        />
                        <Select
                            placeholder="Semua Produk"
                            options={productTypes}
                            value={filterProduct}
                            onChange={(e) => {
                                setFilterProduct(e.target.value);
                                setCurrentPage(1);
                            }}
                        />
                    </div>
                </CardBody>
            </Card>

            {/* Sales Table */}
            <Card>
                {loading ? (
                    <PageLoading />
                ) : (
                    <>
                        <Table>
                            <Thead>
                                <Tr>
                                    <Th>No. Invoice</Th>
                                    <Th>Customer</Th>
                                    <Th>Produk</Th>
                                    <Th>Jumlah</Th>
                                    <Th>Harga/kg</Th>
                                    <Th>Total</Th>
                                    <Th>Status</Th>
                                    <Th>Aksi</Th>
                                </Tr>
                            </Thead>
                            <Tbody>
                                {sales.length === 0 ? (
                                    <Tr>
                                        <Td colSpan={8} className="text-center text-gray-500 py-8">
                                            Tidak ada data penjualan
                                        </Td>
                                    </Tr>
                                ) : (
                                    sales.map((sale) => (
                                        <Tr key={sale.id}>
                                            <Td className="font-mono font-medium">
                                                {sale.invoice_number || `INV-${sale.id}`}
                                            </Td>
                                            <Td>
                                                <div className="font-medium">{sale.customer?.name}</div>
                                                <div className="text-sm text-gray-500">
                                                    {sale.customer?.company}
                                                </div>
                                            </Td>
                                            <Td>
                                                <Badge 
                                                    color={sale.product_type === 'CPO' ? 'yellow' : sale.product_type === 'Kernel' ? 'orange' : 'gray'}
                                                >
                                                    {sale.product_type?.toUpperCase()}
                                                </Badge>
                                            </Td>
                                            <Td>{formatNumber(sale.quantity)} kg</Td>
                                            <Td>{formatCurrency(sale.price_per_kg)}</Td>
                                            <Td className="font-medium">
                                                {formatCurrency(sale.total_price || (sale.quantity * sale.price_per_kg))}
                                            </Td>
                                            <Td>
                                                <Badge color={getStatusColor(sale.status)}>
                                                    {getStatusLabel(sale.status)}
                                                </Badge>
                                            </Td>
                                            <Td>
                                                <div className="flex items-center gap-2">
                                                    {sale.status === 'pending' && (
                                                        <>
                                                            {/* Edit: Admin only */}
                                                            {user?.role === 'admin' && (
                                                                <button
                                                                    onClick={() => openModal(sale)}
                                                                    className="p-1 text-blue-600 hover:bg-blue-50 rounded"
                                                                    title="Edit"
                                                                >
                                                                    <PencilIcon className="h-5 w-5" />
                                                                </button>
                                                            )}
                                                            {/* Deliver: Admin, Accounting */}
                                                            {(user?.role === 'admin' || user?.role === 'accounting') && (
                                                                <button
                                                                    onClick={() => handleDeliver(sale)}
                                                                    className="p-1 text-green-600 hover:bg-green-50 rounded"
                                                                    title="Kirim"
                                                                >
                                                                    <TruckIcon className="h-5 w-5" />
                                                                </button>
                                                            )}
                                                        </>
                                                    )}
                                                    {sale.status === 'delivered' && (
                                                        <>
                                                            {/* Edit: Admin only */}
                                                            {user?.role === 'admin' && (
                                                                <button
                                                                    onClick={() => openModal(sale)}
                                                                    className="p-1 text-blue-600 hover:bg-blue-50 rounded"
                                                                    title="Edit"
                                                                >
                                                                    <PencilIcon className="h-5 w-5" />
                                                                </button>
                                                            )}
                                                            {/* Complete: Admin, Accounting */}
                                                            {(user?.role === 'admin' || user?.role === 'accounting') && (
                                                                <button
                                                                    onClick={() => handleComplete(sale)}
                                                                    className="p-1 text-green-600 hover:bg-green-50 rounded"
                                                                    title="Selesaikan"
                                                                >
                                                                    <CheckIcon className="h-5 w-5" />
                                                                </button>
                                                            )}
                                                        </>
                                                    )}
                                                </div>
                                            </Td>
                                        </Tr>
                                    ))
                                )}
                            </Tbody>
                        </Table>
                        {pagination.last_page > 1 && (
                            <Pagination
                                currentPage={currentPage}
                                totalPages={pagination.last_page}
                                totalItems={pagination.total}
                                itemsPerPage={pagination.per_page}
                                onPageChange={setCurrentPage}
                            />
                        )}
                    </>
                )}
            </Card>

            {/* Modal Form */}
            <Modal
                isOpen={isModalOpen}
                onClose={closeModal}
                title={editingSale ? 'Edit Penjualan' : 'Tambah Penjualan'}
                size="lg"
            >
                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Stock Error Alert */}
                    {stockError && (
                        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-start gap-3">
                            <svg className="h-5 w-5 text-red-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                            </svg>
                            <div>
                                <p className="font-medium">Stock Tidak Mencukupi</p>
                                <p className="text-sm mt-1">{stockError}</p>
                            </div>
                        </div>
                    )}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Select
                            label="Customer"
                            name="customer_id"
                            value={formData.customer_id}
                            onChange={handleChange}
                            options={customers.map(c => ({ 
                                value: c.id, 
                                label: `${c.name}${c.company ? ` - ${c.company}` : ''}` 
                            }))}
                            error={formErrors.customer_id}
                            required
                        />
                        <Select
                            label="Jenis Produk"
                            name="product_type"
                            value={formData.product_type}
                            onChange={handleChange}
                            options={productTypes}
                            error={formErrors.product_type}
                            required
                        />
                        <Input
                            label="Jumlah (kg)"
                            name="quantity"
                            type="number"
                            step="0.01"
                            value={formData.quantity}
                            onChange={handleChange}
                            error={formErrors.quantity}
                            required
                        />
                        <Input
                            label="Harga per Kg (Rp)"
                            name="price_per_kg"
                            type="number"
                            value={formData.price_per_kg}
                            onChange={handleChange}
                            error={formErrors.price_per_kg}
                            required
                        />
                        <Input
                            label="Tanggal Order"
                            name="order_date"
                            type="date"
                            value={formData.order_date}
                            onChange={handleChange}
                            error={formErrors.order_date}
                            required
                        />
                        <Input
                            label="Tanggal Pengiriman"
                            name="delivery_date"
                            type="date"
                            value={formData.delivery_date}
                            onChange={handleChange}
                        />
                    </div>

                    {/* Total Preview */}
                    {formData.quantity && formData.price_per_kg && (
                        <div className="bg-green-50 p-4 rounded-lg">
                            <div className="flex justify-between items-center">
                                <span className="text-green-700">Total:</span>
                                <span className="text-2xl font-bold text-green-700">
                                    {formatCurrency(calculateTotal())}
                                </span>
                            </div>
                        </div>
                    )}

                    <Input
                        label="Catatan"
                        name="notes"
                        value={formData.notes}
                        onChange={handleChange}
                    />

                    <div className="flex justify-end gap-3 pt-4">
                        <Button variant="secondary" onClick={closeModal}>
                            Batal
                        </Button>
                        <Button type="submit" loading={submitting}>
                            {editingSale ? 'Simpan Perubahan' : 'Tambah Penjualan'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
