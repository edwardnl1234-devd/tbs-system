import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
    PlusIcon,
    PencilIcon,
    EyeIcon,
    CalendarIcon,
} from '@heroicons/react/24/outline';
import { productionApi } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Modal from '../../components/ui/Modal';
import Badge from '../../components/ui/Badge';
import Table, { Thead, Tbody, Tr, Th, Td } from '../../components/ui/Table';
import Pagination from '../../components/ui/Pagination';
import { PageLoading } from '../../components/ui/Loading';
import Alert from '../../components/ui/Alert';
import { formatDate, formatNumber } from '../../utils/helpers';

export default function ProductionList() {
    const { user } = useAuth();
    const [productions, setProductions] = useState([]);
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const [currentPage, setCurrentPage] = useState(1);
    const [pagination, setPagination] = useState({});
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingProduction, setEditingProduction] = useState(null);
    const [formData, setFormData] = useState({
        production_date: new Date().toISOString().split('T')[0],
        batch_number: '',
        tbs_input_weight: '',
        cpo_output: '',
        kernel_output: '',
        shell_output: '',
        notes: '',
    });
    const [formErrors, setFormErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const [alert, setAlert] = useState(null);
    const [filterPeriod, setFilterPeriod] = useState(''); // '', 'today', 'week', 'month'

    useEffect(() => {
        fetchProductions();
        fetchStats();
    }, [currentPage, filterPeriod]);

    const fetchProductions = async () => {
        try {
            setLoading(true);
            const params = { page: currentPage };
            if (filterPeriod) {
                params.period = filterPeriod;
            }
            const response = await productionApi.getAll(params);
            setProductions(response.data.data || []);
            setPagination(response.data.meta || {});
        } catch (error) {
            showAlert('error', 'Gagal memuat data produksi');
        } finally {
            setLoading(false);
        }
    };

    const fetchStats = async () => {
        try {
            const params = {};
            if (filterPeriod) {
                params.period = filterPeriod;
            }
            const response = await productionApi.getStatistics(params);
            setStats(response.data.data);
        } catch (error) {
            console.error('Failed to fetch stats:', error);
        }
    };

    const showAlert = (type, message) => {
        setAlert({ type, message });
        setTimeout(() => setAlert(null), 5000);
    };

    const openModal = (production = null) => {
        if (production) {
            setEditingProduction(production);
            setFormData({
                production_date: production.production_date?.split('T')[0] || '',
                batch_number: production.batch_number || '',
                tbs_input_weight: production.tbs_input_weight || '',
                cpo_output: production.cpo_output || '',
                kernel_output: production.kernel_output || '',
                shell_output: production.shell_output || '',
                notes: production.notes || '',
            });
        } else {
            setEditingProduction(null);
            const now = new Date();
            const batchNum = `BATCH-${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}-${String(now.getHours()).padStart(2, '0')}${String(now.getMinutes()).padStart(2, '0')}`;
            setFormData({
                production_date: now.toISOString().split('T')[0],
                batch_number: batchNum,
                tbs_input_weight: '',
                cpo_output: '',
                kernel_output: '',
                shell_output: '',
                notes: '',
            });
        }
        setFormErrors({});
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingProduction(null);
        setFormErrors({});
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
        if (!formData.production_date) errors.production_date = 'Tanggal wajib diisi';
        if (!formData.tbs_input_weight) errors.tbs_input_weight = 'Input TBS wajib diisi';
        setFormErrors(errors);
        return Object.keys(errors).length === 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!validateForm()) return;

        setSubmitting(true);
        try {
            const data = {
                ...formData,
                tbs_input_weight: parseFloat(formData.tbs_input_weight),
                cpo_output: parseFloat(formData.cpo_output) || 0,
                kernel_output: parseFloat(formData.kernel_output) || 0,
                shell_output: parseFloat(formData.shell_output) || 0,
            };

            if (editingProduction) {
                await productionApi.update(editingProduction.id, data);
                showAlert('success', 'Produksi berhasil diperbarui');
            } else {
                await productionApi.create(data);
                showAlert('success', 'Produksi berhasil ditambahkan');
            }
            closeModal();
            fetchProductions();
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

    const calculateOER = (cpo, tbs) => {
        if (!tbs || tbs === 0) return 0;
        return ((cpo / tbs) * 100).toFixed(2);
    };

    const calculateKER = (kernel, tbs) => {
        if (!tbs || tbs === 0) return 0;
        return ((kernel / tbs) * 100).toFixed(2);
    };

    return (
        <div className="space-y-6">
            {alert && (
                <Alert type={alert.type} message={alert.message} />
            )}

            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Produksi</h1>
                    <p className="text-gray-500">Kelola data produksi CPO, Kernel, dan Shell</p>
                </div>
                {/* Add button: Admin, Manager, Operator Timbangan */}
                {/* Add button: Admin, Mandor, Operator Timbangan */}
                {(user?.role === 'admin' || user?.role === 'mandor' || user?.role === 'operator_timbangan') && (
                    <Button onClick={() => openModal()}>
                        <PlusIcon className="h-5 w-5 mr-2" />
                        Tambah Produksi
                    </Button>
                )}
            </div>

            {/* Stats Cards */}
            {stats && (
                <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <Card>
                        <CardBody>
                            <div className="text-sm text-gray-500">Total Input TBS</div>
                            <div className="text-2xl font-bold text-gray-900">
                                {formatNumber(stats.total_input || 0)} kg
                            </div>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody>
                            <div className="text-sm text-gray-500">Output CPO</div>
                            <div className="text-2xl font-bold text-yellow-600">
                                {formatNumber(stats.total_cpo || 0)} kg
                            </div>
                            <div className="text-sm text-gray-500">
                                OER: {parseFloat(stats.avg_oer || 0).toFixed(2)}%
                            </div>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody>
                            <div className="text-sm text-gray-500">Output Kernel</div>
                            <div className="text-2xl font-bold text-orange-600">
                                {formatNumber(stats.total_kernel || 0)} kg
                            </div>
                            <div className="text-sm text-gray-500">
                                KER: {parseFloat(stats.avg_ker || 0).toFixed(2)}%
                            </div>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody>
                            <div className="text-sm text-gray-500">Output Shell</div>
                            <div className="text-2xl font-bold text-gray-600">
                                {formatNumber(stats.total_shell || 0)} kg
                            </div>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody>
                            <div className="text-sm text-gray-500">Total Batch</div>
                            <div className="text-2xl font-bold text-blue-600">
                                {stats.total_batches || 0}
                            </div>
                        </CardBody>
                    </Card>
                </div>
            )}

            {/* Filter Section */}
            <Card>
                <CardBody>
                    <div className="flex flex-wrap items-center gap-4">
                        <span className="text-sm font-medium text-gray-700">Filter Tabel:</span>
                        <div className="flex gap-2">
                            <button
                                onClick={() => { setFilterPeriod(''); setCurrentPage(1); }}
                                className={`px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${
                                    filterPeriod === '' 
                                        ? 'bg-gray-800 text-white' 
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                }`}
                            >
                                Semua
                            </button>
                            <button
                                onClick={() => { setFilterPeriod('today'); setCurrentPage(1); }}
                                className={`px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${
                                    filterPeriod === 'today' 
                                        ? 'bg-green-600 text-white' 
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                }`}
                            >
                                Hari Ini
                            </button>
                            <button
                                onClick={() => { setFilterPeriod('week'); setCurrentPage(1); }}
                                className={`px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${
                                    filterPeriod === 'week' 
                                        ? 'bg-blue-600 text-white' 
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                }`}
                            >
                                Minggu Ini
                            </button>
                            <button
                                onClick={() => { setFilterPeriod('month'); setCurrentPage(1); }}
                                className={`px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${
                                    filterPeriod === 'month' 
                                        ? 'bg-purple-600 text-white' 
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                }`}
                            >
                                Bulan Ini
                            </button>
                        </div>
                    </div>
                </CardBody>
            </Card>

            {/* Table */}
            <Card>
                {loading ? (
                    <PageLoading />
                ) : (
                    <>
                        <Table>
                            <Thead>
                                <Tr>
                                    <Th>Tanggal</Th>
                                    <Th>Batch</Th>
                                    <Th>Input TBS</Th>
                                    <Th>CPO</Th>
                                    <Th>Kernel</Th>
                                    <Th>Shell</Th>
                                    <Th>OER</Th>
                                    <Th>KER</Th>
                                    <Th>Aksi</Th>
                                </Tr>
                            </Thead>
                            <Tbody>
                                {productions.length === 0 ? (
                                    <Tr>
                                        <Td colSpan={9} className="text-center text-gray-500 py-8">
                                            Tidak ada data produksi
                                        </Td>
                                    </Tr>
                                ) : (
                                    productions.map((production) => (
                                        <Tr key={production.id}>
                                            <Td>
                                                <div className="flex items-center">
                                                    <CalendarIcon className="h-4 w-4 text-gray-400 mr-2" />
                                                    {formatDate(production.production_date)}
                                                </div>
                                            </Td>
                                            <Td className="font-mono text-sm">
                                                {production.batch_number}
                                            </Td>
                                            <Td className="font-medium">
                                                {formatNumber(production.tbs_input_weight)} kg
                                            </Td>
                                            <Td className="text-yellow-600">
                                                {formatNumber(production.cpo_output)} kg
                                            </Td>
                                            <Td className="text-orange-600">
                                                {formatNumber(production.kernel_output)} kg
                                            </Td>
                                            <Td className="text-gray-600">
                                                {formatNumber(production.shell_output)} kg
                                            </Td>
                                            <Td>
                                                <Badge color={parseFloat(production.cpo_extraction_rate) >= 20 ? 'green' : 'yellow'}>
                                                    {production.cpo_extraction_rate ? parseFloat(production.cpo_extraction_rate).toFixed(2) : calculateOER(production.cpo_output, production.tbs_input_weight)}%
                                                </Badge>
                                            </Td>
                                            <Td>
                                                <Badge color={parseFloat(production.kernel_extraction_rate) >= 4 ? 'green' : 'yellow'}>
                                                    {production.kernel_extraction_rate ? parseFloat(production.kernel_extraction_rate).toFixed(2) : calculateKER(production.kernel_output, production.tbs_input_weight)}%
                                                </Badge>
                                            </Td>
                                            <Td>
                                                {/* Edit: Admin only */}
                                                {user?.role === 'admin' && (
                                                    <button
                                                        onClick={() => openModal(production)}
                                                        className="text-blue-600 hover:text-blue-800"
                                                    >
                                                        <PencilIcon className="h-5 w-5" />
                                                    </button>
                                                )}
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
                title={editingProduction ? 'Edit Produksi' : 'Tambah Produksi'}
                size="lg"
            >
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Input
                            label="Tanggal Produksi"
                            name="production_date"
                            type="date"
                            value={formData.production_date}
                            onChange={handleChange}
                            error={formErrors.production_date}
                            required
                        />
                        <Input
                            label="Nomor Batch"
                            name="batch_number"
                            value={formData.batch_number}
                            onChange={handleChange}
                            disabled={!!editingProduction}
                        />
                    </div>

                    <div className="border-t pt-4">
                        <h4 className="font-medium text-gray-700 mb-3">Input</h4>
                        <Input
                            label="Berat TBS (kg)"
                            name="tbs_input_weight"
                            type="number"
                            step="0.01"
                            value={formData.tbs_input_weight}
                            onChange={handleChange}
                            error={formErrors.tbs_input_weight}
                            required
                        />
                    </div>

                    <div className="border-t pt-4">
                        <h4 className="font-medium text-gray-700 mb-3">Output</h4>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <Input
                                label="CPO (kg)"
                                name="cpo_output"
                                type="number"
                                step="0.01"
                                value={formData.cpo_output}
                                onChange={handleChange}
                            />
                            <Input
                                label="Kernel (kg)"
                                name="kernel_output"
                                type="number"
                                step="0.01"
                                value={formData.kernel_output}
                                onChange={handleChange}
                            />
                            <Input
                                label="Shell (kg)"
                                name="shell_output"
                                type="number"
                                step="0.01"
                                value={formData.shell_output}
                                onChange={handleChange}
                            />
                        </div>
                    </div>

                    {/* Extraction Rate Preview */}
                    {formData.tbs_input_weight && (formData.cpo_output || formData.kernel_output) && (
                        <div className="bg-gray-50 p-4 rounded-lg">
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span className="text-gray-500">OER:</span>
                                    <span className="ml-2 font-medium text-green-600">
                                        {calculateOER(formData.cpo_output, formData.tbs_input_weight)}%
                                    </span>
                                </div>
                                <div>
                                    <span className="text-gray-500">KER:</span>
                                    <span className="ml-2 font-medium text-green-600">
                                        {calculateKER(formData.kernel_output, formData.tbs_input_weight)}%
                                    </span>
                                </div>
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
                            {editingProduction ? 'Simpan Perubahan' : 'Tambah Produksi'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
