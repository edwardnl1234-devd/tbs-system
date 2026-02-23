import React, { useState, useEffect } from 'react';
import { PlusIcon, PencilIcon, TrashIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import { supplierApi } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';
import { Card, CardBody } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import Modal from '../../components/ui/Modal';
import Badge from '../../components/ui/Badge';
import Table, { Thead, Tbody, Tr, Th, Td } from '../../components/ui/Table';
import Pagination from '../../components/ui/Pagination';
import { PageLoading } from '../../components/ui/Loading';
import Alert from '../../components/ui/Alert';
import { getStatusColor, getStatusLabel } from '../../utils/helpers';

const supplierTypes = [
    { value: 'plasma', label: 'Plasma' },
    { value: 'inti', label: 'Inti' },
    { value: 'umum', label: 'Umum/Masyarakat' },
];

export default function SupplierList() {
    const { user } = useAuth();
    const [suppliers, setSuppliers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [filterType, setFilterType] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [pagination, setPagination] = useState({});
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingSupplier, setEditingSupplier] = useState(null);
    const [formData, setFormData] = useState({
        name: '',
        type: '',
        address: '',
        phone: '',
        contact_person: '',
        bank_name: '',
        bank_account: '',
        is_active: true,
    });
    const [formErrors, setFormErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const [alert, setAlert] = useState(null);

    useEffect(() => {
        fetchSuppliers();
    }, [currentPage, search, filterType]);

    const fetchSuppliers = async () => {
        try {
            setLoading(true);
            const response = await supplierApi.getAll({
                page: currentPage,
                search,
                type: filterType,
            });
            setSuppliers(response.data.data || []);
            setPagination(response.data.meta || {});
        } catch (error) {
            showAlert('error', 'Gagal memuat data supplier');
        } finally {
            setLoading(false);
        }
    };

    const showAlert = (type, message) => {
        setAlert({ type, message });
        setTimeout(() => setAlert(null), 5000);
    };

    const openModal = (supplier = null) => {
        if (supplier) {
            setEditingSupplier(supplier);
            setFormData({
                name: supplier.name || '',
                type: supplier.type || '',
                address: supplier.address || '',
                phone: supplier.phone || '',
                contact_person: supplier.contact_person || '',
                bank_name: supplier.bank_name || '',
                bank_account: supplier.bank_account || '',
                is_active: supplier.is_active ?? true,
            });
        } else {
            setEditingSupplier(null);
            setFormData({
                name: '',
                type: '',
                address: '',
                phone: '',
                contact_person: '',
                bank_name: '',
                bank_account: '',
                is_active: true,
            });
        }
        setFormErrors({});
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingSupplier(null);
        setFormErrors({});
    };

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value,
        }));
        if (formErrors[name]) {
            setFormErrors(prev => ({ ...prev, [name]: null }));
        }
    };

    const validateForm = () => {
        const errors = {};
        if (!formData.name) errors.name = 'Nama supplier wajib diisi';
        if (!formData.type) errors.type = 'Tipe supplier wajib dipilih';
        setFormErrors(errors);
        return Object.keys(errors).length === 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!validateForm()) return;

        setSubmitting(true);
        try {
            if (editingSupplier) {
                await supplierApi.update(editingSupplier.id, formData);
                showAlert('success', 'Supplier berhasil diperbarui');
            } else {
                await supplierApi.create(formData);
                showAlert('success', 'Supplier berhasil ditambahkan');
            }
            closeModal();
            fetchSuppliers();
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

    const handleDelete = async (supplier) => {
        if (!confirm(`Apakah Anda yakin ingin menghapus supplier "${supplier.name}"?`)) {
            return;
        }

        try {
            await supplierApi.delete(supplier.id);
            showAlert('success', 'Supplier berhasil dihapus');
            fetchSuppliers();
        } catch (error) {
            showAlert('error', 'Gagal menghapus supplier');
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
                    <h1 className="text-2xl font-bold text-gray-900">Data Supplier</h1>
                    <p className="text-gray-500">Kelola data supplier TBS</p>
                </div>
                {/* Tambah Supplier: Admin only */}
                {user?.role === 'admin' && (
                    <Button onClick={() => openModal()}>
                        <PlusIcon className="h-5 w-5 mr-2" />
                        Tambah Supplier
                    </Button>
                )}
            </div>

            {/* Filters */}
            <Card>
                <CardBody>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="relative">
                            <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <input
                                type="text"
                                placeholder="Cari supplier..."
                                value={search}
                                onChange={(e) => {
                                    setSearch(e.target.value);
                                    setCurrentPage(1);
                                }}
                                className="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                            />
                        </div>
                        <Select
                            placeholder="Semua Tipe"
                            options={supplierTypes}
                            value={filterType}
                            onChange={(e) => {
                                setFilterType(e.target.value);
                                setCurrentPage(1);
                            }}
                        />
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
                                    <Th>Nama</Th>
                                    <Th>Tipe</Th>
                                    <Th>Kontak</Th>
                                    <Th>Alamat</Th>
                                    <Th>Status</Th>
                                    <Th>Aksi</Th>
                                </Tr>
                            </Thead>
                            <Tbody>
                                {suppliers.length === 0 ? (
                                    <Tr>
                                        <Td colSpan={6} className="text-center text-gray-500 py-8">
                                            Tidak ada data supplier
                                        </Td>
                                    </Tr>
                                ) : (
                                    suppliers.map((supplier) => (
                                        <Tr key={supplier.id}>
                                            <Td className="font-medium">{supplier.name}</Td>
                                            <Td>
                                                <Badge color={supplier.type === 'inti' ? 'green' : supplier.type === 'plasma' ? 'blue' : 'yellow'}>
                                                    {supplier.type?.toUpperCase()}
                                                </Badge>
                                            </Td>
                                            <Td>
                                                <div>{supplier.contact_person}</div>
                                                <div className="text-gray-500 text-sm">{supplier.phone}</div>
                                            </Td>
                                            <Td className="max-w-xs truncate">{supplier.address}</Td>
                                            <Td>
                                                <Badge color={supplier.is_active ? 'green' : 'gray'}>
                                                    {supplier.is_active ? 'Aktif' : 'Tidak Aktif'}
                                                </Badge>
                                            </Td>
                                            <Td>
                                                <div className="flex items-center gap-2">
                                                    {/* Edit: Admin only */}
                                                    {user?.role === 'admin' && (
                                                        <button
                                                            onClick={() => openModal(supplier)}
                                                            className="text-blue-600 hover:text-blue-800"
                                                        >
                                                            <PencilIcon className="h-5 w-5" />
                                                        </button>
                                                    )}
                                                    {/* Delete: Admin only */}
                                                    {user?.role === 'admin' && (
                                                        <button
                                                            onClick={() => handleDelete(supplier)}
                                                            className="text-red-600 hover:text-red-800"
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
                title={editingSupplier ? 'Edit Supplier' : 'Tambah Supplier'}
                size="lg"
            >
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Input
                            label="Nama Supplier"
                            name="name"
                            value={formData.name}
                            onChange={handleChange}
                            error={formErrors.name}
                            required
                        />
                        <Select
                            label="Tipe Supplier"
                            name="type"
                            value={formData.type}
                            onChange={handleChange}
                            options={supplierTypes}
                            error={formErrors.type}
                            required
                        />
                        <Input
                            label="Nama Kontak"
                            name="contact_person"
                            value={formData.contact_person}
                            onChange={handleChange}
                        />
                        <Input
                            label="No. Telepon"
                            name="phone"
                            value={formData.phone}
                            onChange={handleChange}
                        />
                        <Input
                            label="Nama Bank"
                            name="bank_name"
                            value={formData.bank_name}
                            onChange={handleChange}
                        />
                        <Input
                            label="No. Rekening"
                            name="bank_account"
                            value={formData.bank_account}
                            onChange={handleChange}
                        />
                    </div>
                    <Input
                        label="Alamat"
                        name="address"
                        value={formData.address}
                        onChange={handleChange}
                    />
                    <div className="flex items-center">
                        <input
                            type="checkbox"
                            id="is_active"
                            name="is_active"
                            checked={formData.is_active}
                            onChange={handleChange}
                            className="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                        />
                        <label htmlFor="is_active" className="ml-2 text-sm text-gray-700">
                            Supplier Aktif
                        </label>
                    </div>
                    <div className="flex justify-end gap-3 pt-4">
                        <Button variant="secondary" onClick={closeModal}>
                            Batal
                        </Button>
                        <Button type="submit" loading={submitting}>
                            {editingSupplier ? 'Simpan Perubahan' : 'Tambah Supplier'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
