import React, { useState, useEffect } from 'react';
import { PlusIcon, PencilIcon, TrashIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import { useAuth } from '../../contexts/AuthContext';
import { customerApi } from '../../services/api';
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

const productTypes = [
    { value: 'cpo', label: 'CPO' },
    { value: 'kernel', label: 'Kernel' },
    { value: 'shell', label: 'Shell' },
];

export default function CustomerList() {
    const { user } = useAuth();
    const [customers, setCustomers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [pagination, setPagination] = useState({});
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingCustomer, setEditingCustomer] = useState(null);
    const [formData, setFormData] = useState({
        name: '',
        contact_person: '',
        address: '',
        phone: '',
        email: '',
        product_types: [],
        is_active: true,
    });
    const [formErrors, setFormErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const [alert, setAlert] = useState(null);

    useEffect(() => {
        fetchCustomers();
    }, [currentPage, search]);

    const fetchCustomers = async () => {
        try {
            setLoading(true);
            const response = await customerApi.getAll({
                page: currentPage,
                search,
            });
            setCustomers(response.data.data || []);
            setPagination(response.data.meta || {});
        } catch (error) {
            showAlert('error', 'Gagal memuat data customer');
        } finally {
            setLoading(false);
        }
    };

    const showAlert = (type, message) => {
        setAlert({ type, message });
        setTimeout(() => setAlert(null), 5000);
    };

    const openModal = (customer = null) => {
        if (customer) {
            setEditingCustomer(customer);
            setFormData({
                name: customer.name || '',
                contact_person: customer.contact_person || '',
                address: customer.address || '',
                phone: customer.phone || '',
                email: customer.email || '',
                product_types: customer.product_types || [],
                is_active: customer.is_active ?? true,
            });
        } else {
            setEditingCustomer(null);
            setFormData({
                name: '',
                contact_person: '',
                address: '',
                phone: '',
                email: '',
                product_types: [],
                is_active: true,
            });
        }
        setFormErrors({});
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingCustomer(null);
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

    const handleProductTypeChange = (type) => {
        setFormData(prev => {
            const types = prev.product_types.includes(type)
                ? prev.product_types.filter(t => t !== type)
                : [...prev.product_types, type];
            return { ...prev, product_types: types };
        });
    };

    const validateForm = () => {
        const errors = {};
        if (!formData.name) errors.name = 'Nama customer wajib diisi';
        setFormErrors(errors);
        return Object.keys(errors).length === 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!validateForm()) return;

        setSubmitting(true);
        try {
            if (editingCustomer) {
                await customerApi.update(editingCustomer.id, formData);
                showAlert('success', 'Customer berhasil diperbarui');
            } else {
                await customerApi.create(formData);
                showAlert('success', 'Customer berhasil ditambahkan');
            }
            closeModal();
            fetchCustomers();
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

    const handleDelete = async (customer) => {
        if (!confirm(`Apakah Anda yakin ingin menghapus customer "${customer.name}"?`)) {
            return;
        }

        try {
            await customerApi.delete(customer.id);
            showAlert('success', 'Customer berhasil dihapus');
            fetchCustomers();
        } catch (error) {
            showAlert('error', 'Gagal menghapus customer');
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
                    <h1 className="text-2xl font-bold text-gray-900">Data Customer</h1>
                    <p className="text-gray-500">Kelola data customer/pembeli</p>
                </div>
                {/* Tambah Customer: Admin only */}
                {user?.role === 'admin' && (
                    <Button onClick={() => openModal()}>
                        <PlusIcon className="h-5 w-5 mr-2" />
                        Tambah Customer
                    </Button>
                )}
            </div>

            {/* Filters */}
            <Card>
                <CardBody>
                    <div className="relative max-w-md">
                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <input
                            type="text"
                            placeholder="Cari customer..."
                            value={search}
                            onChange={(e) => {
                                setSearch(e.target.value);
                                setCurrentPage(1);
                            }}
                            className="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
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
                                    <Th>Contact Person</Th>
                                    <Th>Kontak</Th>
                                    <Th>Produk</Th>
                                    <Th>Status</Th>
                                    <Th>Aksi</Th>
                                </Tr>
                            </Thead>
                            <Tbody>
                                {customers.length === 0 ? (
                                    <Tr>
                                        <Td colSpan={6} className="text-center text-gray-500 py-8">
                                            Tidak ada data customer
                                        </Td>
                                    </Tr>
                                ) : (
                                    customers.map((customer) => (
                                        <Tr key={customer.id}>
                                            <Td className="font-medium">{customer.name}</Td>
                                            <Td>{customer.contact_person || '-'}</Td>
                                            <Td>
                                                <div>{customer.phone}</div>
                                                <div className="text-gray-500 text-sm">{customer.email}</div>
                                            </Td>
                                            <Td>
                                                <div className="flex gap-1 flex-wrap">
                                                    {(customer.product_types || []).map(type => (
                                                        <Badge 
                                                            key={type} 
                                                            color={type === 'cpo' ? 'yellow' : type === 'kernel' ? 'orange' : 'gray'}
                                                        >
                                                            {type.toUpperCase()}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </Td>
                                            <Td>
                                                <Badge color={customer.is_active ? 'green' : 'gray'}>
                                                    {customer.is_active ? 'Aktif' : 'Tidak Aktif'}
                                                </Badge>
                                            </Td>
                                            <Td>
                                                <div className="flex items-center gap-2">
                                                    {/* Edit: Admin only */}
                                                    {user?.role === 'admin' && (
                                                        <button
                                                            onClick={() => openModal(customer)}
                                                            className="text-blue-600 hover:text-blue-800"
                                                        >
                                                            <PencilIcon className="h-5 w-5" />
                                                        </button>
                                                    )}
                                                    {/* Delete: Admin only */}
                                                    {user?.role === 'admin' && (
                                                        <button
                                                            onClick={() => handleDelete(customer)}
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
                title={editingCustomer ? 'Edit Customer' : 'Tambah Customer'}
                size="lg"
            >
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Input
                            label="Nama Customer"
                            name="name"
                            value={formData.name}
                            onChange={handleChange}
                            error={formErrors.name}
                            required
                        />
                        <Input
                            label="Contact Person"
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
                            label="Email"
                            name="email"
                            type="email"
                            value={formData.email}
                            onChange={handleChange}
                        />
                    </div>
                    <Input
                        label="Alamat"
                        name="address"
                        value={formData.address}
                        onChange={handleChange}
                    />
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Jenis Produk
                        </label>
                        <div className="flex gap-4">
                            {productTypes.map(type => (
                                <label key={type.value} className="flex items-center">
                                    <input
                                        type="checkbox"
                                        checked={formData.product_types.includes(type.value)}
                                        onChange={() => handleProductTypeChange(type.value)}
                                        className="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                                    />
                                    <span className="ml-2 text-sm text-gray-700">{type.label}</span>
                                </label>
                            ))}
                        </div>
                    </div>
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
                            Customer Aktif
                        </label>
                    </div>
                    <div className="flex justify-end gap-3 pt-4">
                        <Button variant="secondary" onClick={closeModal}>
                            Batal
                        </Button>
                        <Button type="submit" loading={submitting}>
                            {editingCustomer ? 'Simpan Perubahan' : 'Tambah Customer'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
