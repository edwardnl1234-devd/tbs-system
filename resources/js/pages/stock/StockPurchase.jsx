import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { TrashIcon } from '@heroicons/react/24/outline';
import { stockPurchaseApi, supplierApi } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import Badge from '../../components/ui/Badge';
import Table, { Thead, Tbody, Tr, Th, Td } from '../../components/ui/Table';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import Modal, { ModalBody, ModalFooter } from '../../components/ui/Modal';
import { PageLoading } from '../../components/ui/Loading';
import Alert from '../../components/ui/Alert';
import { formatNumber, formatDate, formatCurrency } from '../../utils/helpers';

export default function StockPurchase() {
    const { user } = useAuth();
    const [activeTab, setActiveTab] = useState('cpo');
    const [purchases, setPurchases] = useState({ cpo: [], kernel: [], shell: [] });
    const [summary, setSummary] = useState(null);
    const [suppliers, setSuppliers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [alert, setAlert] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [formData, setFormData] = useState({
        supplier_id: '',
        quantity: '',
        purchase_price: '',
        quality_grade: 'standard',
        tank_number: '',
        location: '',
        stock_date: new Date().toISOString().split('T')[0],
        notes: '',
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const [purchasesRes, summaryRes, suppliersRes] = await Promise.all([
                stockPurchaseApi.getAll(),
                stockPurchaseApi.getSummary(),
                stockPurchaseApi.getSuppliers(),
            ]);

            setPurchases(purchasesRes.data.data);
            setSummary(summaryRes.data.data);
            setSuppliers(suppliersRes.data.data || []);
        } catch (error) {
            setAlert({ type: 'error', message: 'Gagal memuat data pembelian stok' });
        } finally {
            setLoading(false);
        }
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);

        try {
            let response;
            const data = {
                ...formData,
                quantity: parseFloat(formData.quantity),
                purchase_price: parseFloat(formData.purchase_price),
            };

            switch (activeTab) {
                case 'cpo':
                    response = await stockPurchaseApi.purchaseCpo(data);
                    break;
                case 'kernel':
                    response = await stockPurchaseApi.purchaseKernel(data);
                    break;
                case 'shell':
                    response = await stockPurchaseApi.purchaseShell(data);
                    break;
            }

            setAlert({ type: 'success', message: response.data.message || 'Pembelian berhasil dicatat' });
            setShowModal(false);
            resetForm();
            fetchData();
        } catch (error) {
            const message = error.response?.data?.message || 'Gagal mencatat pembelian';
            setAlert({ type: 'error', message });
        } finally {
            setSubmitting(false);
        }
    };

    const handleStatusChange = async (id, newStatus) => {
        try {
            let response;
            switch (activeTab) {
                case 'cpo':
                    response = await stockPurchaseApi.updateCpoStatus(id, newStatus);
                    break;
                case 'kernel':
                    response = await stockPurchaseApi.updateKernelStatus(id, newStatus);
                    break;
                case 'shell':
                    response = await stockPurchaseApi.updateShellStatus(id, newStatus);
                    break;
            }
            
            setAlert({ type: 'success', message: response.data.message || 'Status berhasil diupdate' });
            
            // Update local state without refetching
            setPurchases(prev => ({
                ...prev,
                [activeTab]: prev[activeTab].map(item => 
                    item.id === id ? { ...item, purchase_status: newStatus } : item
                )
            }));
        } catch (error) {
            const message = error.response?.data?.message || 'Gagal mengupdate status';
            setAlert({ type: 'error', message });
        }
    };

    const handleDelete = async (item) => {
        const productName = activeTab === 'cpo' ? 'CPO' : activeTab === 'kernel' ? 'Kernel' : 'Shell';
        if (!confirm(`Hapus data pembelian ${productName} dari ${item.supplier?.name || 'Unknown'}? Data yang dihapus tidak dapat dikembalikan.`)) return;

        try {
            let response;
            switch (activeTab) {
                case 'cpo':
                    response = await stockPurchaseApi.deleteCpo(item.id);
                    break;
                case 'kernel':
                    response = await stockPurchaseApi.deleteKernel(item.id);
                    break;
                case 'shell':
                    response = await stockPurchaseApi.deleteShell(item.id);
                    break;
            }
            
            setAlert({ type: 'success', message: response.data.message || 'Data berhasil dihapus' });
            
            // Update local state
            setPurchases(prev => ({
                ...prev,
                [activeTab]: prev[activeTab].filter(i => i.id !== item.id)
            }));
        } catch (error) {
            const message = error.response?.data?.message || 'Gagal menghapus data';
            setAlert({ type: 'error', message });
        }
    };

    const resetForm = () => {
        setFormData({
            supplier_id: '',
            quantity: '',
            purchase_price: '',
            quality_grade: 'standard',
            tank_number: '',
            location: '',
            stock_date: new Date().toISOString().split('T')[0],
            notes: '',
        });
    };

    const tabs = [
        { id: 'cpo', label: 'CPO', color: 'yellow' },
        { id: 'kernel', label: 'Kernel', color: 'orange' },
        { id: 'shell', label: 'Shell', color: 'gray' },
    ];

    const qualityOptions = [
        { value: 'premium', label: 'Premium' },
        { value: 'standard', label: 'Standard' },
        { value: 'low', label: 'Low' },
    ];

    const supplierOptions = suppliers.map(s => ({
        value: s.id,
        label: `${s.name} (${s.code})`,
    }));

    const currentPurchases = purchases[activeTab] || [];

    if (loading) return <PageLoading />;

    return (
        <div className="space-y-6">
            {alert && (
                <Alert 
                    type={alert.type} 
                    message={alert.message} 
                    onClose={() => setAlert(null)}
                />
            )}

            {/* Header */}
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Pembelian Stok</h1>
                    <p className="text-gray-500">Catat pembelian stok dari supplier</p>
                </div>
                <div className="flex gap-2">
                    <Link to="/stock/purchases/history">
                        <Button variant="secondary">
                            📋 Riwayat Pembukuan
                        </Button>
                    </Link>
                    {/* Add button: Admin, Accounting only */}
                    {(user?.role === 'admin' || user?.role === 'accounting') && (
                        <Button onClick={() => setShowModal(true)}>
                            + Tambah Pembelian
                        </Button>
                    )}
                </div>
            </div>

            {/* Summary Cards */}
            {summary && (
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card>
                        <CardBody className="text-center">
                            <div className="text-3xl font-bold text-yellow-600">
                                {formatNumber(summary.cpo?.total_quantity || 0)} kg
                            </div>
                            <div className="text-sm text-gray-500 mt-1">Total CPO Dibeli</div>
                            <div className="text-xs text-gray-400 mt-1">
                                {summary.cpo?.count || 0} transaksi
                            </div>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody className="text-center">
                            <div className="text-3xl font-bold text-orange-600">
                                {formatNumber(summary.kernel?.total_quantity || 0)} kg
                            </div>
                            <div className="text-sm text-gray-500 mt-1">Total Kernel Dibeli</div>
                            <div className="text-xs text-gray-400 mt-1">
                                {summary.kernel?.count || 0} transaksi
                            </div>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody className="text-center">
                            <div className="text-3xl font-bold text-gray-600">
                                {formatNumber(summary.shell?.total_quantity || 0)} kg
                            </div>
                            <div className="text-sm text-gray-500 mt-1">Total Shell Dibeli</div>
                            <div className="text-xs text-gray-400 mt-1">
                                {summary.shell?.count || 0} transaksi
                            </div>
                        </CardBody>
                    </Card>
                </div>
            )}

            {/* Tabs */}
            <div className="border-b border-gray-200">
                <nav className="-mb-px flex space-x-8">
                    {tabs.map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id)}
                            className={`
                                whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                                ${activeTab === tab.id
                                    ? 'border-green-500 text-green-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                }
                            `}
                        >
                            {tab.label}
                            <span className="ml-2 bg-gray-100 px-2 py-0.5 rounded-full text-xs">
                                {(purchases[tab.id] || []).length}
                            </span>
                        </button>
                    ))}
                </nav>
            </div>

            {/* Table */}
            <Card>
                <CardBody>
                    {currentPurchases.length === 0 ? (
                        <div className="text-center py-8 text-gray-500">
                            Belum ada data pembelian {activeTab.toUpperCase()}
                        </div>
                    ) : (
                        <Table>
                            <Thead>
                                <Tr>
                                    <Th>Tanggal</Th>
                                    <Th>Supplier</Th>
                                    <Th>Quantity</Th>
                                    <Th>Harga/kg</Th>
                                    <Th>Total</Th>
                                    {activeTab === 'cpo' && <Th>Grade</Th>}
                                    {activeTab === 'cpo' && <Th>Tank</Th>}
                                    <Th>Status Pembelian</Th>
                                    {user?.role === 'admin' && <Th>Aksi</Th>}
                                </Tr>
                            </Thead>
                            <Tbody>
                                {currentPurchases.map((item) => (
                                    <Tr key={item.id}>
                                        <Td>{formatDate(item.stock_date)}</Td>
                                        <Td>
                                            <div className="font-medium">{item.supplier?.name || '-'}</div>
                                            <div className="text-xs text-gray-500">{item.reference_number}</div>
                                        </Td>
                                        <Td>{formatNumber(item.quantity)} kg</Td>
                                        <Td>{formatCurrency(item.purchase_price || 0)}</Td>
                                        <Td className="font-medium">
                                            {formatCurrency((item.quantity || 0) * (item.purchase_price || 0))}
                                        </Td>
                                        {activeTab === 'cpo' && (
                                            <Td>
                                                <Badge variant={item.quality_grade === 'premium' ? 'success' : item.quality_grade === 'standard' ? 'info' : 'warning'}>
                                                    {item.quality_grade || '-'}
                                                </Badge>
                                            </Td>
                                        )}
                                        {activeTab === 'cpo' && <Td>{item.tank_number || '-'}</Td>}
                                        <Td>
                                            {item.purchase_status === 'done' ? (
                                                <Badge variant="success">Done</Badge>
                                            ) : (
                                                <select
                                                    value={item.purchase_status || 'pending'}
                                                    onChange={(e) => handleStatusChange(item.id, e.target.value)}
                                                    className={`text-sm rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500
                                                        ${item.purchase_status === 'in_process' ? 'bg-yellow-50 text-yellow-700' : 
                                                          'bg-gray-50 text-gray-700'}`}
                                                >
                                                    {item.purchase_status === 'pending' && (
                                                        <>
                                                            <option value="pending">Pending</option>
                                                            <option value="in_process">In Process</option>
                                                        </>
                                                    )}
                                                    {item.purchase_status === 'in_process' && (
                                                        <>
                                                            <option value="in_process">In Process</option>
                                                            <option value="done">Done</option>
                                                        </>
                                                    )}
                                                    {!item.purchase_status && (
                                                        <>
                                                            <option value="pending">Pending</option>
                                                            <option value="in_process">In Process</option>
                                                        </>
                                                    )}
                                                </select>
                                            )}
                                        </Td>
                                        {user?.role === 'admin' && (
                                            <Td>
                                                <button
                                                    onClick={() => handleDelete(item)}
                                                    className="p-1 text-red-600 hover:bg-red-50 rounded"
                                                    title="Hapus"
                                                >
                                                    <TrashIcon className="h-5 w-5" />
                                                </button>
                                            </Td>
                                        )}
                                    </Tr>
                                ))}
                            </Tbody>
                        </Table>
                    )}
                </CardBody>
            </Card>

            {/* Add Purchase Modal */}
            <Modal
                isOpen={showModal}
                onClose={() => setShowModal(false)}
                title="Tambah Pembelian Stok"
            >
                <form onSubmit={handleSubmit}>
                    <ModalBody>
                        <div className="space-y-4">
                            {/* Jenis Stok Selection */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Jenis Stok <span className="text-red-500">*</span>
                                </label>
                                <div className="grid grid-cols-3 gap-2">
                                    {tabs.map((tab) => (
                                        <button
                                            key={tab.id}
                                            type="button"
                                            onClick={() => setActiveTab(tab.id)}
                                            className={`py-3 px-4 rounded-lg border-2 font-medium text-sm transition-all
                                                ${activeTab === tab.id
                                                    ? tab.id === 'cpo' 
                                                        ? 'border-yellow-500 bg-yellow-50 text-yellow-700'
                                                        : tab.id === 'kernel'
                                                            ? 'border-orange-500 bg-orange-50 text-orange-700'
                                                            : 'border-gray-500 bg-gray-50 text-gray-700'
                                                    : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'
                                                }
                                            `}
                                        >
                                            {tab.label}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            
                            <Select
                                label="Supplier"
                                name="supplier_id"
                                value={formData.supplier_id}
                                onChange={handleChange}
                                options={supplierOptions}
                                required
                            />
                            <div className="grid grid-cols-2 gap-4">
                                <Input
                                    label="Quantity (kg)"
                                    type="number"
                                    name="quantity"
                                    value={formData.quantity}
                                    onChange={handleChange}
                                    min="0.01"
                                    step="0.01"
                                    required
                                />
                                <Input
                                    label="Harga per kg (Rp)"
                                    type="number"
                                    name="purchase_price"
                                    value={formData.purchase_price}
                                    onChange={handleChange}
                                    min="0"
                                    required
                                />
                            </div>
                            {activeTab === 'cpo' && (
                                <div className="grid grid-cols-2 gap-4">
                                    <Select
                                        label="Grade Kualitas"
                                        name="quality_grade"
                                        value={formData.quality_grade}
                                        onChange={handleChange}
                                        options={qualityOptions}
                                    />
                                    <Input
                                        label="Nomor Tank"
                                        name="tank_number"
                                        value={formData.tank_number}
                                        onChange={handleChange}
                                        placeholder="contoh: TANK-01"
                                    />
                                </div>
                            )}
                            {(activeTab === 'kernel' || activeTab === 'shell') && (
                                <Input
                                    label="Lokasi Penyimpanan"
                                    name="location"
                                    value={formData.location}
                                    onChange={handleChange}
                                    placeholder="contoh: Gudang A"
                                />
                            )}
                            <Input
                                label="Tanggal Pembelian"
                                type="date"
                                name="stock_date"
                                value={formData.stock_date}
                                onChange={handleChange}
                                required
                            />
                            <Input
                                label="Catatan"
                                name="notes"
                                value={formData.notes}
                                onChange={handleChange}
                                placeholder="Catatan tambahan (opsional)"
                            />

                            {/* Preview Total */}
                            {formData.quantity && formData.purchase_price && (
                                <div className="bg-green-50 p-4 rounded-lg">
                                    <div className="text-sm text-gray-600">Total Pembelian:</div>
                                    <div className="text-2xl font-bold text-green-700">
                                        {formatCurrency(parseFloat(formData.quantity) * parseFloat(formData.purchase_price))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </ModalBody>
                    <ModalFooter>
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setShowModal(false)}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={submitting}>
                            {submitting ? 'Menyimpan...' : 'Simpan Pembelian'}
                        </Button>
                    </ModalFooter>
                </form>
            </Modal>
        </div>
    );
}
