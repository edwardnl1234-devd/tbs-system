import React, { useState, useEffect } from 'react';
import { PlusIcon, PencilIcon, TrashIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import { useAuth } from '../../contexts/AuthContext';
import { truckApi } from '../../services/api';
import { Card, CardBody } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Modal from '../../components/ui/Modal';
import Badge from '../../components/ui/Badge';
import Table, { Thead, Tbody, Tr, Th, Td } from '../../components/ui/Table';
import Pagination from '../../components/ui/Pagination';
import { PageLoading } from '../../components/ui/Loading';
import Alert from '../../components/ui/Alert';

export default function TruckList() {
    const { user } = useAuth();
    const [trucks, setTrucks] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [pagination, setPagination] = useState({});
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingTruck, setEditingTruck] = useState(null);
    const [formData, setFormData] = useState({
        plate_number: '',
        driver_name: '',
        driver_phone: '',
        capacity: '',
        is_active: true,
    });
    const [formErrors, setFormErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const [alert, setAlert] = useState(null);

    useEffect(() => {
        fetchTrucks();
    }, [currentPage, search]);

    const fetchTrucks = async () => {
        try {
            setLoading(true);
            const response = await truckApi.getAll({
                page: currentPage,
                search,
            });
            setTrucks(response.data.data || []);
            setPagination(response.data.meta || {});
        } catch (error) {
            showAlert('error', 'Gagal memuat data truk');
        } finally {
            setLoading(false);
        }
    };

    const showAlert = (type, message) => {
        setAlert({ type, message });
        setTimeout(() => setAlert(null), 5000);
    };

    const openModal = (truck = null) => {
        if (truck) {
            setEditingTruck(truck);
            setFormData({
                plate_number: truck.plate_number || '',
                driver_name: truck.driver_name || '',
                driver_phone: truck.driver_phone || '',
                capacity: truck.capacity || '',
                is_active: truck.is_active ?? true,
            });
        } else {
            setEditingTruck(null);
            setFormData({
                plate_number: '',
                driver_name: '',
                driver_phone: '',
                capacity: '',
                is_active: true,
            });
        }
        setFormErrors({});
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingTruck(null);
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
        if (!formData.plate_number) errors.plate_number = 'Nomor plat wajib diisi';
        setFormErrors(errors);
        return Object.keys(errors).length === 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!validateForm()) return;

        setSubmitting(true);
        try {
            if (editingTruck) {
                await truckApi.update(editingTruck.id, formData);
                showAlert('success', 'Truk berhasil diperbarui');
            } else {
                await truckApi.create(formData);
                showAlert('success', 'Truk berhasil ditambahkan');
            }
            closeModal();
            fetchTrucks();
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

    const handleDelete = async (truck) => {
        if (!confirm(`Apakah Anda yakin ingin menghapus truk "${truck.plate_number}"?`)) {
            return;
        }

        try {
            await truckApi.delete(truck.id);
            showAlert('success', 'Truk berhasil dihapus');
            fetchTrucks();
        } catch (error) {
            showAlert('error', 'Gagal menghapus truk');
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
                    <h1 className="text-2xl font-bold text-gray-900">Data Truk</h1>
                    <p className="text-gray-500">Kelola data truk pengangkut TBS</p>
                </div>
                {/* Tambah Truk: Admin only */}
                {user?.role === 'admin' && (
                    <Button onClick={() => openModal()}>
                        <PlusIcon className="h-5 w-5 mr-2" />
                        Tambah Truk
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
                            placeholder="Cari nomor plat atau driver..."
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
                                    <Th>Nomor Plat</Th>
                                    <Th>Nama Driver</Th>
                                    <Th>No. Telepon</Th>
                                    <Th>Kapasitas</Th>
                                    <Th>Status</Th>
                                    <Th>Aksi</Th>
                                </Tr>
                            </Thead>
                            <Tbody>
                                {trucks.length === 0 ? (
                                    <Tr>
                                        <Td colSpan={6} className="text-center text-gray-500 py-8">
                                            Tidak ada data truk
                                        </Td>
                                    </Tr>
                                ) : (
                                    trucks.map((truck) => (
                                        <Tr key={truck.id}>
                                            <Td className="font-medium font-mono">{truck.plate_number}</Td>
                                            <Td>{truck.driver_name || '-'}</Td>
                                            <Td>{truck.driver_phone || '-'}</Td>
                                            <Td>{truck.capacity ? `${truck.capacity} kg` : '-'}</Td>
                                            <Td>
                                                <Badge color={truck.is_active ? 'green' : 'gray'}>
                                                    {truck.is_active ? 'Aktif' : 'Tidak Aktif'}
                                                </Badge>
                                            </Td>
                                            <Td>
                                                <div className="flex items-center gap-2">
                                                    {/* Edit: Admin only */}
                                                    {user?.role === 'admin' && (
                                                        <button
                                                            onClick={() => openModal(truck)}
                                                            className="text-blue-600 hover:text-blue-800"
                                                        >
                                                            <PencilIcon className="h-5 w-5" />
                                                        </button>
                                                    )}
                                                    {/* Delete: Admin only */}
                                                    {user?.role === 'admin' && (
                                                        <button
                                                            onClick={() => handleDelete(truck)}
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
                title={editingTruck ? 'Edit Truk' : 'Tambah Truk'}
            >
                <form onSubmit={handleSubmit} className="space-y-4">
                    <Input
                        label="Nomor Plat"
                        name="plate_number"
                        value={formData.plate_number}
                        onChange={handleChange}
                        error={formErrors.plate_number}
                        placeholder="BK 1234 AB"
                        required
                    />
                    <Input
                        label="Nama Driver"
                        name="driver_name"
                        value={formData.driver_name}
                        onChange={handleChange}
                    />
                    <Input
                        label="No. Telepon Driver"
                        name="driver_phone"
                        value={formData.driver_phone}
                        onChange={handleChange}
                    />
                    <Input
                        label="Kapasitas (kg)"
                        name="capacity"
                        type="number"
                        value={formData.capacity}
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
                            Truk Aktif
                        </label>
                    </div>
                    <div className="flex justify-end gap-3 pt-4">
                        <Button variant="secondary" onClick={closeModal}>
                            Batal
                        </Button>
                        <Button type="submit" loading={submitting}>
                            {editingTruck ? 'Simpan Perubahan' : 'Tambah Truk'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
