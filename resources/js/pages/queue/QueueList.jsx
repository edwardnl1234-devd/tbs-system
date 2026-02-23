import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
    PlusIcon,
    PlayIcon,
    CheckIcon,
    XMarkIcon,
    MagnifyingGlassIcon,
    ClockIcon,
    TruckIcon,
    PencilIcon,
    TrashIcon,
} from '@heroicons/react/24/outline';
import { queueApi, supplierApi, truckApi } from '../../services/api';
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
import { formatDate, getStatusColor, getStatusLabel } from '../../utils/helpers';

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'waiting', label: 'Menunggu' },
    { value: 'processing', label: 'Diproses' },
    { value: 'completed', label: 'Selesai' },
    { value: 'cancelled', label: 'Dibatalkan' },
];

export default function QueueList() {
    const { user } = useAuth();
    const [queues, setQueues] = useState([]);
    const [stats, setStats] = useState({});
    const [loading, setLoading] = useState(true);
    const [filterStatus, setFilterStatus] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [pagination, setPagination] = useState({});
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [editingQueue, setEditingQueue] = useState(null);
    
    // Form data
    const [suppliers, setSuppliers] = useState([]);
    const [trucks, setTrucks] = useState([]);
    const [formData, setFormData] = useState({
        truck_id: '',
        supplier_id: '',
        supplier_type: '',
        notes: '',
    });
    const [editFormData, setEditFormData] = useState({
        truck_id: '',
        supplier_id: '',
        supplier_type: '',
        notes: '',
    });
    const [formErrors, setFormErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const [alert, setAlert] = useState(null);

    useEffect(() => {
        fetchQueues();
        fetchStats();
    }, [currentPage, filterStatus]);

    useEffect(() => {
        const interval = setInterval(() => {
            fetchQueues();
            fetchStats();
        }, 15000); // Refresh every 15s
        return () => clearInterval(interval);
    }, [currentPage, filterStatus]);

    const fetchQueues = async () => {
        try {
            const response = await queueApi.getToday();
            let data = response.data.data;
            
            // Apply filters
            if (filterStatus) {
                data = data.filter(q => q.status === filterStatus);
            }
            
            setQueues(data);
        } catch (error) {
            showAlert('error', 'Gagal memuat data antrian');
        } finally {
            setLoading(false);
        }
    };

    const fetchStats = async () => {
        try {
            const response = await queueApi.getStatistics();
            setStats(response.data.data);
        } catch (error) {
            // Ignore stats error
        }
    };

    const fetchFormData = async () => {
        try {
            const [suppliersRes, trucksRes] = await Promise.all([
                supplierApi.getAll({ per_page: 100 }),
                truckApi.getAll({ per_page: 100 }),
            ]);
            setSuppliers(suppliersRes.data.data || []);
            setTrucks(trucksRes.data.data || []);
        } catch (error) {
            console.error('Failed to fetch form data:', error);
        }
    };

    const showAlert = (type, message) => {
        setAlert({ type, message });
        setTimeout(() => setAlert(null), 5000);
    };

    const openModal = () => {
        fetchFormData();
        setFormData({
            truck_id: '',
            supplier_id: '',
            supplier_type: '',
            notes: '',
        });
        setFormErrors({});
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setFormErrors({});
    };

    const openEditModal = (queue) => {
        fetchFormData();
        setEditingQueue(queue);
        setEditFormData({
            truck_id: queue.truck_id || '',
            supplier_id: queue.supplier_id || '',
            supplier_type: queue.supplier_type || '',
            notes: queue.notes || '',
        });
        setFormErrors({});
        setIsEditModalOpen(true);
    };

    const closeEditModal = () => {
        setIsEditModalOpen(false);
        setEditingQueue(null);
        setFormErrors({});
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
        
        // Auto-fill supplier_type when supplier is selected
        if (name === 'supplier_id' && value) {
            const supplier = suppliers.find(s => s.id === parseInt(value));
            if (supplier) {
                setFormData(prev => ({ ...prev, supplier_type: supplier.type }));
            }
        }
        
        if (formErrors[name]) {
            setFormErrors(prev => ({ ...prev, [name]: null }));
        }
    };

    const handleEditChange = (e) => {
        const { name, value } = e.target;
        setEditFormData(prev => ({ ...prev, [name]: value }));
        
        // Auto-fill supplier_type when supplier is selected
        if (name === 'supplier_id' && value) {
            const supplier = suppliers.find(s => s.id === parseInt(value));
            if (supplier) {
                setEditFormData(prev => ({ ...prev, supplier_type: supplier.type }));
            }
        }
        
        if (formErrors[name]) {
            setFormErrors(prev => ({ ...prev, [name]: null }));
        }
    };

    const validateForm = () => {
        const errors = {};
        if (!formData.truck_id) errors.truck_id = 'Truk wajib dipilih';
        if (!formData.supplier_id) errors.supplier_id = 'Supplier wajib dipilih';
        // arrival_time tidak perlu divalidasi, diset otomatis oleh server
        setFormErrors(errors);
        return Object.keys(errors).length === 0;
    };

    const validateEditForm = () => {
        const errors = {};
        if (!editFormData.truck_id) errors.truck_id = 'Truk wajib dipilih';
        if (!editFormData.supplier_id) errors.supplier_id = 'Supplier wajib dipilih';
        // arrival_time tidak perlu divalidasi pada edit
        setFormErrors(errors);
        return Object.keys(errors).length === 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!validateForm()) return;

        setSubmitting(true);
        try {
            await queueApi.create(formData);
            showAlert('success', 'Antrian berhasil ditambahkan');
            closeModal();
            fetchQueues();
            fetchStats();
        } catch (error) {
            const message = error.response?.data?.message || 'Terjadi kesalahan';
            showAlert('error', message);
            if (error.response?.data?.errors) {
                setFormErrors(error.response.data.errors);
            }
        } finally {
            setSubmitting(false);
        }
    };

    const handleEditSubmit = async (e) => {
        e.preventDefault();
        if (!validateEditForm()) return;

        setSubmitting(true);
        try {
            await queueApi.update(editingQueue.id, editFormData);
            showAlert('success', 'Antrian berhasil diperbarui');
            closeEditModal();
            fetchQueues();
            fetchStats();
        } catch (error) {
            const message = error.response?.data?.message || 'Terjadi kesalahan';
            showAlert('error', message);
            if (error.response?.data?.errors) {
                setFormErrors(error.response.data.errors);
            }
        } finally {
            setSubmitting(false);
        }
    };

    const handleStatusChange = async (queue, newStatus) => {
        const confirmMessages = {
            processing: `Proses antrian ${queue.queue_number}?`,
            completed: `Selesaikan antrian ${queue.queue_number}?`,
            cancelled: `Batalkan antrian ${queue.queue_number}?`,
        };

        if (!confirm(confirmMessages[newStatus])) return;

        try {
            await queueApi.updateStatus(queue.id, newStatus);
            showAlert('success', `Status antrian berhasil diubah`);
            fetchQueues();
            fetchStats();
        } catch (error) {
            showAlert('error', 'Gagal mengubah status antrian');
        }
    };

    const handleDelete = async (queue) => {
        if (!confirm(`Hapus antrian "${queue.queue_number}"? Data yang dihapus tidak dapat dikembalikan.`)) return;

        try {
            await queueApi.delete(queue.id);
            showAlert('success', 'Antrian berhasil dihapus');
            fetchQueues();
            fetchStats();
        } catch (error) {
            showAlert('error', error.response?.data?.message || 'Gagal menghapus antrian');
        }
    };

    return (
        <div className="space-y-6">
            {alert && (
                <Alert type={alert.type} message={alert.message} />
            )}

            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Antrian TBS</h1>
                    <p className="text-gray-500">Kelola antrian truk TBS hari ini</p>
                </div>
                {/* Add button: Admin, Mandor, and Operator Timbangan */}
                {(user?.role === 'admin' || user?.role === 'mandor' || user?.role === 'operator_timbangan') && (
                    <Button onClick={openModal}>
                        <PlusIcon className="h-5 w-5 mr-2" />
                        Tambah Antrian
                    </Button>
                )}
            </div>

            {/* Stats Cards */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Card>
                    <CardBody className="text-center">
                        <div className="text-3xl font-bold text-yellow-600">
                            {stats.waiting || 0}
                        </div>
                        <div className="text-sm text-gray-500">Menunggu</div>
                    </CardBody>
                </Card>
                <Card>
                    <CardBody className="text-center">
                        <div className="text-3xl font-bold text-blue-600">
                            {stats.processing || 0}
                        </div>
                        <div className="text-sm text-gray-500">Diproses</div>
                    </CardBody>
                </Card>
                <Card>
                    <CardBody className="text-center">
                        <div className="text-3xl font-bold text-green-600">
                            {stats.completed || 0}
                        </div>
                        <div className="text-sm text-gray-500">Selesai</div>
                    </CardBody>
                </Card>
                <Card>
                    <CardBody className="text-center">
                        <div className="text-3xl font-bold text-gray-600">
                            {stats.total || 0}
                        </div>
                        <div className="text-sm text-gray-500">Total</div>
                    </CardBody>
                </Card>
            </div>

            {/* Filters */}
            <Card>
                <CardBody>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Select
                            placeholder="Semua Status"
                            options={statusOptions.slice(1)}
                            value={filterStatus}
                            onChange={(e) => {
                                setFilterStatus(e.target.value);
                                setCurrentPage(1);
                            }}
                        />
                    </div>
                </CardBody>
            </Card>

            {/* Queue Table */}
            <Card>
                {loading ? (
                    <PageLoading />
                ) : (
                    <Table>
                        <Thead>
                            <Tr>
                                <Th>No. Antrian</Th>
                                <Th>Truk</Th>
                                <Th>Supplier</Th>
                                <Th>Waktu Masuk</Th>
                                <Th>Status</Th>
                                <Th>Aksi</Th>
                            </Tr>
                        </Thead>
                        <Tbody>
                            {queues.length === 0 ? (
                                <Tr>
                                    <Td colSpan={6} className="text-center text-gray-500 py-8">
                                        Tidak ada antrian hari ini
                                    </Td>
                                </Tr>
                            ) : (
                                queues.map((queue) => (
                                    <Tr key={queue.id}>
                                        <Td>
                                            <span className="text-lg font-bold text-green-600">
                                                {queue.queue_number}
                                            </span>
                                        </Td>
                                        <Td>
                                            <div className="flex items-center">
                                                <TruckIcon className="h-5 w-5 text-gray-400 mr-2" />
                                                <span className="font-mono font-medium">
                                                    {queue.truck?.plate_number}
                                                </span>
                                            </div>
                                        </Td>
                                        <Td>
                                            <div>
                                                <div className="font-medium">{queue.supplier?.name}</div>
                                                <Badge 
                                                    color={queue.supplier_type === 'inti' ? 'green' : queue.supplier_type === 'plasma' ? 'blue' : 'yellow'}
                                                    className="mt-1"
                                                >
                                                    {queue.supplier_type?.toUpperCase()}
                                                </Badge>
                                            </div>
                                        </Td>
                                        <Td>
                                            <div className="flex items-center text-gray-500">
                                                <ClockIcon className="h-4 w-4 mr-1" />
                                                {formatDate(queue.arrival_time, 'time')}
                                            </div>
                                        </Td>
                                        <Td>
                                            <Badge color={getStatusColor(queue.status)}>
                                                {getStatusLabel(queue.status)}
                                            </Badge>
                                        </Td>
                                        <Td>
                                            <div className="flex items-center gap-2">
                                                {/* Edit button: Admin only */}
                                                {user?.role === 'admin' && (queue.status === 'waiting' || queue.status === 'processing') && (
                                                    <button
                                                        onClick={() => openEditModal(queue)}
                                                        className="p-1 text-gray-600 hover:bg-gray-100 rounded"
                                                        title="Edit"
                                                    >
                                                        <PencilIcon className="h-5 w-5" />
                                                    </button>
                                                )}
                                                {/* Process button: Admin and Operator Timbangan */}
                                                {(user?.role === 'admin' || user?.role === 'mandor' || user?.role === 'operator_timbangan') && queue.status === 'waiting' && (
                                                    <button
                                                        onClick={() => handleStatusChange(queue, 'processing')}
                                                        className="p-1 text-blue-600 hover:bg-blue-50 rounded"
                                                        title="Proses"
                                                    >
                                                        <PlayIcon className="h-5 w-5" />
                                                    </button>
                                                )}
                                                {queue.status === 'processing' && (
                                                    <Link
                                                        to={`/weighings?queue=${queue.id}`}
                                                        className="p-1 text-green-600 hover:bg-green-50 rounded"
                                                        title="Timbang"
                                                    >
                                                        <CheckIcon className="h-5 w-5" />
                                                    </Link>
                                                )}
                                                {/* Cancel button: Admin and Operator Timbangan */}
                                                {(user?.role === 'admin' || user?.role === 'mandor' || user?.role === 'operator_timbangan') && (queue.status === 'waiting' || queue.status === 'processing') && (
                                                    <button
                                                        onClick={() => handleStatusChange(queue, 'cancelled')}
                                                        className="p-1 text-red-600 hover:bg-red-50 rounded"
                                                        title="Batalkan"
                                                    >
                                                        <XMarkIcon className="h-5 w-5" />
                                                    </button>
                                                )}
                                                {/* Delete button - Admin only */}
                                                {user?.role === 'admin' && (
                                                    <button
                                                        onClick={() => handleDelete(queue)}
                                                        className="p-1 text-red-600 hover:bg-red-50 rounded"
                                                        title="Hapus Antrian"
                                                    >
                                                        <TrashIcon className="h-5 w-5" />
                                                    </button>
                                                )}
                                            </div>
                                        </Td>
                                    </Tr>
                                ))
                            )}
                        </Tbody>
                    </Table>
                )}
            </Card>

            {/* Modal Form */}
            <Modal
                isOpen={isModalOpen}
                onClose={closeModal}
                title="Tambah Antrian Baru"
                size="lg"
            >
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Select
                            label="Truk"
                            name="truck_id"
                            value={formData.truck_id}
                            onChange={handleChange}
                            options={trucks.map(t => ({ value: t.id, label: `${t.plate_number} - ${t.driver_name || 'N/A'}` }))}
                            error={formErrors.truck_id}
                            required
                        />
                        <Select
                            label="Supplier"
                            name="supplier_id"
                            value={formData.supplier_id}
                            onChange={handleChange}
                            options={suppliers.map(s => ({ value: s.id, label: `${s.name} (${s.type})` }))}
                            error={formErrors.supplier_id}
                            required
                        />
                    </div>
                    <div className="bg-blue-50 p-3 rounded-lg text-center">
                        <span className="text-sm text-blue-600">Waktu masuk akan dicatat otomatis oleh sistem</span>
                    </div>
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
                            Tambah Antrian
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Edit Modal */}
            <Modal
                isOpen={isEditModalOpen}
                onClose={closeEditModal}
                title={`Edit Antrian ${editingQueue?.queue_number || ''}`}
                size="lg"
            >
                <form onSubmit={handleEditSubmit} className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Select
                            label="Truk"
                            name="truck_id"
                            value={editFormData.truck_id}
                            onChange={handleEditChange}
                            options={trucks.map(t => ({ value: t.id, label: `${t.plate_number} - ${t.driver_name || 'N/A'}` }))}
                            error={formErrors.truck_id}
                            required
                        />
                        <Select
                            label="Supplier"
                            name="supplier_id"
                            value={editFormData.supplier_id}
                            onChange={handleEditChange}
                            options={suppliers.map(s => ({ value: s.id, label: `${s.name} (${s.type})` }))}
                            error={formErrors.supplier_id}
                            required
                        />
                    </div>
                    <Input
                        label="Catatan"
                        name="notes"
                        value={editFormData.notes}
                        onChange={handleEditChange}
                    />
                    <div className="flex justify-end gap-3 pt-4">
                        <Button variant="secondary" onClick={closeEditModal}>
                            Batal
                        </Button>
                        <Button type="submit" loading={submitting}>
                            Simpan Perubahan
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
