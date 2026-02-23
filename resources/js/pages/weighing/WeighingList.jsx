import React, { useState, useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import {
    ScaleIcon,
    PlayIcon,
    MagnifyingGlassIcon,
    TruckIcon,
    PencilIcon,
    TrashIcon,
} from '@heroicons/react/24/outline';
import { weighingApi, queueApi } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import Modal from '../../components/ui/Modal';
import Badge from '../../components/ui/Badge';
import Table, { Thead, Tbody, Tr, Th, Td } from '../../components/ui/Table';
import { PageLoading } from '../../components/ui/Loading';
import Alert from '../../components/ui/Alert';
import { formatDate, formatNumber, formatCurrency, getStatusColor, getStatusLabel, calculateNetto } from '../../utils/helpers';

export default function WeighingList() {
    const [searchParams] = useSearchParams();
    const queueId = searchParams.get('queue');
    const { user } = useAuth();

    const [weighings, setWeighings] = useState([]);
    const [pendingQueues, setPendingQueues] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filterStatus, setFilterStatus] = useState('');
    const [alert, setAlert] = useState(null);

    // Modal state
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalType, setModalType] = useState(''); // 'create', 'edit_weights'
    const [selectedWeighing, setSelectedWeighing] = useState(null);
    const [selectedQueue, setSelectedQueue] = useState(null);
    const [formData, setFormData] = useState({
        bruto_weight: '',
        tara_weight: '',
        notes: '',
        product_type: 'TBS',
    });
    
    // Netto dihitung otomatis dari bruto - tara
    const calculatedNetto = formData.bruto_weight && formData.tara_weight
        ? Math.max(0, parseFloat(formData.bruto_weight) - parseFloat(formData.tara_weight))
        : 0;
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        fetchWeighings();
        fetchPendingQueues();

        if (queueId) {
            handleCreateFromQueue(queueId);
        }
    }, [queueId]);

    const fetchWeighings = async () => {
        try {
            const response = await weighingApi.getToday();
            setWeighings(response.data.data);
        } catch (error) {
            showAlert('error', 'Gagal memuat data timbangan');
        } finally {
            setLoading(false);
        }
    };

    const fetchPendingQueues = async () => {
        try {
            const response = await queueApi.getActive();
            setPendingQueues(response.data.data.filter(q => q.status === 'processing'));
        } catch (error) {
            console.error('Failed to fetch pending queues:', error);
        }
    };

    const showAlert = (type, message) => {
        setAlert({ type, message });
        setTimeout(() => setAlert(null), 5000);
    };

    const handleCreateFromQueue = async (queueId) => {
        try {
            const response = await queueApi.getOne(queueId);
            setSelectedQueue(response.data.data);
            setModalType('create');
            setIsModalOpen(true);
        } catch (error) {
            showAlert('error', 'Gagal memuat data antrian');
        }
    };

    const openModal = (type, weighing = null, queue = null) => {
        setModalType(type);
        setSelectedWeighing(weighing);
        setSelectedQueue(queue);
        setFormData({
            bruto_weight: weighing?.bruto_weight || '',
            tara_weight: weighing?.tara_weight || '',
            notes: weighing?.notes || '',
            product_type: weighing?.product_type || 'TBS',
        });
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setSelectedWeighing(null);
        setSelectedQueue(null);
        setFormData({ bruto_weight: '', tara_weight: '', notes: '', product_type: 'TBS' });
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);

        try {
            if (modalType === 'create') {
                // Create weighing - netto dihitung otomatis dari bruto - tara
                const response = await weighingApi.create({
                    queue_id: selectedQueue.id,
                    product_type: formData.product_type, // Jenis produk sesuai pilihan
                    bruto_weight: formData.bruto_weight ? parseFloat(formData.bruto_weight) : null,
                    tara_weight: formData.tara_weight ? parseFloat(formData.tara_weight) : null,
                    // netto_weight tidak perlu dikirim, dihitung otomatis di backend
                    notes: formData.notes,
                });
                showAlert('success', 'Timbangan berhasil dibuat');
            } else if (modalType === 'edit_weights') {
                // Update berat - netto dihitung otomatis di backend
                await weighingApi.update(selectedWeighing.id, {
                    bruto_weight: formData.bruto_weight ? parseFloat(formData.bruto_weight) : null,
                    tara_weight: formData.tara_weight ? parseFloat(formData.tara_weight) : null,
                    product_type: formData.product_type,
                });
                showAlert('success', 'Berat timbangan berhasil diperbarui');
            }

            closeModal();
            fetchWeighings();
            fetchPendingQueues();
        } catch (error) {
            showAlert('error', error.response?.data?.message || 'Terjadi kesalahan');
        } finally {
            setSubmitting(false);
        }
    };

    const handleDelete = async (weighing) => {
        if (!confirm(`Hapus data timbangan "${weighing.ticket_number}"? Data yang dihapus tidak dapat dikembalikan.`)) return;

        try {
            await weighingApi.delete(weighing.id);
            showAlert('success', 'Timbangan berhasil dihapus');
            fetchWeighings();
        } catch (error) {
            showAlert('error', error.response?.data?.message || 'Gagal menghapus timbangan');
        }
    };

    const getModalTitle = () => {
        switch (modalType) {
            case 'create': return 'Buat Timbangan Baru';
            case 'edit_weights': return 'Edit Berat Timbangan';
            default: return '';
        }
    };

    const filteredWeighings = filterStatus
        ? weighings.filter(w => w.status === filterStatus)
        : weighings;

    return (
        <div className="space-y-6">
            {alert && (
                <Alert type={alert.type} message={alert.message} />
            )}

            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Timbangan</h1>
                    <p className="text-gray-500">Kelola proses penimbangan TBS</p>
                </div>
            </div>

            {/* Pending Queues for Weighing - Only Admin and Operator Timbangan can create */}
            {/* Pending Queues: Admin, Mandor, Operator Timbangan */}
            {(user?.role === 'admin' || user?.role === 'mandor' || user?.role === 'operator_timbangan') && pendingQueues.length > 0 && (
                <Card>
                    <CardHeader>
                        <h3 className="text-lg font-medium text-gray-900">
                            Antrian Siap Ditimbang ({pendingQueues.length})
                        </h3>
                    </CardHeader>
                    <CardBody>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {pendingQueues.map(queue => (
                                <div
                                    key={queue.id}
                                    className="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer"
                                    onClick={() => openModal('create', null, queue)}
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="text-xl font-bold text-green-600">
                                            {queue.queue_number}
                                        </span>
                                        <Badge color="blue">Siap Timbang</Badge>
                                    </div>
                                    <div className="mt-2 flex items-center text-gray-600">
                                        <TruckIcon className="h-4 w-4 mr-2" />
                                        {queue.truck?.plate_number}
                                    </div>
                                    <div className="text-sm text-gray-500 mt-1">
                                        {queue.supplier?.name}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardBody>
                </Card>
            )}

            {/* Filters */}
            <Card>
                <CardBody>
                    <div className="max-w-xs">
                        <Select
                            placeholder="Semua Status"
                            options={[
                                { value: 'completed', label: 'Selesai' },
                            ]}
                            value={filterStatus}
                            onChange={(e) => setFilterStatus(e.target.value)}
                        />
                    </div>
                </CardBody>
            </Card>

            {/* Weighing Table */}
            <Card>
                {loading ? (
                    <PageLoading />
                ) : (
                    <Table>
                        <Thead>
                            <Tr>
                                <Th>No. Tiket</Th>
                                <Th>Truk</Th>
                                <Th>Jenis</Th>
                                <Th>Bruto (kg)</Th>
                                <Th>Tara (kg)</Th>
                                <Th>Netto (kg)</Th>
                                <Th>Status</Th>
                                <Th>Aksi</Th>
                            </Tr>
                        </Thead>
                        <Tbody>
                            {filteredWeighings.length === 0 ? (
                                <Tr>
                                    <Td colSpan={8} className="text-center text-gray-500 py-8">
                                        Tidak ada data timbangan hari ini
                                    </Td>
                                </Tr>
                            ) : (
                                filteredWeighings.map((weighing) => (
                                    <Tr key={weighing.id}>
                                        <Td className="font-medium font-mono text-xs">
                                            {weighing.ticket_number}
                                        </Td>
                                        <Td>
                                            <div className="flex items-center text-xs">
                                                <TruckIcon className="h-4 w-4 text-gray-400 mr-1" />
                                                {weighing.queue?.truck?.plate_number}
                                            </div>
                                        </Td>
                                        <Td>
                                            <Badge color={
                                                weighing.product_type === 'TBS' ? 'green' :
                                                weighing.product_type === 'CPO' ? 'yellow' :
                                                weighing.product_type === 'Kernel' ? 'blue' :
                                                weighing.product_type === 'Cangkang' ? 'orange' :
                                                weighing.product_type === 'Fiber' ? 'purple' :
                                                'gray'
                                            }>
                                                {weighing.product_type || 'TBS'}
                                            </Badge>
                                        </Td>
                                        <Td className="text-right text-xs">
                                            {weighing.bruto_weight
                                                ? formatNumber(weighing.bruto_weight)
                                                : '-'}
                                        </Td>
                                        <Td className="text-right text-xs">
                                            {weighing.tara_weight
                                                ? formatNumber(weighing.tara_weight)
                                                : '-'}
                                        </Td>
                                        <Td className="font-medium text-right text-xs text-green-600">
                                            {weighing.netto_weight
                                                ? formatNumber(weighing.netto_weight)
                                                : calculateNetto(weighing.bruto_weight, weighing.tara_weight) > 0
                                                    ? formatNumber(calculateNetto(weighing.bruto_weight, weighing.tara_weight))
                                                    : '-'}
                                        </Td>
                                        <Td>
                                            <Badge color={getStatusColor(weighing.status)}>
                                                {getStatusLabel(weighing.status)}
                                            </Badge>
                                        </Td>
                                        <Td>
                                            <div className="flex items-center gap-1">
                                                {/* Edit main weights - Admin only */}
                                                {user?.role === 'admin' && (
                                                    <button
                                                        onClick={() => openModal('edit_weights', weighing)}
                                                        className="p-1 text-blue-600 hover:bg-blue-50 rounded"
                                                        title="Edit Berat (Bruto, Tara, Netto)"
                                                    >
                                                        <ScaleIcon className="h-4 w-4" />
                                                    </button>
                                                )}
                                                {/* Delete button - Admin only */}
                                                {user?.role === 'admin' && (
                                                    <button
                                                        onClick={() => handleDelete(weighing)}
                                                        className="p-1 text-red-600 hover:bg-red-50 rounded"
                                                        title="Hapus Timbangan"
                                                    >
                                                        <TrashIcon className="h-4 w-4" />
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

            {/* Modal */}
            <Modal
                isOpen={isModalOpen}
                onClose={closeModal}
                title={getModalTitle()}
                size={modalType === 'create' ? 'lg' : 'md'}
            >
                <form onSubmit={handleSubmit} className="space-y-4">
                    {modalType === 'create' && selectedQueue && (
                        <>
                            <div className="bg-gray-50 p-4 rounded-lg">
                                <div className="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span className="text-gray-500">No. Antrian:</span>
                                        <span className="ml-2 font-bold text-green-600">
                                            {selectedQueue.queue_number}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-gray-500">Truk:</span>
                                        <span className="ml-2 font-mono">
                                            {selectedQueue.truck?.plate_number}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-gray-500">Supplier:</span>
                                        <span className="ml-2">
                                            {selectedQueue.supplier?.name}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Pilih Jenis Produk */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Jenis Produk
                                </label>
                                <select
                                    name="product_type"
                                    value={formData.product_type}
                                    onChange={handleChange}
                                    className="block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                    required
                                >
                                    <option value="TBS">TBS (Tandan Buah Segar)</option>
                                    <option value="CPO">CPO (Crude Palm Oil)</option>
                                    <option value="Kernel">Kernel (Inti Sawit)</option>
                                    <option value="Cangkang">Cangkang (Shell)</option>
                                    <option value="Fiber">Fiber (Serat)</option>
                                    <option value="Jangkos">Jangkos (Tandan Kosong)</option>
                                </select>
                            </div>

                            {/* Input Berat Bruto & Tara */}
                            <div className="border-t pt-4">
                                <h4 className="font-medium text-gray-900 mb-3">Input Berat Timbangan</h4>
                                <div className="grid grid-cols-2 gap-3">
                                    <Input
                                        label="Berat Bruto / Kotor (kg)"
                                        name="bruto_weight"
                                        type="number"
                                        step="0.01"
                                        placeholder="Berat mobil + muatan"
                                        value={formData.bruto_weight}
                                        onChange={handleChange}
                                        required
                                    />
                                    <Input
                                        label="Berat Tara / Kosong (kg)"
                                        name="tara_weight"
                                        type="number"
                                        step="0.01"
                                        placeholder="Berat mobil kosong"
                                        value={formData.tara_weight}
                                        onChange={handleChange}
                                        required
                                    />
                                </div>
                                {/* Tampilkan Netto otomatis */}
                                {calculatedNetto > 0 && (
                                    <div className="mt-3 p-3 bg-green-50 rounded-lg text-center">
                                        <span className="text-sm text-green-600">Berat Netto (Otomatis):</span>
                                        <div className="text-2xl font-bold text-green-700">
                                            {calculatedNetto.toLocaleString('id-ID', { minimumFractionDigits: 2 })} kg
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Catatan */}
                            <div>
                                <Input
                                    label="Catatan (opsional)"
                                    name="notes"
                                    type="text"
                                    placeholder="Catatan tambahan..."
                                    value={formData.notes}
                                    onChange={handleChange}
                                />
                            </div>
                        </>
                    )}

                    {/* Edit Weights Form - Edit berat bruto/tara/netto */}
                    {modalType === 'edit_weights' && selectedWeighing && (
                        <>
                            <div className="bg-blue-50 p-4 rounded-lg mb-4">
                                <h4 className="font-medium text-blue-900 mb-2">Info Timbangan</h4>
                                <div className="grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <span className="text-blue-700">Tiket:</span>
                                        <span className="ml-2 font-mono font-bold">{selectedWeighing.ticket_number}</span>
                                    </div>
                                    <div>
                                        <span className="text-blue-700">Status:</span>
                                        <span className="ml-2">
                                            <Badge color={getStatusColor(selectedWeighing.status)}>
                                                {getStatusLabel(selectedWeighing.status)}
                                            </Badge>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div className="grid grid-cols-1 gap-4">
                                {/* Jenis Produk */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Jenis Produk
                                    </label>
                                    <select
                                        name="product_type"
                                        value={formData.product_type}
                                        onChange={handleChange}
                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                    >
                                        <option value="TBS">TBS (Tandan Buah Segar)</option>
                                        <option value="CPO">CPO (Crude Palm Oil)</option>
                                        <option value="Kernel">Kernel (Inti Sawit)</option>
                                        <option value="Cangkang">Cangkang (Shell)</option>
                                        <option value="Fiber">Fiber (Serat)</option>
                                        <option value="Jangkos">Jangkos (Tandan Kosong)</option>
                                    </select>
                                </div>
                                <Input
                                    label="Berat Bruto / Kotor (kg)"
                                    name="bruto_weight"
                                    type="number"
                                    step="0.01"
                                    placeholder="Berat mobil + muatan"
                                    value={formData.bruto_weight}
                                    onChange={handleChange}
                                />
                                <Input
                                    label="Berat Tara / Kosong (kg)"
                                    name="tara_weight"
                                    type="number"
                                    step="0.01"
                                    placeholder="Berat mobil kosong"
                                    value={formData.tara_weight}
                                    onChange={handleChange}
                                />
                            </div>
                            {/* Netto otomatis */}
                            {formData.bruto_weight && formData.tara_weight && (
                                <div className="mt-3 p-3 bg-green-50 rounded-lg text-center">
                                    <span className="text-sm text-green-600">Berat Netto (Otomatis):</span>
                                    <div className="text-xl font-bold text-green-700">
                                        {Math.max(0, parseFloat(formData.bruto_weight) - parseFloat(formData.tara_weight)).toLocaleString('id-ID', { minimumFractionDigits: 2 })} kg
                                    </div>
                                </div>
                            )}
                        </>
                    )}

                    <div className="flex justify-end gap-3 pt-4">
                        <Button variant="secondary" onClick={closeModal}>
                            Batal
                        </Button>
                        <Button type="submit" loading={submitting}>
                            {modalType === 'create' ? 'Buat Timbangan' : 'Simpan'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
